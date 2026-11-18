<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceSlot>
 */
class ServiceSlotFactory extends Factory
{
    protected $model = ServiceSlot::class;

    /**
     * @return array<string,mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 month', '+1 month');
        $end = fake()->dateTimeBetween($start, '+2 months');

        return [
            'service_id' => Service::factory(),
            'name' => fake()->words(3, true),
            'date_start' => $start->format('Y-m-d'),
            'date_end' => $end->format('Y-m-d'),
            'max' => fake()->numberBetween(10, 80),
            'season_type' => 'other',
            'created_at' => now(),
        ];
    }
}
