<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingDate;
use App\Models\Location;
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
            'service_date_id' => ServiceDate::factory(),
            'booking_id' => function (array $attributes): int {
                $date = ServiceDate::query()->with('slot')->findOrFail($attributes['service_date_id']);
                $serviceId = $date->slot?->service_id;

                return (int) Booking::factory()->create([
                    'service' => $serviceId,
                    'location' => Location::factory()->state(['service_id' => $serviceId]),
                ])->id;
            },
        ];
    }
}
