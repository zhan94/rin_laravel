<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * @return array<string,mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'service' => Service::factory(),
            'service_type' => Booking::SERVICE_TYPE_SERVICE,
            'sum_paid' => 0,
            'paid' => 0,
            'location' => Location::factory(),
            'approved' => false,
            'active' => true,
            'lunch' => false,
            'note' => null,
        ];
    }
}
