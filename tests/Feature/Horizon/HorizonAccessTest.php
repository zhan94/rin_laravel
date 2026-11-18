<?php

declare(strict_types=1);

namespace Tests\Feature\Horizon;

use App\Enums\RoleKey;
use App\Models\Role;
use App\Models\User;
use App\Support\Roles\RoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HorizonAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_can_view_horizon_and_receive_sidebar_permission(): void
    {
        $admin = $this->createUserWithRole(RoleKey::ADMIN);
        $superAdmin = $this->createUserWithRole(RoleKey::SUPER_ADMIN);

        $this->assertTrue(Gate::forUser($admin)->allows('viewHorizon'));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('viewHorizon'));

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.permissions.horizon', true)
            );
    }

    public function test_non_admin_roles_do_not_receive_horizon_access(): void
    {
        $manager = $this->createUserWithRole(RoleKey::MANAGER);
        $driver = $this->createUserWithRole(RoleKey::DRIVER);

        $this->assertFalse(Gate::forUser($manager)->allows('viewHorizon'));
        $this->assertFalse(Gate::forUser($driver)->allows('viewHorizon'));

        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.permissions.horizon', false)
            );
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

    public function test_local_horizon_still_requires_an_administrator(): void
    {
        $this->app->instance('env', 'local');
        $path = 'http://localhost/'.config('horizon.path');
        $this->get($path)->assertRedirect(route('login'));

        $role = Role::factory()->create(['role_key' => RoleKey::MANAGER]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
        $this->get($path)->assertForbidden();
    }
}
