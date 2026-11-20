<?php

declare(strict_types=1);

namespace App\Support\Customers;

use App\Models\SeasonDefault;
use App\Support\Seasons\SeasonCalendar;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

/**
 * @phpstan-type UnpaidRow array{
 *     customer_id:int,
 *     customer_name:string,
 *     date_birth:?string,
 *     booking_id:int,
 *     service_id:?int,
 *     service_type:?int,
 *     service_title:string,
 *     sum_paid:float,
 *     paid:float,
 *     due:float
 * }
 * @phpstan-type UnpaidSeasonRow array{
 *     customer_id:int,
 *     customer_name:string,
 *     date_birth:?string,
 *     booking_id:int,
 *     service_id:?int,
 *     service_type:?int,
 *     service_title:string,
 *     sum_paid:float,
 *     paid:float,
 *     due:float,
 *     season_label:string
 * }
 * @phpstan-type BookingDateRow array{
 *     slot_date:?string,
 *     season_name:?string,
 *     season_start:?string,
 *     season_end:?string
 * }
 * @phpstan-type SeasonDefaults array{start_month:int,start_day:int,end_month:int,end_day:int}
 * @phpstan-type SeasonInfo array{start:DateTimeImmutable,end:DateTimeImmutable,label:string}
 * @phpstan-type UnpaidTab array{id:string,label:string,is_current:bool,count:int,due_total:float}
 */
final class UnpaidOverviewBuilder
{
    /**
     * @param  list<UnpaidRow>  $rows
     * @return array{
     *     rows:list<UnpaidSeasonRow>,
     *     records:int,
     *     total_due:float,
     *     current_unpaid_season_label:string,
     *     unpaid_tabs:list<UnpaidTab>
     * }
     */
    public function build(array $rows): array
    {
        $defaults = $this->seasonDefaults();
        $currentSeason = $this->seasonInfoForDate(new DateTimeImmutable('today'), $defaults);
        $currentLabel = trim($currentSeason['label']);
        if ($currentLabel === '') {
            $currentLabel = '—';
        }

        if ($rows === []) {
            return [
                'rows' => [],
                'records' => 0,
                'total_due' => 0,
                'current_unpaid_season_label' => $currentLabel,
                'unpaid_tabs' => [$this->buildUnpaidTab(0, $currentLabel, [], true)],
            ];
        }

        /** @var array<string,list<UnpaidSeasonRow>> $rowsBySeason */
        $rowsBySeason = [
            $currentLabel => [],
        ];

        /** @var list<UnpaidSeasonRow> $rowsWithSeason */
        $rowsWithSeason = [];
        $totalDue = 0;

        foreach (array_chunk($rows, 1000) as $batch) {
            $bookingIds = array_values(array_unique(array_column($batch, 'booking_id')));
            $dateRowsByBooking = $this->bookingDateRowsByBookingIds($bookingIds);

            foreach ($batch as $row) {
                $bookingId = $row['booking_id'];
                $seasonLabel = $this->resolveUnpaidSeasonLabelForBooking(
                    $bookingId,
                    $dateRowsByBooking[$bookingId] ?? [],
                    $defaults,
                    $currentSeason,
                    $currentLabel,
                );

                $rowWithSeason = $row + ['season_label' => $seasonLabel];
                $rowsWithSeason[] = $rowWithSeason;
                $rowsBySeason[$seasonLabel][] = $rowWithSeason;
                $totalDue += max(0, $row['due']);
            }
            unset($dateRowsByBooking);
        }

        return [
            'rows' => $rowsWithSeason,
            'records' => count($rowsWithSeason),
            'total_due' => round($totalDue, 2),
            'current_unpaid_season_label' => $currentLabel,
            'unpaid_tabs' => $this->buildUnpaidTabs($rowsBySeason, $currentLabel),
        ];
    }

