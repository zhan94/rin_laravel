<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\RoleKey;
use App\Models\Role;
use App\Models\User;
use App\Support\Roles\RoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_index_includes_role_options_and_supports_id_search(): void
    {
        $this->actingAs($this->createUserWithRole(RoleKey::ADMIN));

        $managerRole = Role::query()->firstOrCreate(
            ['role_key' => 'manager'],
            RoleCatalog::payload('manager', 2),
        );

        $adminRole = Role::query()->firstOrCreate(
            ['role_key' => 'admin'],
            RoleCatalog::payload('admin', 1),
        );

        $targetUser = User::factory()->create([
            'role_id' => $managerRole->id,
            'username' => 'target-manager',
            'firstname' => 'Target',
            'lastname' => 'Manager',
            'email' => 'target.manager@example.test',
        ]);

        $otherUser = User::factory()->create([
            'role_id' => $adminRole->id,
            'username' => 'other-admin',
            'firstname' => 'Other',
            'lastname' => 'Admin',
            'email' => 'other.admin@example.test',
        ]);

        $response = $this->getJson(route('api.v1.users.index', [
            'q' => (string) $targetUser->id,
            'per_page' => 100,
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('errors', []);

        /** @var list<array<string,mixed>> $rolesPayload */
        $rolesPayload = (array) $response->json('meta.roles');

        $roles = collect($rolesPayload)
            ->map(static fn (array $row): array => $row)
            ->values();

        $this->assertTrue($roles->contains(fn (array $row): bool => ($row['role_key'] ?? null) === 'admin'));
        $this->assertTrue($roles->contains(fn (array $row): bool => ($row['role_key'] ?? null) === 'manager'));

        $admin = $roles->first(fn (array $row): bool => ($row['role_key'] ?? null) === 'admin');
        $manager = $roles->first(fn (array $row): bool => ($row['role_key'] ?? null) === 'manager');

        $this->assertIsArray($admin);
        $this->assertIsArray($manager);
        $this->assertSame(1, (int) ($admin['sort_order'] ?? 0));
        $this->assertSame(2, (int) ($manager['sort_order'] ?? 0));

        /** @var list<array<string,mixed>> $rows */
        $rows = (array) $response->json('data');

        $ids = collect($rows)
            ->pluck('id')
            ->map(static fn (mixed $value): int => (int) $value)
            ->all();

        $this->assertContains((int) $targetUser->id, $ids);
        $this->assertNotContains((int) $otherUser->id, $ids);
    }

    public function test_user_index_obeys_target_policy_before_pagination_and_search(): void
    {
        $admin = $this->createUserWithRole(RoleKey::ADMIN);
        $super = $this->createUserWithRole(RoleKey::SUPER_ADMIN);
        $manager = $this->createUserWithRole(RoleKey::MANAGER);

        $this->actingAs($admin)->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 2)
            ->assertJsonPath('data.0.id', $manager->id)
            ->assertJsonPath('data.0.can_delete', true)
            ->assertJsonPath('data.1.id', $admin->id)
            ->assertJsonPath('data.1.can_delete', false)
            ->assertJsonMissing(['username' => $super->username]);
        $this->getJson('/api/v1/users?q='.$super->username)->assertOk()->assertJsonPath('meta.pagination.total', 0);

        $this->actingAs($super)->getJson('/api/v1/users')->assertOk()
            ->assertJsonPath('meta.pagination.total', 3)
            ->assertJsonFragment(['username' => $super->username]);
    }

    public function test_user_can_be_created_with_role(): void
    {
        $this->actingAs($this->createUserWithRole(RoleKey::ADMIN));
        $role = Role::factory()->create(['role_key' => 'manager']);

        $payload = [
            'username' => 'crm.manager',
            'firstname' => 'CRM',
            'lastname' => 'Manager',
            'email' => 'crm.manager@example.com',
            'password' => 'StrongPass123!',
            'role_id' => $role->id,
            'is_active' => true,
        ];

        $response = $this->postJson(route('api.v1.users.store'), $payload);

        $response
            ->assertCreated()
            ->assertJsonPath('data.username', 'crm.manager')
            ->assertJsonPath('data.role.id', $role->id)
            ->assertJsonPath('errors', []);

        $saved = User::query()->where('username', 'crm.manager')->firstOrFail();
        $this->assertTrue(Hash::check('StrongPass123!', (string) $saved->password));
    }

    public function test_user_can_be_updated_without_changing_password(): void
    {
        $this->actingAs($this->createUserWithRole(RoleKey::ADMIN));

        $user = User::factory()->create([
            'username' => 'legacy.user',
            'password' => Hash::make('Original123!'),
        ]);

        $previousHash = (string) $user->password;

        $payload = [
            'username' => 'legacy.user',
            'firstname' => 'Updated',
            'surname' => 'Legacy',
            'lastname' => 'User',
            'email' => 'legacy.updated@example.com',
            'is_active' => true,
        ];

        $response = $this->putJson(route('api.v1.users.update', $user->id), $payload);

        $response
            ->assertOk()
            ->assertJsonPath('data.firstname', 'Updated')
            ->assertJsonPath('errors', []);

        $user->refresh();
        $this->assertSame($previousHash, (string) $user->password);
    }

    public function test_manager_cannot_access_users_api_routes(): void
    {
        $manager = $this->createUserWithRole(RoleKey::MANAGER);
        $this->actingAs($manager);

        $response = $this->getJson(route('api.v1.users.index'));

        $response
            ->assertForbidden()
            ->assertJsonPath('errors.0.code', 'forbidden')
            ->assertJsonPath('errors.0.message', 'Access denied.');
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
