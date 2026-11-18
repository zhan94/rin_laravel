<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\RoleKey;
use App\Models\Role;
use App\Models\User;

final class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->canAccessRole(RoleKey::ADMIN);
    }

    public function create(User $actor): bool
    {
        return $this->viewAny($actor);
    }

    public function view(User $actor, User $target): bool
    {
        return $this->viewAny($actor) && $actor->canAccessRole($target->assignedRoleKey() ?? RoleKey::DRIVER);
    }

    public function update(User $actor, User $target): bool
    {
        return $this->view($actor, $target);
    }

    public function delete(User $actor, User $target): bool
    {
        return $this->update($actor, $target) && ! $actor->is($target);
    }

    public function assignRole(User $actor, Role $role): bool
    {
        $key = RoleKey::tryFrom((string) $role->getRawOriginal('role_key'));

        return $this->create($actor) && $role->is_active && $key !== null && $actor->canAccessRole($key);
    }
}
