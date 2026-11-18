<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

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
            'max' => fake()->numberBetween(10, 80),
            'active' => true,
        ];
    }
}
