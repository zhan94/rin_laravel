<?php

declare(strict_types=1);

namespace Tests\Feature\Crm;

use App\Enums\RoleKey;
use App\Models\Role;
use App\Models\User;
use App\Support\Roles\RoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_users_and_logs_pages(): void
    {
        $admin = $this->createUserWithRole(RoleKey::ADMIN);

        $this->actingAs($admin)
            ->get('/crm/users')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/crm/logs')
            ->assertOk();
    }

    public function test_manager_cannot_open_users_or_logs_pages(): void
    {
        $manager = $this->createUserWithRole(RoleKey::MANAGER);

        $this->actingAs($manager)
            ->get('/crm/users')
            ->assertForbidden();

        $this->actingAs($manager)
            ->get('/crm/logs')
            ->assertForbidden();
    }

    public function test_group_leader_and_manager_can_open_issues_page(): void
    {
        $groupLeader = $this->createUserWithRole(RoleKey::GROUP_LEADER);
        $manager = $this->createUserWithRole(RoleKey::MANAGER);

        $this->actingAs($groupLeader)
            ->get('/crm/issues')
            ->assertOk();

        $this->actingAs($manager)
            ->get('/crm/issues')
            ->assertOk();
    }

    public function test_driver_cannot_open_issues_page(): void
    {
        $driver = $this->createUserWithRole(RoleKey::DRIVER);

        $this->actingAs($driver)
            ->get('/crm/issues')
            ->assertForbidden();
    }

    private function createUserWithRole(RoleKey $roleKey): User
    {
        $role = Role::query()->firstOrCreate(
            ['role_key' => $roleKey->value],
            RoleCatalog::payload($roleKey, 1),
        );

        return User::factory()->create([
            'role_id' => $role->id,
        ]);
    }
}
