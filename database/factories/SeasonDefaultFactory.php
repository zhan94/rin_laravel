<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SeasonDefault;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeasonDefault>
 */
class SeasonDefaultFactory extends Factory
{
    protected $model = SeasonDefault::class;

    /**
     * @return array<string,mixed>
     */
    public function definition(): array
    {
        return [
            'id' => 1,
            'start_month' => 9,
            'start_day' => 1,
            'end_month' => 8,
            'end_day' => 31,
            'updated_at' => now(),
        ];
    }
}
