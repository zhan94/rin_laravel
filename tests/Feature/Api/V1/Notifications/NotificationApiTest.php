<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Notifications;

use App\Enums\RoleKey;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use App\Support\Roles\RoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_receives_standard_unauthenticated_envelope(): void
    {
        $response = $this->getJson('/api/v1/notifications');

        $response
            ->assertUnauthorized()
            ->assertJsonPath('errors.0.code', 'unauthenticated')
            ->assertJsonPath('errors.0.message', 'Unauthenticated.');
    }

    public function test_web_logged_in_user_can_access_notifications_api_with_session_cookie(): void
    {
        $user = $this->createUserWithRole(RoleKey::MANAGER);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');

        $response = $this->getJson('/api/v1/notifications');

        $response
            ->assertOk()
            ->assertJsonPath('errors', []);
    }

    public function test_user_can_list_notifications_with_unread_count(): void
    {
        $user = $this->createUserWithRole(RoleKey::MANAGER);
        $actor = $this->createUserWithRole(RoleKey::ADMIN);
        $other = $this->createUserWithRole(RoleKey::DRIVER);

        $unread = UserNotification::query()->create([
            'user_id' => $user->id,
            'actor_user_id' => $actor->id,
            'notification_type' => 'manager_action',
            'title' => 'Created customer',
            'message' => 'A customer was created.',
            'status' => 'unread',
        ]);

        UserNotification::query()->create([
            'user_id' => $user->id,
            'actor_user_id' => $actor->id,
            'notification_type' => 'manager_action',
            'title' => 'Updated customer',
            'message' => 'A customer was updated.',
            'status' => 'read',
            'read_at' => now(),
        ]);

        UserNotification::query()->create([
            'user_id' => $other->id,
            'actor_user_id' => $actor->id,
            'notification_type' => 'manager_action',
            'title' => 'Other user notification',
            'message' => 'Should not be visible.',
            'status' => 'unread',
        ]);

        $this->actingAs($user);

        $response = $this->getJson('/api/v1/notifications?status=unread');

        $response
            ->assertOk()
            ->assertJsonPath('meta.unread_count', 1)
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.0.id', $unread->id)
            ->assertJsonPath('data.0.status', 'unread')
            ->assertJsonPath('errors', []);

        $all = $this->getJson('/api/v1/notifications');

        $all
            ->assertOk()
            ->assertJsonPath('meta.unread_count', 1)
            ->assertJsonPath('meta.pagination.total', 2)
            ->assertJsonPath('errors', []);
    }

    public function test_user_can_mark_single_notification_as_read(): void
    {
        $user = $this->createUserWithRole(RoleKey::MANAGER);

        $notification = UserNotification::query()->create([
            'user_id' => $user->id,
            'notification_type' => 'manager_action',
            'title' => 'Pending',
            'message' => 'Pending read.',
            'status' => 'unread',
        ]);

        $this->actingAs($user);

        $response = $this->postJson("/api/v1/notifications/{$notification->id}/read");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $notification->id)
            ->assertJsonPath('data.status', 'read')
            ->assertJsonPath('errors', []);

        $this->assertDatabaseHas('user_notifications', [
            'id' => $notification->id,
            'status' => 'read',
        ]);
    }

    public function test_user_cannot_mark_other_users_notification(): void
    {
        $user = $this->createUserWithRole(RoleKey::MANAGER);
        $other = $this->createUserWithRole(RoleKey::ADMIN);

        $notification = UserNotification::query()->create([
            'user_id' => $other->id,
            'notification_type' => 'manager_action',
            'title' => 'Foreign',
            'message' => 'Foreign notification.',
            'status' => 'unread',
        ]);

        $this->actingAs($user);

        $response = $this->postJson("/api/v1/notifications/{$notification->id}/read");

        $response
            ->assertNotFound()
            ->assertJsonPath('errors.0.code', 'notification_not_found');
    }

    public function test_user_can_mark_all_visible_notifications_as_read(): void
    {
        $user = $this->createUserWithRole(RoleKey::MANAGER);
        $otherUser = $this->createUserWithRole(RoleKey::DRIVER);

        UserNotification::query()->create([
            'user_id' => $user->id,
            'notification_type' => 'manager_action',
            'title' => 'A',
            'message' => 'A message',
            'status' => 'unread',
        ]);

        UserNotification::query()->create([
            'user_id' => $user->id,
            'notification_type' => 'manager_action',
            'title' => 'B',
            'message' => 'B message',
            'status' => 'unread',
        ]);

        UserNotification::query()->create([
            'user_id' => $user->id,
            'notification_type' => 'manager_action',
            'title' => 'C',
            'message' => 'C message',
            'status' => 'read',
            'read_at' => now(),
        ]);

        $deleted = UserNotification::factory()->for($user)->create([
            'status' => 'unread',
            'deleted_at' => now(),
        ]);
        $foreign = UserNotification::factory()->for($otherUser)->create([
            'status' => 'unread',
        ]);

        $this->actingAs($user);

        $this->getJson('/api/v1/notifications?status=unread')
            ->assertOk()
            ->assertJsonPath('meta.unread_count', 2)
            ->assertJsonPath('meta.pagination.total', 2);

        $this->postJson("/api/v1/notifications/{$deleted->id}/read")
            ->assertNotFound()
            ->assertJsonPath('errors.0.code', 'notification_not_found');

        $response = $this->postJson('/api/v1/notifications/read-all');

        $response
            ->assertOk()
            ->assertJsonPath('data.updated', 2)
            ->assertJsonPath('errors', []);

        $remainingUnread = UserNotification::query()
            ->where('user_id', $user->id)
            ->where('status', 'unread')
            ->whereNull('deleted_at')
            ->count();

        $this->assertSame(0, $remainingUnread);
        $this->assertSame('unread', $deleted->refresh()->status);
        $this->assertSame('unread', $foreign->refresh()->status);
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
