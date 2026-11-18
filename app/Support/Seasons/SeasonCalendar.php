<?php

declare(strict_types=1);

namespace App\Support\Seasons;

use App\Models\SeasonDefault;
use DateTimeImmutable;
use DateTimeInterface;

final class SeasonCalendar
{
    public const DEFAULT_START_MONTH = 9;

    public const DEFAULT_START_DAY = 1;

    public const DEFAULT_END_MONTH = 8;

    public const DEFAULT_END_DAY = 31;

    /**
     * @return array{start_month:int,start_day:int,end_month:int,end_day:int}
     */
    public static function defaultDefaults(): array
    {
        return [
            'start_month' => self::DEFAULT_START_MONTH,
            'start_day' => self::DEFAULT_START_DAY,
            'end_month' => self::DEFAULT_END_MONTH,
            'end_day' => self::DEFAULT_END_DAY,
        ];
    }

    /**
     * @return array{start_month:int,start_day:int,end_month:int,end_day:int}
     */
    public static function normalizeDefaults(?SeasonDefault $defaults): array
    {
        if (! $defaults instanceof SeasonDefault) {
            return self::defaultDefaults();
        }

        return self::normalizeDefaultsValues(
            (int) $defaults->start_month,
            (int) $defaults->start_day,
            (int) $defaults->end_month,
            (int) $defaults->end_day,
        );
    }

    /**
     * @return array{start_month:int,start_day:int,end_month:int,end_day:int}
     */
    public static function normalizeDefaultsValues(int $startMonth, int $startDay, int $endMonth, int $endDay): array
    {
        if (! checkdate($startMonth, $startDay, 2024) || ! checkdate($endMonth, $endDay, 2024)) {
            return self::defaultDefaults();
        }

        return [
            'start_month' => $startMonth,
            'start_day' => $startDay,
            'end_month' => $endMonth,
            'end_day' => $endDay,
        ];
    }

    /**
     * @param  array{start_month:int,start_day:int,end_month:int,end_day:int}  $defaults
     * @return array{start:string,end:string}
     */
    public static function rangeForDate(DateTimeInterface $date, array $defaults): array
    {
        $info = self::infoForDate($date, $defaults);

        return [
            'start' => $info['start']->format('Y-m-d'),
            'end' => $info['end']->format('Y-m-d'),
        ];
    }

    /**
     * @param  array{start_month:int,start_day:int,end_month:int,end_day:int}  $defaults
     * @return array{start:DateTimeImmutable,end:DateTimeImmutable,label:string}
     */
    public static function infoForDate(DateTimeInterface $date, array $defaults): array
    {
        $startMonth = $defaults['start_month'];
        $startDay = $defaults['start_day'];
        $endMonth = $defaults['end_month'];
        $endDay = $defaults['end_day'];

        $dateYear = (int) $date->format('Y');
        $dateMd = ((int) $date->format('n') * 100) + (int) $date->format('j');
        $startMd = ($startMonth * 100) + $startDay;
        $endMd = ($endMonth * 100) + $endDay;
        $crossYear = $startMd > $endMd;

        if ($crossYear) {
            $startYear = $dateMd >= $startMd ? $dateYear : $dateYear - 1;
            $endYear = $startYear + 1;
        } else {
            $startYear = $dateMd < $startMd ? $dateYear - 1 : $dateYear;
            $endYear = $startYear;
        }

        return [
            'start' => new DateTimeImmutable(sprintf('%04d-%02d-%02d', $startYear, $startMonth, $startDay)),
            'end' => new DateTimeImmutable(sprintf('%04d-%02d-%02d', $endYear, $endMonth, $endDay)),
            'label' => SeasonLabel::fromYears($startYear, $endYear),
        ];
    }
}
