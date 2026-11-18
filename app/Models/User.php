<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoleKey;
use App\Support\Names\PersonName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Legacy users table does not have created_at / updated_at columns.
     */
    public $timestamps = false;

    protected $attributes = ['is_active' => true];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'firstname',
        'surname',
        'lastname',
        'role_id',
        'is_active',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function getNameAttribute(): string
    {
        return PersonName::fromParts([
            $this->firstname,
            $this->surname,
            $this->lastname,
        ], (string) $this->username);
    }

    public function displayLabel(): string
    {
        $username = trim((string) $this->username);
        if ($username !== '') {
            return $username;
        }

        $key = $this->getKey();

        return is_scalar($key) && (string) $key !== ''
            ? '#'.(string) $key
            : '';
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    protected static function booted(): void
    {
        static::updating(function (User $user): void {
            if ($user->isDirty(['password', 'role_id', 'is_active'])) {
                $user->setRememberToken(Str::random(60));
            }
        });

        static::updated(function (User $user): void {
            if ($user->wasChanged(['password', 'role_id', 'is_active'])) {
                $user->tokens()->delete();
            }
        });

        static::deleting(fn (User $user) => $user->tokens()->delete());
    }

    public function assignedRoleKey(): ?RoleKey
    {
        $role = $this->loadMissing('role')->role;

        return $role instanceof Role ? RoleKey::tryFrom((string) $role->getRawOriginal('role_key')) : null;
    }

    public function resolvedRoleKey(): ?RoleKey
    {
        $role = $this->loadMissing('role')->role;

        return $role instanceof Role && $role->is_active ? $this->assignedRoleKey() : null;
    }

    public function maySignIn(): bool
    {
        return $this->is_active && $this->resolvedRoleKey() !== null;
    }

    public function authenticationStamp(): string
    {
        return hash_hmac('sha256', implode('|', [
            $this->getAuthIdentifier(), $this->getAuthPassword(), $this->getRememberToken(), $this->role_id,
        ]), (string) config('app.key'));
    }

    public function canAccessRole(RoleKey $requiredRole): bool
    {
        $role = $this->is_active ? $this->resolvedRoleKey() : null;
        if (! $role instanceof RoleKey) {
            return false;
        }

        return $role->canAccess($requiredRole);
    }
}
