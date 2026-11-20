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

        $booking = $this->makeBooking(sumPaid: 100.25, paid: 49.82);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/payments/validate", [
            'amount' => 40.13,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.amount_eur', 40.13)
            ->assertJsonPath('data.remaining_before_eur', 50.43)
            ->assertJsonPath('data.remaining_after_eur', 10.3)
            ->assertJsonPath('errors', []);
    }

    public function test_validate_payment_returns_false_when_amount_exceeds_remaining_balance(): void
    {
        $this->actingAs(User::factory()->create());

        $booking = $this->makeBooking(sumPaid: 100.25, paid: 49.82);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/payments/validate", [
            'amount' => 80,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.valid', false)
            ->assertJsonPath('data.amount_eur', 80)
            ->assertJsonPath('data.remaining_before_eur', 50.43)
            ->assertJsonPath('data.remaining_after_eur', 0)
            ->assertJsonPath('errors', []);
    }

    public function test_apply_payment_increment_updates_booking_and_booking_payments(): void
    {
        $this->actingAs(User::factory()->create());

        $booking = $this->makeBooking(sumPaid: 100.25, paid: 0);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/payments/apply", [
            'amount' => 50.18,
            'mode' => 'increment',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.payment.mode', 'increment')
            ->assertJsonPath('data.payment.amount_eur', 50.18)
            ->assertJsonPath('data.payment.paid_eur', 50.18)
            ->assertJsonPath('data.payment.remaining_eur', 50.07)
            ->assertJsonPath('errors', []);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'paid' => 50.18,
        ]);

        $this->assertDatabaseHas('booking_payments', [
            'booking_id' => $booking->id,
            'sum_paid' => 100.25,
            'paid' => 50.18,
        ]);
    }

    public function test_apply_payment_set_mode_replaces_paid_value(): void
    {
        $this->actingAs(User::factory()->create());

        $booking = $this->makeBooking(sumPaid: 150.27, paid: 40.90);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/payments/apply", [
            'amount' => 60.03,
            'mode' => 'set',
            'expected_paid_eur' => 40.90,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.payment.mode', 'set')
            ->assertJsonPath('data.payment.paid_eur', 60.03)
            ->assertJsonPath('data.payment.remaining_eur', 90.24)
            ->assertJsonPath('errors', []);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'paid' => 60.03,
        ]);
    }

    public function test_apply_payment_rejects_overpayment(): void
    {
        $this->actingAs(User::factory()->create());

        $booking = $this->makeBooking(sumPaid: 100, paid: 75);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/payments/apply", [
            'amount' => 40.13,
            'mode' => 'increment',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.code', 'booking_payment_exceeds_remaining');
    }

    public function test_payment_retry_replays_the_receipt_without_charging_twice(): void
    {
        $this->actingAs(User::factory()->create());
        $booking = $this->makeBooking(sumPaid: 150.75, paid: 10.24);
        $url = "/api/v1/bookings/{$booking->id}/payments/apply";
        $payload = ['amount' => 10.05, 'request_key' => Str::uuid()->toString()];

        $this->postJson($url, $payload)->assertOk()->assertJsonPath('data.payment.paid_eur', 20.29);
        $this->postJson($url, $payload)->assertOk()->assertJsonPath('data.payment.paid_eur', 20.29);
        $this->postJson($url, array_replace($payload, ['amount' => 11]))->assertConflict();
        $this->assertDatabaseCount('crm_mutation_receipts', 1);
        $this->assertDatabaseHas('booking_payments', ['booking_id' => $booking->id, 'paid' => 20.29]);
    }

    public function test_stale_correction_preserves_the_current_payment(): void
    {
        $this->actingAs(User::factory()->create());
        $booking = $this->makeBooking(sumPaid: 150.27, paid: 40.90);
        $url = "/api/v1/bookings/{$booking->id}/payments/apply";

        $this->postJson($url, ['amount' => 60.03, 'mode' => 'set'])->assertUnprocessable();
        $this->postJson($url, ['amount' => 60.03, 'mode' => 'set', 'expected_paid_eur' => 40.89])
            ->assertConflict()->assertJsonPath('errors.0.code', 'booking_payment_changed');
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'paid' => 40.90]);
    }

    private function makeBooking(float $sumPaid, float $paid): Booking
    {
        $service = Service::factory()->create();
        $customer = Customer::factory()->create();
        $location = Location::factory()->create(['service_id' => 0]);

        return Booking::query()->create([
            'customer_id' => $customer->id,
            'service' => $service->id,
            'service_type' => Booking::SERVICE_TYPE_SERVICE,
            'sum_paid' => $sumPaid,
            'paid' => $paid,
            'location' => $location->id,
            'approved' => false,
            'active' => true,
            'lunch' => false,
            'note' => null,
        ]);
    }
}
