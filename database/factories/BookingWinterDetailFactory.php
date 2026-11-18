<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingWinterDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingWinterDetail>
 */
class BookingWinterDetailFactory extends Factory
{
    protected $model = BookingWinterDetail::class;

    /**
     * @return array<string,mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'skill_id' => fake()->numberBetween(1, 5),
            'equip_id' => null,
            'card_id' => null,
            'shoe_size' => fake()->numberBetween(31, 45),
            'height' => fake()->numberBetween(120, 190),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
