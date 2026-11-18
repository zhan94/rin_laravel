<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Enums\RoleKey;
use App\Http\Resources\Concerns\FormatsResourceValues;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    use FormatsResourceValues;

    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $this->role;
        $rawRoleKey = $role !== null ? (string) $role->getRawOriginal('role_key') : null;
        $resolvedRoleKey = $rawRoleKey !== null ? RoleKey::tryFrom($rawRoleKey) : null;

        return [
            'id' => (int) $this->id,
            'username' => $this->username,
            'name' => $this->name,
            'firstname' => $this->firstname,
            'surname' => $this->surname,
            'lastname' => $this->lastname,
            'email' => $this->email,
            'role' => $role ? [
                'id' => (int) $role->id,
                'role_key' => $resolvedRoleKey === null ? $rawRoleKey : $resolvedRoleKey->value,
                'title' => $role->displayTitle($request->getLocale()),
                'translations' => $role->displayTranslations(),
            ] : null,
            'role_id' => $this->role_id !== null ? (int) $this->role_id : null,
            'is_active' => (bool) data_get($this->resource, 'is_active', true),
            'created_at' => $this->formatAtom(data_get($this->resource, 'created_at')),
            'updated_at' => $this->formatAtom(data_get($this->resource, 'updated_at')),
        ];
    }
}
