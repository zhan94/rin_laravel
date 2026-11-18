<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use App\Models\User;
use Illuminate\Validation\Rule;

final class UpdateUserRequest extends StoreUserRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && ($this->user()?->can('update', $user) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'username' => ['required', 'string', 'max:100', Rule::unique('users', 'username')->ignore($user)],
            'firstname' => ['required', 'string', 'max:50'],
            'surname' => ['nullable', 'string', 'max:50'],
            'lastname' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'role_id' => ['nullable', 'integer', Rule::exists('roles', 'id')],
        ];
    }
}
