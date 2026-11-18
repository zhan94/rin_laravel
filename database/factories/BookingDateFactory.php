<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingDate;
use App\Models\ServiceDate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookingDate>
 */
class BookingDateFactory extends Factory
{
    protected $model = BookingDate::class;

    /**
     * @return array<string,mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'service_date_id' => ServiceDate::factory(),
        ];
    }
}
