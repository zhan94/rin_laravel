<?php

declare(strict_types=1);

namespace App\Support\Bookings;

use App\Models\SeasonDefault;
use App\Support\Seasons\SeasonCalendar;
use DateTimeImmutable;

final class BookingSeasonRangeResolver
{
    /**
     * @return array{start:string,end:string}
     */
    public function forSelectedDate(?string $selectedDate): array
    {
        $date = $this->parseSelectedDate($selectedDate);

        return SeasonCalendar::rangeForDate($date, $this->seasonDefaults());
    }

    private function parseSelectedDate(?string $selectedDate): DateTimeImmutable
    {
        $candidate = trim((string) $selectedDate);

        if ($candidate === '') {
            return new DateTimeImmutable('today');
        }

        foreach (['!Y-m-d', '!d-m-Y', '!d.m.Y'] as $format) {
            $parsed = DateTimeImmutable::createFromFormat($format, $candidate);
            if ($parsed instanceof DateTimeImmutable) {
                return $parsed;
            }
        }

        return new DateTimeImmutable('today');
    }

    /**
     * @return array{start_month:int,start_day:int,end_month:int,end_day:int}
     */
    private function seasonDefaults(): array
    {
        return SeasonCalendar::normalizeDefaults(SeasonDefault::query()->find(1));
    }
}
