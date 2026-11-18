<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    /**
     * @return array<string,mixed>
     */
    public function definition(): array
    {
        return [
            'location_from' => fake()->city(),
            'location_to' => fake()->city(),
            'from_time' => fake()->time('H:i'),
            'to_time' => fake()->time('H:i'),
            'service_id' => 0,
        ];
    }
}
