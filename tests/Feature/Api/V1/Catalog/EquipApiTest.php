<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Catalog;

use App\Models\Equip;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_equips(): void
    {
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create();
        $equip = Equip::factory()->create([
            'service' => $service->id,
            'title' => 'Ski Set',
            'title_translations' => [
                'en' => 'Ski Set EN',
                'de' => 'Ski Set DE',
                'es' => 'Ski Set ES',
            ],
        ]);
        $equip->prices()->create([
            'days' => 2,
            'price' => 58.67,
            'per_day' => 29.34,
        ]);

        $response = $this->getJson('/api/v1/equips');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['pagination'],
                'errors',
            ])
            ->assertJsonPath('data.0.id', $equip->id)
            ->assertJsonPath('data.0.prices.0.days', 2)
            ->assertJsonPath('errors', []);
    }

    public function test_equip_can_be_created_with_price_rows(): void
    {
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create();

        $response = $this->postJson('/api/v1/equips', [
            'translations' => [
                'en' => 'Equipment',
                'de' => 'Ausrüstung',
                'es' => 'Equipo',
            ],
            'service' => $service->id,
            'prices' => [
                ['days' => 2, 'price_eur' => 30],
                ['days' => 4, 'price_eur' => 54.5],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'Equipment')
            ->assertJsonPath('data.translations.de', 'Ausrüstung')
            ->assertJsonPath('data.service_id', $service->id)
            ->assertJsonPath('data.prices.0.days', 2)
            ->assertJsonPath('data.prices.0.price_bgn', 58.67)
            ->assertJsonPath('errors', []);

        $this->assertDatabaseHas('equips', [
            'title' => 'Equipment',
            'service' => $service->id,
        ]);

        $this->assertDatabaseHas('equips_price', [
            'days' => 2,
            'price' => 58.67,
            'per_day' => 29.34,
        ]);
    }

    public function test_equip_prices_are_replaced_on_update(): void
    {
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create();
        $equip = Equip::factory()->create([
            'service' => $service->id,
            'title' => 'Old equip',
            'title_translations' => [
                'en' => 'Old equip',
                'de' => 'Old equip',
                'es' => 'Old equip',
            ],
        ]);
        $equip->prices()->createMany([
            ['days' => 1, 'price' => 29.34, 'per_day' => 29.34],
            ['days' => 2, 'price' => 48.90, 'per_day' => 24.45],
        ]);

        $response = $this->putJson("/api/v1/equips/{$equip->id}", [
            'title' => 'Updated equip',
            'service' => $service->id,
            'prices' => [
                ['days' => 3, 'price_eur' => 45],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated equip')
            ->assertJsonCount(1, 'data.prices')
            ->assertJsonPath('data.prices.0.days', 3);

        $this->assertDatabaseMissing('equips_price', [
            'equip_id' => $equip->id,
            'days' => 1,
        ]);
        $this->assertDatabaseHas('equips_price', [
            'equip_id' => $equip->id,
            'days' => 3,
            'price' => 88.01,
            'per_day' => 29.34,
        ]);
    }

    public function test_equip_can_be_deleted(): void
    {
        $this->actingAs(User::factory()->create());

        $equip = Equip::factory()->create();

        $response = $this->deleteJson("/api/v1/equips/{$equip->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.deleted', true)
            ->assertJsonPath('errors', []);

        $this->assertDatabaseMissing('equips', ['id' => $equip->id]);
    }
}
