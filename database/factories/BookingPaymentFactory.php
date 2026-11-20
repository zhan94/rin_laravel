<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingPayment>
 */
class BookingPaymentFactory extends Factory
{
    protected $model = BookingPayment::class;

    /**
     * @return array<string,mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'sum_paid' => fn (array $attributes): ?string => Booking::query()->findOrFail($attributes['booking_id'])->sum_paid,
            'paid' => fn (array $attributes): ?string => Booking::query()->findOrFail($attributes['booking_id'])->paid,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
