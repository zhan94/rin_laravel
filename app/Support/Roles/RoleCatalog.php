<?php

declare(strict_types=1);

namespace App\Support\Roles;

use App\Enums\RoleKey;

final class RoleCatalog
{
    public static function title(RoleKey|string $roleKey): string
    {
        $key = $roleKey instanceof RoleKey ? $roleKey->value : $roleKey;

        return match ($key) {
            RoleKey::SUPER_ADMIN->value => 'Super Admin',
            RoleKey::ADMIN->value => 'Admin',
            RoleKey::MANAGER->value => 'Manager',
            RoleKey::GROUP_LEADER->value => 'Group Leader',
            RoleKey::DRIVER->value => 'Driver',
            default => ucwords(str_replace('_', ' ', $key)),
        };
    }

    /**
     * @return array<string,string>
     */
    public static function translations(RoleKey|string $roleKey): array
    {
        $title = self::title($roleKey);

        return [
            'en' => $title,
            'de' => $title,
            'es' => $title,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public static function payload(RoleKey|string $roleKey, int $sortOrder = 0, bool $isActive = true): array
    {
        return [
            'title_translations' => self::translations($roleKey),
            'sort_order' => $sortOrder,
            'is_active' => $isActive,
        ];
    }
}
