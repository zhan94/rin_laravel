<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Services;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_services(): void
    {
        $this->actingAs(User::factory()->create());
        Service::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/services');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['pagination'],
                'errors',
            ])
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('errors', []);
    }

    public function test_service_can_be_created(): void
    {
        $this->actingAs(User::factory()->create());

        $payload = [
            'translations' => [
                'en' => 'Ski School',
                'de' => 'Skischule',
                'es' => 'Escuela de esquí',
            ],
            'max' => 24,
            'active' => true,
        ];

        $response = $this->postJson('/api/v1/services', $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'Ski School')
            ->assertJsonPath('data.translations.de', 'Skischule')
            ->assertJsonPath('data.max', 24)
            ->assertJsonPath('errors', []);

        $this->assertDatabaseHas('services', [
            'title' => 'Ski School',
            'max' => 24,
        ]);
    }

    public function test_core_service_cannot_be_disabled(): void
    {
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create();

        $response = $this->deleteJson("/api/v1/services/{$service->id}");

        $response
            ->assertForbidden()
            ->assertJsonPath('errors.0.code', 'service_protected');
    }
}
