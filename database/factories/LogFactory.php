<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Log;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Log>
 */
class LogFactory extends Factory
{
    protected $model = Log::class;

    /**
     * @return array<string,mixed>
     */
    public function definition(): array
    {
        return [
            'user' => User::factory(),
            'page' => '/crm/bookings',
            'ip' => fake()->ipv4(),
            'location' => fake()->optional()->city(),
            'date' => now(),
        ];
    }
}
