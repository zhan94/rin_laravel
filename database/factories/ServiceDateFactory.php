<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ServiceDate;
use App\Models\ServiceSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceDate>
 */
class ServiceDateFactory extends Factory
{
    protected $model = ServiceDate::class;

    /**
     * @return array<string,mixed>
     */
    public function definition(): array
    {
        return [
            'slot_id' => ServiceSlot::factory(),
            'slot_date' => fake()->date(),
            'is_active' => true,
        ];
    }
}
