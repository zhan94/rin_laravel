<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Bookings;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BookingPaymentApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_validate_payment_returns_true_when_amount_is_within_remaining_balance(): void
    {
        $this->actingAs(User::factory()->create());

        $booking = $this->makeBooking(sumPaidBgn: 196, paidBgn: 98);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/payments/validate", [
            'amount' => 40,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.amount_bgn', 78)
            ->assertJsonPath('data.remaining_before_bgn', 98)
            ->assertJsonPath('data.remaining_after_bgn', 20)
            ->assertJsonPath('errors', []);
    }

    public function test_validate_payment_returns_false_when_amount_exceeds_remaining_balance(): void
    {
        $this->actingAs(User::factory()->create());

        $booking = $this->makeBooking(sumPaidBgn: 196, paidBgn: 98);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/payments/validate", [
            'amount' => 80,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('data.amount_bgn', 156)
            ->assertJsonPath('data.remaining_before_bgn', 98)
            ->assertJsonPath('data.remaining_after_bgn', 0)
            ->assertJsonPath('errors', []);
    }

    public function test_apply_payment_increment_updates_booking_and_booking_payments(): void
    {
        $this->actingAs(User::factory()->create());

        $booking = $this->makeBooking(sumPaidBgn: 196, paidBgn: 0);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/payments/apply", [
            'amount' => 50,
            'mode' => 'increment',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.payment.mode', 'increment')
            ->assertJsonPath('data.payment.amount_bgn', 98)
            ->assertJsonPath('data.payment.paid_bgn', 98)
            ->assertJsonPath('data.payment.remaining_bgn', 98)
            ->assertJsonPath('errors', []);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'paid' => 98,
        ]);

        $this->assertDatabaseHas('booking_payments', [
            'booking_id' => $booking->id,
            'sum_paid' => 196,
            'paid' => 98,
        ]);
    }

    public function test_apply_payment_set_mode_replaces_paid_value(): void
    {
        $this->actingAs(User::factory()->create());

        $booking = $this->makeBooking(sumPaidBgn: 300, paidBgn: 80);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/payments/apply", [
            'amount' => 60,
            'mode' => 'set',
            'expected_paid_bgn' => 80,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.payment.mode', 'set')
            ->assertJsonPath('data.payment.paid_bgn', 117)
            ->assertJsonPath('data.payment.remaining_bgn', 183)
            ->assertJsonPath('errors', []);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'paid' => 117,
        ]);
    }

    public function test_apply_payment_rejects_overpayment(): void
    {
        $this->actingAs(User::factory()->create());

        $booking = $this->makeBooking(sumPaidBgn: 100, paidBgn: 50);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/payments/apply", [
            'amount' => 40,
            'mode' => 'increment',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.code', 'booking_payment_exceeds_remaining');
    }

    public function test_payment_retry_replays_the_receipt_without_charging_twice(): void
    {
        $this->actingAs(User::factory()->create());
        $booking = $this->makeBooking(sumPaidBgn: 300, paidBgn: 20);
        $url = "/api/v1/bookings/{$booking->id}/payments/apply";
        $payload = ['amount' => 10, 'request_key' => Str::uuid()->toString()];

        $this->postJson($url, $payload)->assertOk()->assertJsonPath('data.payment.paid_bgn', 40);
        $this->postJson($url, $payload)->assertOk()->assertJsonPath('data.payment.paid_bgn', 40);
        $this->postJson($url, array_replace($payload, ['amount' => 11]))->assertConflict();
        $this->assertDatabaseCount('crm_mutation_receipts', 1);
        $this->assertDatabaseHas('booking_payments', ['booking_id' => $booking->id, 'paid' => 40]);
    }

    public function test_stale_correction_preserves_the_current_payment(): void
    {
        $this->actingAs(User::factory()->create());
        $booking = $this->makeBooking(sumPaidBgn: 300, paidBgn: 80);
        $url = "/api/v1/bookings/{$booking->id}/payments/apply";

        $this->postJson($url, ['amount' => 60, 'mode' => 'set'])->assertUnprocessable();
        $this->postJson($url, ['amount' => 60, 'mode' => 'set', 'expected_paid_bgn' => 50])
            ->assertConflict()->assertJsonPath('errors.0.code', 'booking_payment_changed');
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'paid' => 80]);
    }

    private function makeBooking(int $sumPaidBgn, int $paidBgn): Booking
    {
        $service = Service::factory()->create();
        $customer = Customer::factory()->create();
        $location = Location::factory()->create(['service_id' => 0]);

        return Booking::query()->create([
            'customer_id' => $customer->id,
            'service' => $service->id,
            'service_type' => Booking::SERVICE_TYPE_SERVICE,
            'sum_paid' => $sumPaidBgn,
            'paid' => $paidBgn,
            'location' => $location->id,
            'approved' => false,
            'active' => true,
            'lunch' => false,
            'note' => null,
        ]);
    }
}
