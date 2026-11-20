<?php

declare(strict_types=1);

namespace App\Support\Pricing;

final class EurCurrency
{
    public const MAX_BOOKING_AMOUNT = 9999999999.99;

    public static function normalizeAmount(mixed $value): ?float
    {
        if (! is_scalar($value) || is_bool($value)) {
            return null;
        }

        $raw = str_replace(',', '.', trim((string) $value));
        if (! is_numeric($raw)) {
            return null;
        }

        $amount = (float) $raw;

        return is_finite($amount) && $amount >= 0 ? round($amount, 2) : null;
    }

    public static function balance(float $total, float $paid): float
    {
        return max(0.0, round($total - $paid, 2));
    }
}
