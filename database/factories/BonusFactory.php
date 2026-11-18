<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Bonus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bonus>
 */
class BonusFactory extends Factory
{
    protected $model = Bonus::class;

    /**
     * @return array<string,mixed>
     */
    public function definition(): array
    {
        $title = fake()->words(2, true);

        return [
            'title' => $title,
            'title_translations' => [
                'en' => $title,
                'de' => $title,
                'es' => $title,
            ],
            'title_key' => null,
            'location_from' => fake()->city(),
            'location_to' => fake()->city(),
            'date_from' => fake()->dateTimeBetween('-1 year', '+1 year')->format('Y-m-d'),
            'date_to' => fake()->dateTimeBetween('+1 year', '+2 year')->format('Y-m-d'),
            'price' => fake()->randomFloat(2, 10, 600),
            'description' => fake()->sentence(),
        ];
    }
}