    /**
     * @return SeasonDefaults
     */
    private function seasonDefaults(): array
    {
        return SeasonCalendar::normalizeDefaults(SeasonDefault::query()->find(1));
    }

    /**
     * @param  SeasonDefaults  $defaults
     * @return SeasonInfo
     */
    private function seasonInfoForDate(DateTimeImmutable $date, array $defaults): array
    {
        return SeasonCalendar::infoForDate($date, $defaults);
    }

    /**
     * @param  list<int>  $bookingIds
     * @return array<int,list<BookingDateRow>>
     */
    private function bookingDateRowsByBookingIds(array $bookingIds): array
    {
        if ($bookingIds === []) {
            return [];
        }

        $rows = DB::table('booking_dates as bd')
            ->join('service_dates as sd', 'sd.id', '=', 'bd.service_date_id')
            ->leftJoin('service_slots as ss', 'ss.id', '=', 'sd.slot_id')
            ->whereIntegerInRaw('bd.booking_id', $bookingIds)
            ->orderBy('bd.booking_id')
            ->orderBy('sd.slot_date')
            ->get([
                'bd.booking_id',
                'sd.slot_date',
                'ss.name as season_name',
                'ss.date_start as season_start',
                'ss.date_end as season_end',
            ]);

        /** @var array<int,list<BookingDateRow>> $grouped */
        $grouped = [];

        foreach ($rows as $row) {
            $bookingId = (int) ($row->booking_id ?? 0);
            if ($bookingId <= 0) {
                continue;
            }

            if (! isset($grouped[$bookingId])) {
                $grouped[$bookingId] = [];
            }

            $grouped[$bookingId][] = [
                'slot_date' => $row->slot_date !== null ? (string) $row->slot_date : null,
                'season_name' => $row->season_name !== null ? (string) $row->season_name : null,
                'season_start' => $row->season_start !== null ? (string) $row->season_start : null,
                'season_end' => $row->season_end !== null ? (string) $row->season_end : null,
            ];
        }

        return $grouped;
    }

    /**
     * @param  list<BookingDateRow>  $dateRows
     * @param  SeasonDefaults  $defaults
     * @param  SeasonInfo  $currentSeason
     */
    private function resolveUnpaidSeasonLabelForBooking(
        int $bookingId,
        array $dateRows,
        array $defaults,
        array $currentSeason,
        string $currentLabel,
    ): string {
        if ($bookingId <= 0 || $dateRows === []) {
            return $currentLabel;
        }

        $fallbackLabel = '';
        $latestDate = null;
        $latestLabel = '';

        foreach ($dateRows as $dateRow) {
            $rawDate = trim((string) $dateRow['slot_date']);
            $parsedDate = $this->parseSlotDate($rawDate);
            $seasonLabel = trim($this->resolveSeasonLabelForDateRow($dateRow, $parsedDate, $defaults));
            if ($seasonLabel === '') {
                $seasonLabel = '—';
            }

            if ($fallbackLabel === '') {
                $fallbackLabel = $seasonLabel;
            }

            if ($parsedDate !== null && $parsedDate >= $currentSeason['start'] && $parsedDate <= $currentSeason['end']) {
                return $currentLabel;
            }

            if ($parsedDate !== null) {
                if ($latestDate === null || $parsedDate > $latestDate) {
                    $latestDate = $parsedDate;
                    $latestLabel = $seasonLabel;
                }
            } elseif ($latestLabel === '') {
                $latestLabel = $seasonLabel;
            }
        }

        if ($latestLabel !== '') {
            return $latestLabel;
        }

        return $fallbackLabel;
    }

    private function parseSlotDate(string $rawDate): ?DateTimeImmutable
    {
        $rawDate = trim($rawDate);
        if ($rawDate === '') {
            return null;
        }

        foreach (['!Y-m-d', '!Y-m-d H:i:s', '!Y-m-d H:i'] as $format) {
            $parsed = DateTimeImmutable::createFromFormat($format, $rawDate);
            if ($parsed instanceof DateTimeImmutable) {
                return $parsed;
            }
        }

        $timestamp = strtotime($rawDate);
        if ($timestamp === false) {
            return null;
        }

        $normalized = date('Y-m-d', $timestamp);
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $normalized);

