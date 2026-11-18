<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Locations;

use App\Models\Location;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_locations(): void
    {
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create(['title' => 'Ski School', 'title_en' => 'Ski School']);
        $location = Location::factory()->create([
            'location_from' => 'Station A',
            'location_to' => 'Slope B',
            'from_time' => '08:30',
            'to_time' => '09:00',
            'service_id' => $service->id,
        ]);

        $response = $this->getJson('/api/v1/locations');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['pagination'],
                'errors',
            ])
            ->assertJsonPath('data.0.id', $location->id)
            ->assertJsonPath('data.0.service_id', $service->id)
            ->assertJsonPath('errors', []);
    }

    public function test_location_can_be_created_with_normalized_time(): void
    {
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create();

        $response = $this->postJson('/api/v1/locations', [
            'location_from' => 'Sofia',
            'location_to' => 'Bansko',
            'from_time' => '8:5',
            'to_time' => '9:3',
            'service_id' => $service->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.location_from', 'Sofia')
            ->assertJsonPath('data.location_to', 'Bansko')
            ->assertJsonPath('data.from_time', '08:05')
            ->assertJsonPath('data.to_time', '09:03')
            ->assertJsonPath('data.service_id', $service->id)
            ->assertJsonPath('errors', []);

        $this->assertDatabaseHas('locations', [
            'location_from' => 'Sofia',
            'location_to' => 'Bansko',
            'from_time' => '08:05',
            'to_time' => '09:03',
            'service_id' => $service->id,
        ]);
    }

    public function test_location_can_be_updated(): void
    {
        $this->actingAs(User::factory()->create());

        $serviceA = Service::factory()->create(['title' => 'A', 'title_en' => 'A']);
        $serviceB = Service::factory()->create(['title' => 'B', 'title_en' => 'B']);

        $location = Location::factory()->create([
            'service_id' => $serviceA->id,
            'location_from' => 'Old start',
            'location_to' => 'Old end',
            'from_time' => '07:00',
            'to_time' => '08:00',
        ]);

        $response = $this->putJson("/api/v1/locations/{$location->id}", [
            'location_from' => 'New start',
            'location_to' => 'New end',
            'from_time' => '10:15',
            'to_time' => '11:45',
            'service_id' => $serviceB->id,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $location->id)
            ->assertJsonPath('data.location_from', 'New start')
            ->assertJsonPath('data.location_to', 'New end')
            ->assertJsonPath('data.from_time', '10:15')
            ->assertJsonPath('data.to_time', '11:45')
            ->assertJsonPath('data.service_id', $serviceB->id)
            ->assertJsonPath('errors', []);

        $this->assertDatabaseHas('locations', [
            'id' => $location->id,
            'location_from' => 'New start',
            'location_to' => 'New end',
            'from_time' => '10:15',
            'to_time' => '11:45',
            'service_id' => $serviceB->id,
        ]);
    }

    public function test_location_can_be_deleted(): void
    {
        $this->actingAs(User::factory()->create());

        $location = Location::factory()->create();

        $response = $this->deleteJson("/api/v1/locations/{$location->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.deleted', true)
            ->assertJsonPath('errors', []);

        $this->assertDatabaseMissing('locations', ['id' => $location->id]);
    }
}
