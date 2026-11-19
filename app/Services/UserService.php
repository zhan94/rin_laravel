<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Users\UserData;
use App\DTOs\Users\UserFilterData;
use App\Enums\RoleKey;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Support\Auth\StaffAudit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

final readonly class UserService
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private ManagerActionNotificationService $managerActionNotifier,
    ) {}

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(UserFilterData $filters, User $actor): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $actor);
    }

    /**
     * @return list<array{id:int,role_key:string,title:string,translations:array<string,string>,is_active:bool,sort_order:int}>
     */
    public function roleOptions(?User $actor): array
    {
        return array_values(array_filter($this->repository->roleOptions(), static function (array $role) use ($actor): bool {
            $key = RoleKey::tryFrom($role['role_key']);

            return $actor !== null && $role['is_active'] && $key !== null && $actor->canAccessRole($key);
        }));
    }

    public function create(UserData $data, ?User $actor = null): User
    {
        return DB::transaction(function () use ($data, $actor): User {
            if ($actor !== null) {
                $actor = User::query()->lockForUpdate()->findOrFail($actor->id);
                Gate::forUser($actor)->authorize('create', User::class);
                $this->authorizeRole($actor, $data->roleId);
            }

            $created = $this->repository->create($this->withHashedPassword($data));

            if ($actor instanceof User) {
                StaffAudit::committed('staff.account.created', $actor->id, $created->id);
                $label = $created->displayLabel();

                $this->managerActionNotifier->notifyAdminsForManagerEntityAction(
                    actor: $actor,
                    moduleKey: 'users',
                    actionKey: 'created',
                    actionText: 'created',
                    titleEntityLabel: 'User',
                    messageEntityLabel: 'user',
                    label: $label,
                    entityType: 'user',
                    entityId: (int) $created->id,
                    linkPath: '/crm/users',
                );
            }

            return $created;
        }, 3);
    }

    public function update(User $user, UserData $data, ?User $actor = null): User
    {
        return DB::transaction(function () use ($user, $data, $actor): User {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $resolvedData = new UserData(
                username: $data->username,
                firstname: $data->firstname,
                surname: $data->surname,
                lastname: $data->lastname,
                email: $data->email,
                password: $data->password,
                roleId: $data->roleId ?? $user->role_id,
                isActive: $data->isActive,
            );
            if ($actor !== null) {
                $actor = $actor->is($user) ? $user : User::query()->lockForUpdate()->findOrFail($actor->id);
                Gate::forUser($actor)->authorize('update', $user);
                if ($resolvedData->roleId !== $user->role_id) {
                    $this->authorizeRole($actor, $resolvedData->roleId);
                }
                abort_if($actor->is($user) && ($resolvedData->roleId !== $user->role_id || $resolvedData->isActive === false), 403);
            }

            $updated = $this->repository->update($user, $this->withHashedPassword($resolvedData));

            if ($actor instanceof User) {
                StaffAudit::committed('staff.account.updated', $actor->id, $updated->id);
                $label = $updated->displayLabel();

                $this->managerActionNotifier->notifyAdminsForManagerEntityAction(
                    actor: $actor,
                    moduleKey: 'users',
                    actionKey: 'updated',
                    actionText: 'updated',
                    titleEntityLabel: 'User',
                    messageEntityLabel: 'user',
                    label: $label,
                    entityType: 'user',
                    entityId: (int) $updated->id,
                    linkPath: '/crm/users',
                );
            }

            return $updated;
        }, 3);
    }

    public function delete(User $user, ?User $actor = null): void
    {
        DB::transaction(function () use ($user, $actor): void {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            if ($actor !== null) {
                $actor = $actor->is($user) ? $user : User::query()->lockForUpdate()->findOrFail($actor->id);
                Gate::forUser($actor)->authorize('delete', $user);
            }

            $label = $user->displayLabel();

            $this->repository->delete($user);

            if ($actor instanceof User) {
                StaffAudit::committed('staff.account.deleted', $actor->id, $user->id);
                $this->managerActionNotifier->notifyAdminsForManagerEntityAction(
                    actor: $actor,
                    moduleKey: 'users',
                    actionKey: 'deleted',
                    actionText: 'deleted',
                    titleEntityLabel: 'User',
                    messageEntityLabel: 'user',
                    label: $label,
                    entityType: 'user',
                    entityId: (int) $user->id,
                    linkPath: '/crm/users',
                );
            }
        }, 3);
    }

    private function authorizeRole(User $actor, ?int $roleId): void
    {
        $role = Role::query()->lockForUpdate()->find($roleId);
        abort_unless($role instanceof Role, 403);
        Gate::forUser($actor)->authorize('assignRole', [User::class, $role]);
    }

    private function withHashedPassword(UserData $data): UserData
    {
        if ($data->password === null) {
            return $data;
        }

        return new UserData(
            username: $data->username,
            firstname: $data->firstname,
            surname: $data->surname,
            lastname: $data->lastname,
            email: $data->email,
            password: Hash::make($data->password),
            roleId: $data->roleId,
            isActive: $data->isActive,
        );
    }
}
