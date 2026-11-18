<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleKey;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        DatabaseSeeder::assertIsolatedDatabase();

        $password = Hash::make('password');

        foreach ($this->users() as $user) {
            $roleId = Role::query()
                ->where('role_key', $user['role_key'])
                ->value('id');

            if ($roleId === null) {
                continue;
            }

            User::query()->updateOrCreate(
                ['username' => $user['username']],
                [
                    'firstname' => $user['firstname'],
                    'surname' => $user['surname'],
                    'lastname' => $user['lastname'],
                    'role_id' => (int) $roleId,
                    'email' => $user['email'],
                    'password' => $password,
                    'email_verified_at' => now(),
                ],
            );
        }
    }

    /**
     * @return list<array{username:string,email:string,firstname:string,surname:?string,lastname:string,role_key:string}>
     */
    private function users(): array
    {
        return [
            [
                'username' => 'superadmin.test',
                'email' => 'superadmin@example.test',
                'firstname' => 'System',
                'surname' => null,
                'lastname' => 'Owner',
                'role_key' => RoleKey::SUPER_ADMIN->value,
            ],
            [
                'username' => 'admin.test',
                'email' => 'admin@example.test',
                'firstname' => 'Alice',
                'surname' => null,
                'lastname' => 'Admin',
                'role_key' => RoleKey::ADMIN->value,
            ],
            [
                'username' => 'manager.test',
                'email' => 'manager@example.test',
                'firstname' => 'Mark',
                'surname' => null,
                'lastname' => 'Manager',
                'role_key' => RoleKey::MANAGER->value,
            ],
            [
                'username' => 'leader.test',
                'email' => 'leader@example.test',
                'firstname' => 'Laura',
                'surname' => null,
                'lastname' => 'Leader',
                'role_key' => RoleKey::GROUP_LEADER->value,
            ],
            [
                'username' => 'driver.test',
                'email' => 'driver@example.test',
                'firstname' => 'David',
                'surname' => null,
                'lastname' => 'Driver',
                'role_key' => RoleKey::DRIVER->value,
            ],
        ];
    }
}
