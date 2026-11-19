<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Services;

use App\Enums\RoleKey;
use App\Models\Location;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceDate;
use App\Models\ServiceSlot;
use App\Models\User;
use App\Support\Roles\RoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceScheduleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_lists_each_season_with_supported_search_sort_and_filters(): void
    {
        $this->travelTo(now()->setDate(2026, 12, 1));
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create([
            'title' => 'Ski Rental',
            'title_translations' => ['en' => 'Ski Rental', 'de' => 'Skiverleih'],
        ]);
        $service->prices()->create(['days' => 1, 'price' => 42, 'per_day' => 42]);
        $service->prices()->create(['days' => 3, 'price' => 100, 'per_day' => 33.33]);

        $active = $this->season($service, 'Holiday', '2026-12-01', '2026-12-01', 12);
        $expired = $this->season($service, 'Autumn', '2026-11-01', '2026-11-30', 6);
        $upcoming = $this->season($service, 'Winter', '2026-12-02', '2026-12-31', 15);
        $other = Service::factory()->create(['title' => 'Hiking', 'title_translations' => null]);
        $this->season($other, 'No dates', null, null, 10);
        ServiceDate::factory()->create(['slot_id' => $active->id, 'slot_date' => '2026-12-01']);

        $url = '/api/v1/service-seasons';
        $this->getJson("{$url}?q=Skiverleih&sort=date&direction=desc")->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.id', $upcoming->id)
            ->assertJsonPath('data.0.status', 'upcoming')
            ->assertJsonPath('data.1.id', $active->id)
            ->assertJsonPath('data.1.capacity', 12)
            ->assertJsonPath('data.1.status', 'active')
            ->assertJsonPath('data.1.service_name', 'Ski Rental')
            ->assertJsonPath('data.1.dates_count', 1)
            ->assertJsonPath('data.1.prices.0.price', '42.00')
            ->assertJsonPath('data.1.prices.1.days', 3)
            ->assertJsonPath('data.2.id', $expired->id)
            ->assertJsonPath('data.2.status', 'expired')
            ->assertJsonPath('meta.counts', ['services' => 2, 'seasons' => 4, 'dates' => 1]);
        $this->getJson("{$url}?service_id={$service->id}&date=2026-12-01&status=active")->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $active->id);
        $this->getJson("{$url}?q=Holiday&date=2026-12-02")->assertOk()->assertJsonCount(0, 'data');
        $this->getJson("{$url}?service_id={$service->id}&sort=season")->assertOk()
            ->assertJsonPath('data.0.id', $expired->id);
        $this->getJson("{$url}?sort=status")->assertOk()
            ->assertJsonPath('data.0.id', $active->id)
            ->assertJsonPath('data.3.status', 'unscheduled')
            ->assertJsonPath('data.3.prices', []);
        $this->getJson("{$url}?sort=service")->assertOk()->assertJsonPath('data.0.service_name', 'Hiking');
        $this->getJson("{$url}?direction=invalid")->assertUnprocessable();
        $this->assertDatabaseCount('services', 2);
    }

    public function test_daily_availability_matches_booking_creation_editing_and_cancellation(): void
    {
        $this->actingAs(User::factory()->create());
        $service = Service::factory()->create(['max' => 20]);
        $slot = $this->season($service, 'Winter', '2026-12-01', '2026-12-15', 1);
        $other = $this->season($service, 'Later winter', '2026-12-16', '2026-12-31', 5);
        $location = Location::factory()->create(['service_id' => $service->id]);
        $first = '2026-12-08';
        $second = '2026-12-09';

        $days = "/api/v1/service-seasons/{$slot->id}/dates";
        $support = "/api/v1/booking-support/services/{$service->id}/availability";
        $this->getJson("{$days}?date={$first}")->assertOk()->assertJsonCount(0, 'data');

        $payload = [
            'firstname' => 'Synthetic', 'lastname' => 'Schedule',
            'service_id' => $service->id, 'location_id' => $location->id,
            'dates' => [$first], 'skill_id' => 1,
        ];
        $booking = $this->postJson('/api/v1/bookings', $payload)->assertCreated();
        $id = $booking->json('data.id');
        $customer = $booking->json('data.customer_id');

        $firstResponse = $this->getJson("{$days}?date={$first}")->assertOk()
            ->assertJsonPath('data.0.booked', 1)
            ->assertJsonPath('data.0.remaining', 0)
            ->assertJsonPath('meta.capacity', 1);
        $dayId = $firstResponse->json('data.0.id');
        $this->getJson("{$days}/{$dayId}/bookings")->assertOk()
            ->assertJsonPath('data.0.customer', 'Synthetic Schedule')
            ->assertJsonPath('data.0.status', 'active')
            ->assertJsonMissingPath('data.0.email');
        $this->getJson("/api/v1/service-seasons/{$other->id}/dates/{$dayId}/bookings")->assertNotFound();
        $this->getJson($support)->assertOk()->assertJsonPath('data.full_dates', [$first]);
        $this->getJson("{$support}?slot_id={$other->id}")->assertOk()->assertJsonPath('data.full_dates', []);
        $this->postJson('/api/v1/bookings', array_replace($payload, ['lastname' => 'Second']))
            ->assertUnprocessable()->assertJsonPath('errors.0.code', 'booking_capacity_exceeded');

        $this->putJson("/api/v1/bookings/{$id}", ['customer_id' => $customer] + $payload)->assertOk();
        $this->putJson("/api/v1/bookings/{$id}", array_replace(
            $payload, ['customer_id' => $customer, 'dates' => [$second], 'approved' => true],
        ))->assertOk();

        $this->getJson("{$days}?date={$first}")->assertOk()->assertJsonPath('data.0.remaining', 1);
        $this->getJson("{$days}?date={$second}")->assertOk()->assertJsonPath('data.0.remaining', 0);
        $this->getJson($support)->assertOk()->assertJsonPath('data.full_dates', [$second]);

        $this->deleteJson("/api/v1/bookings/{$id}")->assertOk();
        $dayId = $this->getJson("{$days}?date={$second}")->assertOk()
            ->assertJsonPath('data.0.remaining', 1)
            ->assertJsonPath('data.0.booked', 0)
            ->assertJsonPath('data.0.bookings_count', 1)
            ->json('data.0.id');
        $this->getJson("{$days}/{$dayId}/bookings")->assertOk()
            ->assertJsonPath('data.0.status', 'cancelled');
        $this->getJson($support)->assertOk()->assertJsonPath('data.full_dates', []);
    }

    public function test_schedule_does_not_expand_driver_access(): void
    {
        $role = Role::query()->firstOrCreate(
            ['role_key' => RoleKey::DRIVER->value],
            RoleCatalog::payload(RoleKey::DRIVER, 50),
        );
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
        $this->getJson('/api/v1/service-seasons')->assertForbidden();
    }

    private function season(Service $service, string $name, ?string $start, ?string $end, int $capacity): ServiceSlot
    {
        return ServiceSlot::factory()->create([
            'service_id' => $service->id,
            'name' => $name,
            'date_start' => $start,
            'date_end' => $end,
            'max' => $capacity,
        ]);
    }
}
