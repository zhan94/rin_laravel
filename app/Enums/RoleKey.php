<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleKey: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case GROUP_LEADER = 'group_leader';
    case DRIVER = 'driver';

    public function canAccess(self $requiredRole): bool
    {
        if ($this === self::SUPER_ADMIN) {
            return true;
        }

        if ($requiredRole === self::SUPER_ADMIN) {
            return false;
        }

        return $this->permissionRank() >= $requiredRole->permissionRank();
    }

    private function permissionRank(): int
    {
        return match ($this) {
            self::SUPER_ADMIN => 50,
            self::ADMIN => 40,
            self::MANAGER => 30,
            self::GROUP_LEADER => 20,
            self::DRIVER => 10,
        };
    }
}
