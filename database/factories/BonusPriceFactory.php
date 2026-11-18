<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Bonus;
use App\Models\BonusPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BonusPrice>
 */
class BonusPriceFactory extends Factory
{
    protected $model = BonusPrice::class;

    /**
     * @return array<string,mixed>
     */
    public function definition(): array
    {
        $days = fake()->numberBetween(1, 30);
        $price = fake()->randomFloat(2, 20, 1200);

        return [
            'bonus_id' => Bonus::factory(),
            'price' => $price,
            'days' => $days,
            'per_day' => round($price / $days, 2),
        ];
    }
}
