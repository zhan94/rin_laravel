<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RoleKey;
use App\Models\Role;
use App\Support\Roles\RoleCatalog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * @return array<string,mixed>
     */
    public function definition(): array
    {
        $roleKey = RoleKey::SUPER_ADMIN;

        return [
            'role_key' => $roleKey->value,
            ...RoleCatalog::payload($roleKey, 1),
        ];
    }
}
