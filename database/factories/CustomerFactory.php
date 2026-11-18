<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * @return array<string,mixed>
     */
    public function definition(): array
    {
        return [
            'firstname' => fake()->firstName(),
            'surname' => null,
            'lastname' => fake()->lastName(),
            'date_birth' => fake()->date(),
            'age' => fake()->numberBetween(6, 75),
            'parent_name' => fake()->name(),
            'parent_phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'active' => true,
        ];
    }
}
