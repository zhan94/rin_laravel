<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Seasons;

use App\Models\Bonus;
use App\Models\Booking;
use App\Models\BookingDate;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Service;
use App\Models\ServiceDate;
use App\Models\ServiceSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeasonApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_season_overview_returns_stats_and_bookings(): void
    {
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create([
            'title' => 'Ski School',
            'title_translations' => [
                'en' => 'Ski School',
                'de' => 'Skischule',
                'es' => 'Escuela de esquí',
            ],
        ]);
        $slot = ServiceSlot::factory()->create([
            'service_id' => $service->id,
            'name' => '2026/2027',
            'date_start' => '2026-12-01',
            'date_end' => '2027-03-31',
            'max' => 24,
        ]);

        $dateOne = ServiceDate::factory()->create([
            'slot_id' => $slot->id,
            'slot_date' => '2026-12-01',
        ]);
        $dateTwo = ServiceDate::factory()->create([
            'slot_id' => $slot->id,
            'slot_date' => '2026-12-02',
        ]);

        $customer = Customer::factory()->create([
            'firstname' => 'Mira',
            'lastname' => 'Petrova',
        ]);
        $location = Location::factory()->create([
            'location_from' => 'Station A',
            'location_to' => 'Slope B',
            'from_time' => '08:30',
            'to_time' => '09:00',
        ]);

        $booking = Booking::query()->create([
            'customer_id' => $customer->id,
            'service' => $service->id,
            'service_type' => Booking::SERVICE_TYPE_SERVICE,
            'sum_paid' => 300,
            'paid' => 150,
            'location' => $location->id,
            'approved' => true,
            'active' => true,
            'lunch' => false,
            'note' => '',
        ]);

        BookingDate::query()->create([
            'booking_id' => $booking->id,
            'service_date_id' => $dateOne->id,
        ]);
        BookingDate::query()->create([
            'booking_id' => $booking->id,
            'service_date_id' => $dateTwo->id,
        ]);

        $response = $this->getJson("/api/v1/seasons?type=service&service={$service->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.type', 'service')
            ->assertJsonPath('data.selected_service_id', $service->id)
            ->assertJsonPath('data.seasons.0.id', $slot->id)
            ->assertJsonPath('data.seasons.0.bookings_count', 1)
            ->assertJsonPath('data.seasons.0.customers_count', 1)
            ->assertJsonPath('data.seasons.0.dates_count', 2)
            ->assertJsonPath('data.season_bookings.0.booking_id', $booking->id)
            ->assertJsonCount(2, 'data.season_bookings.0.dates')
            ->assertJsonPath('errors', []);
    }

    public function test_service_season_can_be_created_and_overlap_is_blocked(): void
    {
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create(['max' => 18]);

        $created = $this->postJson('/api/v1/seasons', [
            'service_id' => $service->id,
            'season_name' => '',
            'season_start' => '2026-10-01',
            'season_end' => '2026-10-31',
        ]);

        $created
            ->assertCreated()
            ->assertJsonPath('data.label', '2026')
            ->assertJsonPath('errors', []);

        $createdId = (int) $created->json('data.id');

        $this->assertDatabaseHas('service_slots', [
            'id' => $createdId,
            'service_id' => $service->id,
            'date_start' => '2026-10-01 00:00:00',
            'date_end' => '2026-10-31 00:00:00',
            'max' => 18,
        ]);

        $overlap = $this->postJson('/api/v1/seasons', [
            'service_id' => $service->id,
            'season_name' => 'Overlapping season',
            'season_start' => '2026-10-15',
            'season_end' => '2026-11-15',
        ]);

        $overlap
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.code', 'season_overlap');
    }

    public function test_service_season_delete_is_blocked_when_bookings_exist(): void
    {
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create();
        $slot = ServiceSlot::factory()->create([
            'service_id' => $service->id,
            'date_start' => '2026-12-01',
            'date_end' => '2026-12-10',
        ]);
        $serviceDate = ServiceDate::factory()->create([
            'slot_id' => $slot->id,
            'slot_date' => '2026-12-05',
        ]);

        $booking = Booking::query()->create([
            'customer_id' => Customer::factory()->create()->id,
            'service' => $service->id,
            'service_type' => Booking::SERVICE_TYPE_SERVICE,
            'sum_paid' => 120,
            'paid' => 80,
            'location' => Location::factory()->create()->id,
            'approved' => false,
            'active' => true,
            'lunch' => false,
            'note' => '',
        ]);

        BookingDate::query()->create([
            'booking_id' => $booking->id,
            'service_date_id' => $serviceDate->id,
        ]);

        $blocked = $this->deleteJson("/api/v1/seasons/{$slot->id}");

        $blocked
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.code', 'season_has_bookings');

        $emptySlot = ServiceSlot::factory()->create([
            'service_id' => $service->id,
            'date_start' => '2027-01-01',
            'date_end' => '2027-01-10',
        ]);

        $deleted = $this->deleteJson("/api/v1/seasons/{$emptySlot->id}");

        $deleted
            ->assertOk()
            ->assertJsonPath('data.deleted', true)
            ->assertJsonPath('errors', []);

        $this->assertDatabaseMissing('service_slots', ['id' => $emptySlot->id]);
    }

    public function test_season_defaults_can_be_updated(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->putJson('/api/v1/seasons/defaults', [
            'default_start_month' => 10,
            'default_start_day' => 5,
            'default_end_month' => 4,
            'default_end_day' => 15,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.start_month', 10)
            ->assertJsonPath('data.start_day', 5)
            ->assertJsonPath('data.end_month', 4)
            ->assertJsonPath('data.end_day', 15)
            ->assertJsonPath('errors', []);

        $this->assertDatabaseHas('season_defaults', [
            'id' => 1,
            'start_month' => 10,
            'start_day' => 5,
            'end_month' => 4,
            'end_day' => 15,
        ]);
    }

    public function test_bonus_season_overview_uses_bonus_stats_and_bookings(): void
    {
        $this->actingAs(User::factory()->create());

        $bonus = Bonus::factory()->create([
            'title' => 'Winter Camp',
            'title_translations' => [
                'en' => 'Winter Camp',
                'de' => 'Winter Camp',
                'es' => 'Winter Camp',
            ],
            'date_from' => '2026-01-10',
            'date_to' => '2026-01-15',
            'location_from' => 'Sofia',
            'location_to' => 'Bansko',
        ]);

        $customer = Customer::factory()->create([
            'firstname' => 'Niki',
            'lastname' => 'Ivanov',
        ]);

        $location = Location::factory()->create([
            'location_from' => 'Sofia',
            'location_to' => 'Bansko',
            'from_time' => '07:00',
            'to_time' => '11:00',
        ]);

        $booking = Booking::query()->create([
            'customer_id' => $customer->id,
            'service' => $bonus->id,
            'service_type' => Booking::SERVICE_TYPE_BONUS,
            'sum_paid' => 500,
            'paid' => 300,
            'location' => $location->id,
            'approved' => true,
            'active' => true,
            'lunch' => false,
            'note' => 'bonus booking',
        ]);

        $response = $this->getJson('/api/v1/seasons?type=bonus&service=Winter%20Camp');

        $response
            ->assertOk()
            ->assertJsonPath('data.type', 'bonus')
            ->assertJsonPath('data.selected_bonus_title', 'Winter Camp')
            ->assertJsonPath('data.seasons.0.id', $bonus->id)
            ->assertJsonPath('data.seasons.0.bookings_count', 1)
            ->assertJsonPath('data.season_bookings.0.booking_id', $booking->id)
            ->assertJsonPath('errors', []);
    }
}
