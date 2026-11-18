<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Card;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Card>
 */
class CardFactory extends Factory
{
    protected $model = Card::class;

    /**
     * @return array<string,mixed>
     */
    public function definition(): array
    {
        $title = fake()->words(2, true);

        return [
            'title' => $title,
            'title_translations' => [
                'en' => $title,
                'de' => $title,
                'es' => $title,
            ],
            'service' => Service::factory(),
        ];
    }
}
