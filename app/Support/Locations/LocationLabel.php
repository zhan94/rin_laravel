<?php

declare(strict_types=1);

namespace App\Support\Locations;

final class LocationLabel
{
    public static function route(
        mixed $locationFrom,
        mixed $locationTo,
        string $separator = ' - ',
        string $fallback = '',
    ): string {
        $from = trim((string) $locationFrom);
        $to = trim((string) $locationTo);

        if ($from !== '' && $to !== '') {
            return $from.$separator.$to;
        }

        if ($from !== '') {
            return $from;
        }

        if ($to !== '') {
            return $to;
        }

        return trim($fallback);
    }

    public static function routeWithTimes(
        mixed $locationFrom,
        mixed $locationTo,
        mixed $fromTime,
        mixed $toTime,
        string $separator = ' - ',
        string $fallback = '',
    ): string {
        $label = self::route($locationFrom, $locationTo, $separator, $fallback);
        $timeWindow = self::timeWindow($fromTime, $toTime);

        if ($timeWindow === '') {
            return $label;
        }

        return trim($label.' ('.$timeWindow.')');
    }

    public static function timeWindow(mixed $fromTime, mixed $toTime): string
    {
        $from = trim((string) ($fromTime ?? ''));
        $to = trim((string) ($toTime ?? ''));

        if ($from !== '' && $to !== '') {
            return $from.' - '.$to;
        }

        if ($from !== '') {
            return $from;
        }

        return $to;
    }
}
