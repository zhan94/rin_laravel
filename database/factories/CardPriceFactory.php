<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Card;
use App\Models\CardPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CardPrice>
 */
class CardPriceFactory extends Factory
{
    protected $model = CardPrice::class;

    /**
     * @return array<string,mixed>
     */
    public function definition(): array
    {
        $days = fake()->numberBetween(1, 30);
        $price = fake()->randomFloat(2, 10, 500);

        return [
            'card_id' => Card::factory(),
            'price' => $price,
            'days' => $days,
            'per_day' => round($price / $days, 2),
        ];
    }
}
