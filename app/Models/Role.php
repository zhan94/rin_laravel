<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoleKey;
use App\Models\Concerns\HasLocalizedTitle;
use App\Support\Roles\RoleCatalog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    /** @use HasFactory<\Database\Factories\RoleFactory> */
    use HasFactory;

    use HasLocalizedTitle;

    protected $fillable = [
        'role_key',
        'title_translations',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'title_translations' => 'array',
            'role_key' => RoleKey::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    private function fallbackTitle(): string
    {
        $roleKey = $this->getAttribute('role_key');

        return RoleCatalog::title($roleKey instanceof RoleKey || is_string($roleKey) ? $roleKey : '');
    }
}
