<?php

declare(strict_types=1);

namespace App\Support\Seasons;

final class SeasonLabel
{
    public static function fromYears(int $startYear, int $endYear): string
    {
        return $startYear === $endYear
            ? (string) $startYear
            : sprintf('%d/%d', $startYear, $endYear);
    }

    public static function fromDateRange(?string $startDate, ?string $endDate, string $emptyLabel = 'Season'): string
    {
        $start = trim((string) $startDate);
        $end = trim((string) $endDate);

        if ($start === '' || $end === '') {
            return $emptyLabel;
        }

        return self::fromYears(
            (int) substr($start, 0, 4),
            (int) substr($end, 0, 4),
        );
    }

    public static function shortFromYears(int $startYear, int $endYear): string
    {
        return sprintf('%02d/%02d', $startYear % 100, $endYear % 100);
    }

    public static function normalize(string $value): string
    {
        $value = preg_replace('/\s+/', '', $value) ?? '';

        return strtolower(trim($value));
    }
}
