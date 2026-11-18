<?php

declare(strict_types=1);

use App\Enums\RoleKey;
use App\Models\Role;
use App\Models\User;
use App\Services\ManagerActionNotificationService;
use App\Support\Roles\RoleCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('manager entity notification helper builds the standard notification payload', function () {
    $service = app(ManagerActionNotificationService::class);

    $manager = makeUserWithRole(RoleKey::MANAGER, 'manager.mira');
    $admin = makeUserWithRole(RoleKey::ADMIN, 'admin.ana');
    $superAdmin = makeUserWithRole(RoleKey::SUPER_ADMIN, 'super.bob');

    $service->notifyAdminsForManagerEntityAction(
        actor: $manager,
        moduleKey: 'customers',
        actionKey: 'created',
        actionText: 'created',
        titleEntityLabel: 'Customer',
        messageEntityLabel: 'customer',
        label: 'Mira Petrova',
        entityType: 'customer',
        entityId: 15,
        linkPath: '/crm/customers',
    );

    foreach ([$admin, $superAdmin] as $recipient) {
        expect(DB::table('user_notifications')->where([
            'user_id' => $recipient->id,
            'actor_user_id' => $manager->id,
            'notification_type' => 'manager.customers.created',
            'module_key' => 'customers',
            'action_key' => 'created',
            'entity_type' => 'customer',
            'entity_id' => 15,
            'title' => 'Customer created: Mira Petrova',
            'message' => 'manager.mira created customer Mira Petrova.',
            'link_path' => '/crm/customers',
        ])->exists())->toBeTrue();
    }
});

test('manager entity notification helper ignores non manager actors', function () {
    $service = app(ManagerActionNotificationService::class);

    $adminActor = makeUserWithRole(RoleKey::ADMIN, 'admin.actor');
    makeUserWithRole(RoleKey::SUPER_ADMIN, 'super.recipient');

    $service->notifyAdminsForManagerEntityAction(
        actor: $adminActor,
        moduleKey: 'users',
        actionKey: 'deleted',
        actionText: 'deleted',
        titleEntityLabel: 'User',
        messageEntityLabel: 'user',
        label: 'legacy-user',
        entityType: 'user',
        entityId: 22,
        linkPath: '/crm/users',
    );

    expect(DB::table('user_notifications')->count())->toBe(0);
});

function makeUserWithRole(RoleKey $roleKey, string $username): User
{
    $role = Role::query()->firstOrCreate(
        ['role_key' => $roleKey->value],
        RoleCatalog::payload($roleKey, 1),
    );

    return User::factory()->create([
        'role_id' => $role->id,
        'username' => $username,
    ]);
}
