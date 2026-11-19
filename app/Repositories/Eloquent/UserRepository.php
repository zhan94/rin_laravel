<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\DTOs\Users\UserData;
use App\DTOs\Users\UserFilterData;
use App\Enums\RoleKey;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class UserRepository implements UserRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(UserFilterData $filters, User $actor): LengthAwarePaginator
    {
        $protectedRoles = array_map(
            static fn (RoleKey $role): string => $role->value,
            array_filter(RoleKey::cases(), fn (RoleKey $role): bool => ! $actor->canAccessRole($role)),
        );

        return User::query()
            ->with('role')
            ->when($protectedRoles !== [], fn ($query) => $query->whereDoesntHave('role', fn ($role) => $role->whereIn('role_key', $protectedRoles)))
            ->when($filters->search !== null, function ($query) use ($filters): void {
                $q = '%'.$filters->search.'%';
                $searchId = ctype_digit($filters->search) ? (int) $filters->search : null;

                $query->where(function ($nested) use ($q, $searchId): void {
                    $nested->where('username', 'like', $q)
                        ->orWhere('firstname', 'like', $q)
                        ->orWhere('surname', 'like', $q)
                        ->orWhere('lastname', 'like', $q)
                        ->orWhere('email', 'like', $q);

                    if ($searchId !== null) {
                        $nested->orWhere('id', $searchId);
                    }
                });
            })
            ->orderByDesc('id')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    /**
     * @return list<array{id:int,role_key:string,title:string,translations:array<string,string>,is_active:bool,sort_order:int}>
     */
    public function roleOptions(): array
    {
        $roles = Role::query()
            ->select([
                'id',
                'role_key',
                'title_translations',
                'is_active',
                'sort_order',
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(static function (Role $role): array {
                $rawRoleKey = (string) $role->getRawOriginal('role_key');
                $roleKey = RoleKey::tryFrom($rawRoleKey);

                return [
                    'id' => (int) $role->id,
                    'role_key' => $roleKey === null ? $rawRoleKey : $roleKey->value,
                    'title' => $role->displayTitle(),
                    'translations' => $role->displayTranslations(),
                    'is_active' => (bool) $role->is_active,
                    'sort_order' => (int) $role->sort_order,
                ];
            })
            ->values()
            ->all();

        /** @var list<array{id:int,role_key:string,title:string,translations:array<string,string>,is_active:bool,sort_order:int}> $roles */
        return $roles;
    }

    public function create(UserData $data): User
    {
        return User::query()->create($data->toPersistence());
    }

    public function update(User $user, UserData $data): User
    {
        $user->fill($data->toPersistence(includePassword: false));

        if ($data->password !== null) {
            $user->password = $data->password;
        }

        $user->save();

        return $user;
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
