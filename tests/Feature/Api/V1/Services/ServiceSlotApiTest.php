<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Services;

use App\Models\BookingDate;
use App\Models\Service;
use App\Models\ServiceDate;
use App\Models\ServiceSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceSlotApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_slot_can_be_created_with_dates(): void
    {
        $this->actingAs(User::factory()->create());
        $service = Service::factory()->create();

        $payload = [
            'name' => 'Ski School 2026/2027',
            'max' => 30,
            'season_type' => 'winter',
            'dates' => ['2026-12-01', '2026-12-03', '2026-12-02'],
        ];

        $response = $this->postJson("/api/v1/services/{$service->id}/slots", $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Ski School 2026/2027')
            ->assertJsonPath('data.date_start', '2026-12-01')
            ->assertJsonPath('data.date_end', '2026-12-03')
            ->assertJsonPath('data.dates.0.slot_date', '2026-12-01')
            ->assertJsonPath('errors', []);

        $slotId = (int) $response->json('data.id');
        $this->assertDatabaseCount('service_dates', 3);
        $this->assertDatabaseHas('service_slots', [
            'id' => $slotId,
            'service_id' => $service->id,
            'season_type' => 'winter',
        ]);
    }

    public function test_slot_dates_are_replaced_on_update(): void
    {
        $this->actingAs(User::factory()->create());
        $service = Service::factory()->create();

        $created = $this->postJson("/api/v1/services/{$service->id}/slots", [
            'name' => 'Camp Slot',
            'max' => 20,
            'season_type' => 'summer',
            'dates' => ['2026-06-01', '2026-06-02', '2026-06-03'],
        ])->assertCreated();

        $slotId = (int) $created->json('data.id');

        $updated = $this->putJson("/api/v1/slots/{$slotId}", [
            'name' => 'Camp Slot Updated',
            'max' => 25,
            'season_type' => 'summer',
            'dates' => ['2026-06-10', '2026-06-11'],
        ]);

        $updated
            ->assertOk()
            ->assertJsonPath('data.name', 'Camp Slot Updated')
            ->assertJsonPath('data.date_start', '2026-06-10')
            ->assertJsonPath('data.date_end', '2026-06-11')
            ->assertJsonCount(2, 'data.dates');

        $this->assertDatabaseMissing('service_dates', [
            'slot_id' => $slotId,
            'slot_date' => '2026-06-01',
        ]);
        $this->assertDatabaseHas('service_dates', [
            'slot_id' => $slotId,
            'slot_date' => '2026-06-10',
        ]);
    }

    public function test_slot_edits_keep_booked_dates_and_reject_removal(): void
    {
        $this->actingAs(User::factory()->create());
        $date = ServiceDate::factory()->create();
        BookingDate::factory()->create(['service_date_id' => $date->id]);
        $url = "/api/v1/slots/{$date->slot_id}";
        $payload = ['name' => 'Synthetic slot', 'max' => 20, 'season_type' => 'winter'];

        $this->putJson($url, $payload + ['dates' => [substr((string) $date->getRawOriginal('slot_date'), 0, 10)]])->assertOk();
        $this->assertDatabaseHas('booking_dates', ['service_date_id' => $date->id]);
        $this->putJson($url, $payload + ['dates' => ['2100-01-01']])->assertUnprocessable();
        $this->deleteJson($url)->assertUnprocessable();
        $this->assertDatabaseHas('booking_dates', ['service_date_id' => $date->id]);
    }

    public function test_service_period_writes_reject_overlaps_on_unscheduled_dates_and_boundaries(): void
    {
        $this->actingAs(User::factory()->create());
        $service = Service::factory()->create();
        $payload = ['name' => 'Original period', 'max' => 10, 'dates' => ['2027-02-11', '2027-02-13']];
        $endpoint = "/api/v1/services/{$service->id}/slots";

        $originalId = $this->postJson($endpoint, $payload)->assertCreated()->json('data.id');

        $this->postJson($endpoint, array_replace($payload, ['dates' => ['2027-02-12']]))
            ->assertUnprocessable()->assertJsonPath('errors.0.code', 'season_overlap');
        $this->postJson($endpoint, array_replace($payload, ['dates' => ['2027-02-10', '2027-02-11']]))
            ->assertUnprocessable()->assertJsonPath('errors.0.code', 'season_overlap');

        $adjacentId = $this->postJson($endpoint, array_replace($payload, ['dates' => ['2027-02-14']]))
            ->assertCreated()->json('data.id');

        $this->putJson("/api/v1/slots/{$adjacentId}", array_replace($payload, ['dates' => ['2027-02-13', '2027-02-14']]))
            ->assertUnprocessable()->assertJsonPath('errors.0.code', 'season_overlap');
        $this->putJson("/api/v1/slots/{$originalId}", array_replace($payload, ['max' => 15]))->assertOk();

        $this->assertSame(2, ServiceSlot::query()->where('service_id', $service->id)->count());
        $this->assertDatabaseHas('service_slots', ['id' => $adjacentId, 'max' => 10]);
    }

    public function test_slot_requires_dates(): void
    {
        $this->actingAs(User::factory()->create());
        $service = Service::factory()->create();

        $response = $this->postJson("/api/v1/services/{$service->id}/slots", [
            'name' => 'Broken Slot',
            'max' => 10,
            'dates' => [],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('errors.0.code', 'validation_error');
    }
}
