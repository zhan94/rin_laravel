<?php

declare(strict_types=1);

namespace App\Support\Localization;

use DateTimeInterface;

final class DisplayDate
{
    public static function german(DateTimeInterface|string|null $value, string $fallback = '—'): string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('d.m.Y');
        }

        if ($value === null || preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T].*)?$/D', trim($value), $parts) !== 1
            || ! checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1])) {
            return $fallback;
        }

        return $parts[3].'.'.$parts[2].'.'.$parts[1];
    }
}
