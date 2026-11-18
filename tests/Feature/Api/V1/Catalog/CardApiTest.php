<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Catalog;

use App\Models\Card;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_cards(): void
    {
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create();
        $card = Card::factory()->create([
            'service' => $service->id,
            'title' => 'Beginner Card',
            'title_translations' => [
                'en' => 'Beginner Card EN',
                'de' => 'Beginner Card DE',
                'es' => 'Beginner Card ES',
            ],
        ]);
        $card->prices()->create([
            'days' => 2,
            'price' => 39.12,
            'per_day' => 19.56,
        ]);

        $response = $this->getJson('/api/v1/cards');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'meta' => ['pagination'],
                'errors',
            ])
            ->assertJsonPath('data.0.id', $card->id)
            ->assertJsonPath('data.0.prices.0.days', 2)
            ->assertJsonPath('errors', []);
    }

    public function test_card_can_be_created_with_price_rows(): void
    {
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create();

        $response = $this->postJson('/api/v1/cards', [
            'translations' => [
                'en' => 'Ski card',
                'de' => 'Skikarte',
                'es' => 'Tarjeta de esquí',
            ],
            'service' => $service->id,
            'prices' => [
                ['days' => 2, 'price_eur' => 20],
                ['days' => 4, 'price_eur' => 36.5],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'Ski card')
            ->assertJsonPath('data.translations.de', 'Skikarte')
            ->assertJsonPath('data.service_id', $service->id)
            ->assertJsonPath('data.prices.0.days', 2)
            ->assertJsonPath('data.prices.0.price_bgn', 39.12)
            ->assertJsonPath('errors', []);

        $this->assertDatabaseHas('cards', [
            'title' => 'Ski card',
            'service' => $service->id,
        ]);

        $this->assertDatabaseHas('cards_price', [
            'days' => 2,
            'price' => 39.12,
            'per_day' => 19.56,
        ]);
    }

    public function test_card_prices_are_replaced_on_update(): void
    {
        $this->actingAs(User::factory()->create());

        $service = Service::factory()->create();
        $card = Card::factory()->create([
            'service' => $service->id,
            'title' => 'Old card',
            'title_translations' => [
                'en' => 'Old card',
                'de' => 'Old card',
                'es' => 'Old card',
            ],
        ]);
        $card->prices()->createMany([
            ['days' => 1, 'price' => 19.56, 'per_day' => 19.56],
            ['days' => 2, 'price' => 29.34, 'per_day' => 14.67],
        ]);

        $response = $this->putJson("/api/v1/cards/{$card->id}", [
            'title' => 'Updated card',
            'service' => $service->id,
            'prices' => [
                ['days' => 3, 'price_eur' => 30],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated card')
            ->assertJsonCount(1, 'data.prices')
            ->assertJsonPath('data.prices.0.days', 3);

        $this->assertDatabaseMissing('cards_price', [
            'card_id' => $card->id,
            'days' => 1,
        ]);
        $this->assertDatabaseHas('cards_price', [
            'card_id' => $card->id,
            'days' => 3,
            'price' => 58.67,
            'per_day' => 19.56,
        ]);
    }

    public function test_card_can_be_deleted(): void
    {
        $this->actingAs(User::factory()->create());

        $card = Card::factory()->create();

        $response = $this->deleteJson("/api/v1/cards/{$card->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.deleted', true)
            ->assertJsonPath('errors', []);

        $this->assertDatabaseMissing('cards', ['id' => $card->id]);
    }
}
