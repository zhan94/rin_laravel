<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Equip;
use App\Models\EquipPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipPrice>
 */
class EquipPriceFactory extends Factory
{
    protected $model = EquipPrice::class;

    /**
     * @return array<string,mixed>
     */
    public function definition(): array
    {
        $days = fake()->numberBetween(1, 30);
        $price = fake()->randomFloat(2, 10, 500);

        return [
            'equip_id' => Equip::factory(),
            'price' => $price,
            'days' => $days,
            'per_day' => round($price / $days, 2),
        ];
    }
}
