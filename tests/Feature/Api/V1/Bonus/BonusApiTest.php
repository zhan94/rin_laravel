<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Bonus;

use App\Models\Bonus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BonusApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_bonus_services(): void
    {
        $this->actingAs(User::factory()->create());

        $bonus = Bonus::factory()->create([
            'title' => 'Camp',
            'title_translations' => [
                'en' => 'Camp EN',
                'de' => 'Camp DE',
                'es' => 'Camp ES',
            ],
            'location_from' => 'Sofia',
            'location_to' => 'Bansko',
        ]);

        $bonus->prices()->create([
            'days' => 2,
            'price' => 30.25,
            'per_day' => 15.13,
        ]);

        $response = $this->getJson('/api/v1/bonus');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['pagination'],
                'errors',
            ])
            ->assertJsonPath('data.0.id', $bonus->id)
            ->assertJsonPath('data.0.prices.0.days', 2)
            ->assertJsonPath('errors', []);
    }

    public function test_bonus_service_can_be_created_with_price_rows(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/bonus', [
            'translations' => [
                'en' => 'Weekend Camp',
                'de' => 'Wochenendlager',
                'es' => 'Campamento de fin de semana',
            ],
            'location_from' => 'Sofia',
            'location_to' => 'Bansko',
            'date_from' => '2026-03-01',
            'date_to' => '2026-03-05',
            'price_eur' => 45.50,
            'prices' => [
                ['days' => 2, 'price_eur' => 30.25],
                ['days' => 4, 'price_eur' => 55],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'Weekend Camp')
            ->assertJsonPath('data.translations.de', 'Wochenendlager')
            ->assertJsonPath('data.location_from', 'Sofia')
            ->assertJsonPath('data.location_to', 'Bansko')
            ->assertJsonPath('data.prices.0.days', 2)
            ->assertJsonPath('data.prices.0.price_eur', 30.25)
            ->assertJsonPath('errors', []);

        $bonusId = (int) $response->json('data.id');

        $this->assertDatabaseHas('bonus', [
            'id' => $bonusId,
            'title' => 'Weekend Camp',
            'location_from' => 'Sofia',
            'location_to' => 'Bansko',
            'price' => 45.50,
        ]);

        $this->assertDatabaseHas('bonus_price', [
            'bonus_id' => $bonusId,
            'days' => 2,
            'price' => 30.25,
            'per_day' => 15.13,
        ]);
    }

    public function test_bonus_prices_are_replaced_on_update(): void
    {
        $this->actingAs(User::factory()->create());

        $bonus = Bonus::factory()->create([
            'location_from' => 'Sofia',
            'location_to' => 'Bansko',
            'title' => 'Old bonus',
            'title_translations' => [
                'en' => 'Old bonus',
                'de' => 'Old bonus',
                'es' => 'Old bonus',
            ],
        ]);

        $bonus->prices()->createMany([
            ['days' => 1, 'price' => 39.12, 'per_day' => 39.12],
            ['days' => 2, 'price' => 30.25, 'per_day' => 15.13],
        ]);

        $response = $this->putJson("/api/v1/bonus/{$bonus->id}", [
            'title' => 'Updated bonus',
            'location_from' => 'Plovdiv',
            'location_to' => 'Pamporovo',
            'prices' => [
                ['days' => 3, 'price_eur' => 45],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated bonus')
            ->assertJsonPath('data.location_from', 'Plovdiv')
            ->assertJsonCount(1, 'data.prices')
            ->assertJsonPath('data.prices.0.days', 3);

        $this->assertDatabaseMissing('bonus_price', [
            'bonus_id' => $bonus->id,
            'days' => 1,
        ]);
        $this->assertDatabaseHas('bonus_price', [
            'bonus_id' => $bonus->id,
            'days' => 3,
            'price' => 45,
            'per_day' => 15,
        ]);
    }

    public function test_bonus_service_can_be_deleted(): void
    {
        $this->actingAs(User::factory()->create());

        $bonus = Bonus::factory()->create();

        $response = $this->deleteJson("/api/v1/bonus/{$bonus->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.deleted', true)
            ->assertJsonPath('errors', []);

        $this->assertDatabaseMissing('bonus', ['id' => $bonus->id]);
    }
}
