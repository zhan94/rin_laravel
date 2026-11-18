<?php

declare(strict_types=1);

namespace App\Support\Seasons;

use App\Models\Bonus;
use App\Models\SeasonDefault;
use App\Models\Service;
use App\Models\ServiceSlot;
use App\Support\Locations\LocationLabel;
use App\Support\Names\PersonName;
use Carbon\CarbonImmutable;

final class SeasonPresenter
{
    /**
     * @param  iterable<Service>  $services
     * @return list<array<string,mixed>>
     */
    public function services(iterable $services): array
    {
        $items = [];

        foreach ($services as $service) {
            $items[] = [
                'id' => (int) $service->id,
                'title' => $service->displayTitle(),
                'translations' => $service->displayTranslations(),
            ];
        }

        return $items;
    }

    /**
     * @return array<string,mixed>
     */
    public function defaults(SeasonDefault $defaults): array
    {
        $updatedAt = $defaults->getAttribute('updated_at');

        return [
            'start_month' => (int) $defaults->start_month,
            'start_day' => (int) $defaults->start_day,
            'end_month' => (int) $defaults->end_month,
            'end_day' => (int) $defaults->end_day,
            'updated_at' => $updatedAt instanceof \DateTimeInterface
                ? $updatedAt->format(DATE_ATOM)
                : (is_string($updatedAt) && trim($updatedAt) !== '' ? CarbonImmutable::parse($updatedAt)->toAtomString() : null),
        ];
    }

    /**
     * @param  array{bookings_count:int,customers_count:int,sum_total:float,paid_total:float}|null  $stats
     * @return array<string,mixed>
     */
    public function serviceSeason(ServiceSlot $slot, ?array $stats, ?Service $service = null): array
    {
        $dateStartRaw = $this->asDateString($slot->date_start)
            ?? $this->asDateString($slot->getAttribute('dates_min_slot_date'));
        $dateEndRaw = $this->asDateString($slot->date_end)
            ?? $this->asDateString($slot->getAttribute('dates_max_slot_date'));

        $sum = (float) ($stats['sum_total'] ?? 0);
        $paid = (float) ($stats['paid_total'] ?? 0);

        return [
            'id' => (int) $slot->id,
            'label' => $this->buildServiceSeasonLabel($slot, $service, $dateStartRaw, $dateEndRaw),
            'title' => null,
            'date_start' => $dateStartRaw,
            'date_end' => $dateEndRaw,
            'date_start_label' => $this->formatDateLabel($dateStartRaw),
            'date_end_label' => $this->formatDateLabel($dateEndRaw),
            'date_range' => $this->buildDateRangeLabel($dateStartRaw, $dateEndRaw, 'No dates'),
            'dates_count' => (int) ($slot->dates_count ?? 0),
            'bookings_count' => (int) ($stats['bookings_count'] ?? 0),
            'customers_count' => (int) ($stats['customers_count'] ?? 0),
            'sum_total' => $sum,
            'paid_total' => $paid,
            'due_total' => max(0, $sum - $paid),
            'location' => null,
            'is_selected' => false,
        ];
    }

    /**
     * @param  array{bookings_count:int,customers_count:int,sum_total:float,paid_total:float}|null  $stats
     * @return array<string,mixed>
     */
    public function bonusSeason(Bonus $bonus, ?array $stats): array
    {
        $dateFromRaw = $this->asDateString($bonus->date_from);
        $dateToRaw = $this->asDateString($bonus->date_to);

        $sum = (float) ($stats['sum_total'] ?? 0);
        $paid = (float) ($stats['paid_total'] ?? 0);

        return [
            'id' => (int) $bonus->id,
            'label' => SeasonLabel::fromDateRange($dateFromRaw, $dateToRaw),
            'title' => $bonus->displayTitle(),
            'date_start' => $dateFromRaw,
            'date_end' => $dateToRaw,
            'date_start_label' => $this->formatDateLabel($dateFromRaw),
            'date_end_label' => $this->formatDateLabel($dateToRaw),
            'date_range' => $this->buildDateRangeLabel($dateFromRaw, $dateToRaw, 'No period'),
            'dates_count' => null,
            'bookings_count' => (int) ($stats['bookings_count'] ?? 0),
            'customers_count' => (int) ($stats['customers_count'] ?? 0),
            'sum_total' => $sum,
            'paid_total' => $paid,
            'due_total' => max(0, $sum - $paid),
            'location' => LocationLabel::route($bonus->location_from, $bonus->location_to),
            'is_selected' => false,
        ];
    }

