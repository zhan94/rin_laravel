<?php

declare(strict_types=1);

namespace App\Support\Pricing;

final class BgnCurrency
{
    private const EUR_TO_BGN_RATE = 1.95583;

    public static function normalizeEurAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = str_replace(',', '.', trim((string) $value));
        if (! is_numeric($raw)) {
            return null;
        }

        $amount = (float) $raw;

        return $amount >= 0 ? $amount : null;
    }

    public static function fromEur(float $amount): float
    {
        return round(max(0.0, $amount) * self::EUR_TO_BGN_RATE, 2);
    }

    public static function fromEurValue(mixed $value): ?float
    {
        $amount = self::normalizeEurAmount($value);

        return $amount !== null ? self::fromEur($amount) : null;
    }

    public static function intFromEurValue(mixed $value): ?int
    {
        $amount = self::normalizeEurAmount($value);

        return $amount !== null ? (int) round($amount * self::EUR_TO_BGN_RATE) : null;
    }

    public static function toEur(int|float|null $amount): ?float
    {
        if ($amount === null) {
            return null;
        }

        return round(((float) $amount) / self::EUR_TO_BGN_RATE, 2);
    }

    public static function formatDecimal(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