        return $parsed instanceof DateTimeImmutable ? $parsed : null;
    }

    /**
     * @param  BookingDateRow  $dateRow
     * @param  SeasonDefaults  $defaults
     */
    private function resolveSeasonLabelForDateRow(array $dateRow, ?DateTimeImmutable $parsedDate, array $defaults): string
    {
        $slotName = trim((string) $dateRow['season_name']);
        if ($slotName !== '') {
            return $slotName;
        }

        if ($parsedDate !== null) {
            return $this->seasonInfoForDate($parsedDate, $defaults)['label'];
        }

        $start = trim((string) $dateRow['season_start']);
        $end = trim((string) $dateRow['season_end']);
        if ($start !== '' && $end !== '') {
            return $start.' - '.$end;
        }

        return '—';
    }

    /**
     * @param  array<string,list<UnpaidSeasonRow>>  $rowsBySeason
     * @return list<UnpaidTab>
     */
    private function buildUnpaidTabs(array $rowsBySeason, string $currentLabel): array
    {
        $currentRows = $rowsBySeason[$currentLabel] ?? [];
        unset($rowsBySeason[$currentLabel]);

        if ($rowsBySeason !== []) {
            uksort($rowsBySeason, function (string $labelA, string $labelB): int {
                $startYearA = $this->extractSeasonStartYear($labelA);
                $startYearB = $this->extractSeasonStartYear($labelB);
                if ($startYearA !== null && $startYearB !== null) {
                    return $startYearB <=> $startYearA;
                }
                if ($startYearA !== null) {
                    return -1;
                }
                if ($startYearB !== null) {
                    return 1;
                }

                return strcmp($labelA, $labelB);
            });
        }

        $tabs = [];
        $tabs[] = $this->buildUnpaidTab(0, $currentLabel, $currentRows, true);
        $index = 1;
        foreach ($rowsBySeason as $seasonLabel => $seasonRows) {
            $tabs[] = $this->buildUnpaidTab($index, $seasonLabel, $seasonRows, false);
            $index++;
        }

        return $tabs;
    }

    /**
     * @param  list<UnpaidSeasonRow>  $rows
     * @return UnpaidTab
     */
    private function buildUnpaidTab(int $index, string $seasonLabel, array $rows, bool $isCurrent): array
    {
        $dueTotal = 0;
        foreach ($rows as $row) {
            $dueTotal += max(0, $row['due']);
        }

        return [
            'id' => $this->buildUnpaidSeasonTabId($seasonLabel, $index),
            'label' => $seasonLabel,
            'is_current' => $isCurrent,
            'count' => count($rows),
            'due_total' => round($dueTotal, 2),
        ];
    }

    private function buildUnpaidSeasonTabId(string $seasonLabel, int $index): string
    {
        $slug = function_exists('mb_strtolower')
            ? mb_strtolower($seasonLabel, 'UTF-8')
            : strtolower($seasonLabel);
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $slug) ?? '';
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'season';
        }

        return 'unpaid-season-'.$index.'-'.$slug;
    }

    private function extractSeasonStartYear(string $seasonLabel): ?int
    {
        if (preg_match('/^\s*(\d{4})\s*$/', $seasonLabel, $singleYearMatches) === 1) {
            return (int) $singleYearMatches[1];
        }

        if (preg_match('/^\s*(\d{4})\s*\/\s*(\d{4})\s*$/', $seasonLabel, $matches) !== 1) {
            return null;
        }

        $startYear = (int) $matches[1];
        $endYear = (int) $matches[2];
        if ($endYear < $startYear) {
            return null;
        }

        return $startYear;
    }
}
