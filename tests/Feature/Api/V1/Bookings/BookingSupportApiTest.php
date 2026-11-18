<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Bookings;

use App\Models\Bonus;
use App\Models\Booking;
use App\Models\BookingDate;
use App\Models\Card;
use App\Models\Customer;
use App\Models\Equip;
use App\Models\Location;
use App\Models\Service;
use App\Models\ServiceDate;
use App\Models\ServiceSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingSupportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_autocomplete_and_lookup_return_expected_data(): void
    {
        $this->actingAs(User::factory()->create());

        $customer = Customer::factory()->create([
            'firstname' => 'Samuil',
            'surname' => 'Petrov',
            'lastname' => 'Arnaudov',
            'date_birth' => '2012-01-15',
            'parent_phone' => '0888000111',
            'email' => 'samuil@example.com',
        ]);

        $autocomplete = $this->getJson('/api/v1/booking-support/customers/autocomplete?q=Samuil%20Arna');

        $autocomplete
            ->assertOk()
            ->assertJsonPath('data.0.customer_id', $customer->id)
            ->assertJsonPath('data.0.value', 'Samuil Petrov Arnaudov')
            ->assertJsonPath('errors', []);

        $lookup = $this->getJson("/api/v1/booking-support/customers/{$customer->id}");

        $lookup
            ->assertOk()
            ->assertJsonPath('data.id', $customer->id)
            ->assertJsonPath('data.full_name', 'Samuil Petrov Arnaudov')
            ->assertJsonPath('data.email', 'samuil@example.com')
            ->assertJsonPath('errors', []);
    }

    public function test_service_support_endpoints_return_options_slots_dates_and_availability(): void
    {
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create([
            'title' => 'Ski',
            'title_translations' => [
                'en' => 'Ski',
                'de' => 'Ski',
                'es' => 'Ski',
            ],
        ]);
        $otherService = Service::factory()->create();

        Card::factory()->create([
            'service' => $service->id,
            'title' => 'Card A',
            'title_translations' => [
                'en' => 'Card A',
                'de' => 'Card A',
                'es' => 'Card A',
            ],
        ]);
        Card::factory()->create([
            'service' => $otherService->id,
            'title' => 'Card B',
            'title_translations' => [
                'en' => 'Card B',
                'de' => 'Card B',
                'es' => 'Card B',
            ],
        ]);

        Equip::factory()->create([
            'service' => $service->id,
            'title' => 'Equip A',
            'title_translations' => [
                'en' => 'Equip A',
                'de' => 'Equip A',
                'es' => 'Equip A',
            ],
        ]);
        Equip::factory()->create([
            'service' => $otherService->id,
            'title' => 'Equip B',
            'title_translations' => [
                'en' => 'Equip B',
                'de' => 'Equip B',
                'es' => 'Equip B',
            ],
        ]);

        $slot = ServiceSlot::factory()->create([
            'service_id' => $service->id,
            'name' => '2026/2027',
            'date_start' => '2026-09-01',
            'date_end' => '2027-08-31',
            'max' => 20,
        ]);

        $serviceDate = ServiceDate::factory()->create([
            'slot_id' => $slot->id,
            'slot_date' => '2026-12-22',
            'is_active' => true,
        ]);

        Location::factory()->create([
            'service_id' => 0,
            'location_from' => 'Sofia',
            'location_to' => 'Bansko',
            'from_time' => '07:00',
            'to_time' => '10:00',
        ]);

        Location::factory()->create([
            'service_id' => $service->id,
            'location_from' => 'Varna',
            'location_to' => 'Pamporovo',
            'from_time' => '06:00',
            'to_time' => '11:00',
        ]);

        $customer = Customer::factory()->create();

        $booking = Booking::query()->create([
            'customer_id' => $customer->id,
            'service' => $service->id,
            'service_type' => Booking::SERVICE_TYPE_SERVICE,
            'sum_paid' => 100,
            'paid' => 40,
            'location' => null,
            'approved' => false,
            'active' => true,
            'lunch' => false,
            'note' => null,
        ]);

        BookingDate::query()->create([
            'booking_id' => $booking->id,
            'service_date_id' => $serviceDate->id,
        ]);

        $options = $this->getJson("/api/v1/booking-support/services/{$service->id}/options");

        $options
            ->assertOk()
            ->assertJsonCount(1, 'data.cards')
            ->assertJsonCount(1, 'data.equips')
            ->assertJsonPath('data.cards.0.title', 'Card A')
            ->assertJsonPath('data.cards.0.translations.en', 'Card A')
            ->assertJsonPath('data.equips.0.title', 'Equip A')
            ->assertJsonPath('data.equips.0.translations.en', 'Equip A')
            ->assertJsonPath('errors', []);

        $slots = $this->getJson("/api/v1/booking-support/services/{$service->id}/slots");

        $slots
            ->assertOk()
            ->assertJsonFragment([
                'id' => $slot->id,
                'name' => '2026/2027',
            ])
            ->assertJsonPath('errors', []);

        $dates = $this->getJson("/api/v1/booking-support/services/{$service->id}/dates?slot_id={$slot->id}");

        $dates
            ->assertOk()
            ->assertJsonPath('data.0.slot_date', '2026-12-22')
            ->assertJsonPath('errors', []);

        $availability = $this->getJson(
            "/api/v1/booking-support/services/{$service->id}/availability?customer_id={$customer->id}&slot_id={$slot->id}",
        );

        $availability
            ->assertOk()
            ->assertJsonFragment(['2026-12-22'])
            ->assertJsonCount(2, 'data.locations')
            ->assertJsonCount(1, 'data.location_groups.main')
            ->assertJsonCount(1, 'data.location_groups.custom')
            ->assertJsonPath('errors', []);
    }

    public function test_bonus_context_returns_location_and_dates(): void
    {
        $this->actingAs(User::factory()->create());

        $bonus = Bonus::factory()->create([
            'location_from' => 'Sofia',
            'location_to' => 'Bansko',
            'date_from' => '2026-01-10',
            'date_to' => '2026-01-20',
        ]);

        $context = $this->getJson("/api/v1/booking-support/bonus/{$bonus->id}/context");

        $context
            ->assertOk()
            ->assertJsonPath('data.found', true)
            ->assertJsonPath('data.location', 'Sofia - Bansko')
            ->assertJsonPath('data.date_from', '2026-01-10')
            ->assertJsonPath('data.date_to', '2026-01-20')
            ->assertJsonPath('errors', []);

        $missing = $this->getJson('/api/v1/booking-support/bonus/999999/context');

        $missing
            ->assertOk()
            ->assertJsonPath('data.found', false)
            ->assertJsonPath('data.location', '')
            ->assertJsonPath('errors', []);
    }

    public function test_prefill_returns_latest_service_booking_with_ski_meta(): void
    {
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create([
            'id' => 1,
            'title' => 'Ski school',
            'title_translations' => [
                'en' => 'Ski school',
                'de' => 'Ski school',
                'es' => 'Ski school',
            ],
            'max' => 30,
        ]);

        $customer = Customer::factory()->create();
        $location = Location::factory()->create(['service_id' => 0]);

        $slot = ServiceSlot::factory()->create([
            'service_id' => $service->id,
            'name' => '2026/2027',
            'date_start' => '2026-09-01',
            'date_end' => '2027-08-31',
            'max' => 30,
        ]);

        $serviceDate = ServiceDate::factory()->create([
            'slot_id' => $slot->id,
            'slot_date' => '2026-12-15',
            'is_active' => true,
        ]);

        $note = "Legacy note\n\n[[SKI_META]]\n{\"shoe_size\":\"\",\"height\":\"\",\"skill_id\":\"2\",\"equip_enabled\":\"0\"}\n[[/SKI_META]]";

        $booking = Booking::query()->create([
            'customer_id' => $customer->id,
            'service' => $service->id,
            'service_type' => Booking::SERVICE_TYPE_SERVICE,
            'sum_paid' => 250,
            'paid' => 100,
            'location' => $location->id,
            'approved' => true,
            'active' => true,
            'lunch' => false,
            'note' => $note,
        ]);

        BookingDate::query()->create([
            'booking_id' => $booking->id,
            'service_date_id' => $serviceDate->id,
        ]);

        $response = $this->getJson(
            "/api/v1/booking-support/prefill?customer_id={$customer->id}&service_id={$service->id}&selected_date=15-12-2026",
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.found', true)
            ->assertJsonPath('data.booking.id', $booking->id)
            ->assertJsonPath('data.booking.location', $location->id)
            ->assertJsonPath('data.booking.skill_id', 2)
            ->assertJsonPath('data.booking.equip_enabled', true)
            ->assertJsonPath('data.booking.note', 'Legacy note')
            ->assertJsonPath('data.booking.ski_form.skill_id', '2')
            ->assertJsonPath('data.booking.season_start', '2026-09-01')
            ->assertJsonPath('data.booking.season_end', '2027-08-31')
            ->assertJsonPath('errors', []);
    }
}
