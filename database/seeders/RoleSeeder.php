<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleKey;
use App\Models\Role;
use Database\Seeders\Support\SeedCatalog;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DatabaseSeeder::assertIsolatedDatabase();

        $roles = [
            [
                'role_key' => RoleKey::SUPER_ADMIN->value,
                'title_translations' => SeedCatalog::translations(SeedCatalog::roleTitles()[RoleKey::SUPER_ADMIN->value]),
                'sort_order' => 1,
            ],
            [
                'role_key' => RoleKey::ADMIN->value,
                'title_translations' => SeedCatalog::translations(SeedCatalog::roleTitles()[RoleKey::ADMIN->value]),
                'sort_order' => 2,
            ],
            [
                'role_key' => RoleKey::MANAGER->value,
                'title_translations' => SeedCatalog::translations(SeedCatalog::roleTitles()[RoleKey::MANAGER->value]),
                'sort_order' => 3,
            ],
            [
                'role_key' => RoleKey::GROUP_LEADER->value,
                'title_translations' => SeedCatalog::translations(SeedCatalog::roleTitles()[RoleKey::GROUP_LEADER->value]),
                'sort_order' => 4,
            ],
            [
                'role_key' => RoleKey::DRIVER->value,
                'title_translations' => SeedCatalog::translations(SeedCatalog::roleTitles()[RoleKey::DRIVER->value]),
                'sort_order' => 5,
            ],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(
                ['role_key' => $role['role_key']],
                [
                    ...$role,
                    'is_active' => true,
                ],
            );
        }
    }
}