    /**
     * @param  list<object>  $rows
     * @return list<array<string,mixed>>
     */
    public function serviceBookings(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $bookingId = (int) data_get($row, 'booking_id', 0);
            if ($bookingId <= 0) {
                continue;
            }

            if (! isset($grouped[$bookingId])) {
                $first = trim((string) data_get($row, 'firstname', ''));
                $last = trim((string) data_get($row, 'lastname', ''));
                $sum = (float) data_get($row, 'sum_paid', 0);
                $paid = (float) data_get($row, 'paid', 0);

                $grouped[$bookingId] = [
                    'booking_id' => $bookingId,
                    'customer_id' => (int) data_get($row, 'customer_id', 0),
                    'name' => PersonName::fromParts([$first, $last], '—'),
                    'phone' => trim((string) data_get($row, 'parent_phone', '')),
                    'location' => LocationLabel::routeWithTimes(
                        (string) data_get($row, 'location_from', ''),
                        (string) data_get($row, 'location_to', ''),
                        data_get($row, 'from_time'),
                        data_get($row, 'to_time'),
                    ),
                    'sum_paid' => $sum,
                    'paid' => $paid,
                    'due' => max(0, $sum - $paid),
                    'dates' => [],
                ];
            }

            $slotDate = $this->asDateString(data_get($row, 'slot_date'));
            if ($slotDate !== null) {
                $grouped[$bookingId]['dates'][$slotDate] = true;
            }
        }

        foreach ($grouped as &$item) {
            $dates = array_keys($item['dates']);
            sort($dates);
            $item['dates'] = $dates;
        }
        unset($item);

        return array_values($grouped);
    }

    /**
     * @param  list<object>  $rows
     * @return list<array<string,mixed>>
     */
    public function bonusBookings(array $rows): array
    {
        $result = [];

        foreach ($rows as $row) {
            $first = trim((string) data_get($row, 'firstname', ''));
            $last = trim((string) data_get($row, 'lastname', ''));
            $sum = (float) data_get($row, 'sum_paid', 0);
            $paid = (float) data_get($row, 'paid', 0);

            $result[] = [
                'booking_id' => (int) data_get($row, 'booking_id', 0),
                'customer_id' => (int) data_get($row, 'customer_id', 0),
                'name' => PersonName::fromParts([$first, $last], '—'),
                'phone' => trim((string) data_get($row, 'parent_phone', '')),
                'location' => LocationLabel::routeWithTimes(
                    (string) data_get($row, 'location_from', ''),
                    (string) data_get($row, 'location_to', ''),
                    data_get($row, 'from_time'),
                    data_get($row, 'to_time'),
                ),
                'sum_paid' => $sum,
                'paid' => $paid,
                'due' => max(0, $sum - $paid),
                'dates' => [],
            ];
        }

        return $result;
    }

    public function dateRangeLabel(?string $startDate, ?string $endDate, string $emptyLabel): string
    {
        return $this->buildDateRangeLabel($startDate, $endDate, $emptyLabel);
    }

    public function dateString(mixed $value): ?string
    {
        return $this->asDateString($value);
    }

    public function dateLabel(?string $date): string
    {
        return $this->formatDateLabel($date);
    }

    private function buildServiceSeasonLabel(
        ServiceSlot $slot,
        ?Service $service,
        ?string $startDate,
        ?string $endDate,
    ): string {
        $name = trim((string) $slot->name);

        if ($name !== '' && ! $this->containsCyrillic($name)) {
            return $name;
        }

        $range = SeasonLabel::fromDateRange($startDate, $endDate);
        $serviceTitle = $service instanceof Service ? $service->displayTitle() : '';
        $serviceTitle = trim($serviceTitle);

        if ($serviceTitle !== '' && $range !== 'Season') {
            return sprintf('%s %s', $serviceTitle, $range);
        }

        if ($serviceTitle !== '') {
            return $serviceTitle;
        }

        if ($range !== 'Season') {
            return $range;
        }

        return 'Season #'.(int) $slot->id;
    }

    private function containsCyrillic(string $value): bool
    {
        return preg_match('/\p{Cyrillic}/u', $value) === 1;
    }

    private function formatDateLabel(?string $date): string
    {
        if ($date === null || $date === '') {
            return '—';
        }

        return CarbonImmutable::parse($date)->format('d-m-Y');
    }

    private function buildDateRangeLabel(?string $startDate, ?string $endDate, string $emptyLabel): string
    {
        if (($startDate ?? '') === '' && ($endDate ?? '') === '') {
            return $emptyLabel;
        }

        return sprintf('%s - %s', $this->formatDateLabel($startDate), $this->formatDateLabel($endDate));
    }

    private function asDateString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);

            return $trimmed !== '' ? CarbonImmutable::parse($trimmed)->toDateString() : null;
        }

        if ($value instanceof CarbonImmutable) {
            return $value->toDateString();
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return null;
    }
}
