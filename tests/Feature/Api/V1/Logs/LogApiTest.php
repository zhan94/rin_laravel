<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Logs;

use App\Enums\RoleKey;
use App\Models\Log;
use App\Models\Role;
use App\Models\User;
use App\Support\Roles\RoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_logs_but_own_entries_are_hidden(): void
    {
        $admin = $this->createUserWithRole(RoleKey::ADMIN);
        $otherUser = $this->createUserWithRole(RoleKey::MANAGER);

        $ownLog = Log::query()->create([
            'user' => $admin->id,
            'page' => '/logs',
            'ip' => '127.0.0.1',
            'location' => 'Admin place',
        ]);

        $visibleLog = Log::query()->create([
            'user' => $otherUser->id,
            'page' => '/customers',
            'ip' => '127.0.0.2',
            'location' => 'Client place',
        ]);

        $this->actingAs($admin);

        $response = $this->getJson('/api/v1/logs');

        $response
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.0.id', $visibleLog->id)
            ->assertJsonMissing(['id' => $ownLog->id])
            ->assertJsonPath('errors', []);
    }

    public function test_super_admin_can_clear_all_logs(): void
    {
        $superAdmin = $this->createUserWithRole(RoleKey::SUPER_ADMIN);
        $user = $this->createUserWithRole(RoleKey::MANAGER);

        Log::query()->create([
            'user' => $superAdmin->id,
            'page' => '/logs',
            'ip' => '127.0.0.3',
            'location' => 'HQ',
        ]);

        Log::query()->create([
            'user' => $user->id,
            'page' => '/reports',
            'ip' => '127.0.0.4',
            'location' => 'Branch',
        ]);

        $this->actingAs($superAdmin);

        $response = $this->deleteJson('/api/v1/logs');

        $response
            ->assertOk()
            ->assertJsonPath('data.deleted', 2)
            ->assertJsonPath('errors', []);

        $this->assertDatabaseCount('logs', 0);
    }

    public function test_manager_cannot_access_logs_module(): void
    {
        $manager = $this->createUserWithRole(RoleKey::MANAGER);

        $this->actingAs($manager);

        $response = $this->getJson('/api/v1/logs');

        $response
            ->assertForbidden()
            ->assertJsonPath('errors.0.code', 'logs_forbidden');
    }

    public function test_admin_can_delete_selected_visible_logs_only(): void
    {
        $admin = $this->createUserWithRole(RoleKey::ADMIN);
        $manager = $this->createUserWithRole(RoleKey::MANAGER);

        $ownLog = Log::query()->create([
            'user' => $admin->id,
            'page' => '/own',
            'ip' => '127.0.0.1',
            'location' => null,
        ]);

        $logA = Log::query()->create([
            'user' => $manager->id,
            'page' => '/a',
            'ip' => '127.0.0.2',
            'location' => null,
        ]);

        $logB = Log::query()->create([
            'user' => $manager->id,
            'page' => '/b',
            'ip' => '127.0.0.3',
            'location' => null,
        ]);

        $this->actingAs($admin);

        $response = $this->postJson('/api/v1/logs/delete-selected', [
            'ids' => [$ownLog->id, $logA->id, $logB->id],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.deleted', 2)
            ->assertJsonPath('errors', []);

        $this->assertDatabaseHas('logs', ['id' => $ownLog->id]);
        $this->assertDatabaseMissing('logs', ['id' => $logA->id]);
        $this->assertDatabaseMissing('logs', ['id' => $logB->id]);
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
