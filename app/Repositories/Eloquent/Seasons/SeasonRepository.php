<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Seasons;

use App\DTOs\Seasons\SeasonDefaultsData;
use App\Models\Bonus;
use App\Models\SeasonDefault;
use App\Models\Service;
use App\Models\ServiceSlot;
use App\Repositories\Contracts\Seasons\SeasonRepositoryInterface;
use App\Support\Seasons\SeasonCalendar;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class SeasonRepository implements SeasonRepositoryInterface
{
    public function getSeasonDefaults(): SeasonDefault
    {
        $defaults = SeasonDefault::query()->find(1);

        if ($defaults instanceof SeasonDefault) {
            return $defaults;
        }

        $fallback = SeasonCalendar::defaultDefaults();

        return SeasonDefault::query()->create([
            'id' => 1,
            'start_month' => $fallback['start_month'],
            'start_day' => $fallback['start_day'],
            'end_month' => $fallback['end_month'],
            'end_day' => $fallback['end_day'],
            'updated_at' => now(),
        ]);
    }

    public function updateSeasonDefaults(SeasonDefaultsData $data): SeasonDefault
    {
        return SeasonDefault::query()->updateOrCreate(
            ['id' => 1],
            [
                'start_month' => $data->startMonth,
                'start_day' => $data->startDay,
                'end_month' => $data->endMonth,
                'end_day' => $data->endDay,
                'updated_at' => now(),
            ],
        );
    }

    public function listServices(): Collection
    {
        return Service::query()
            ->orderBy('id')
            ->get();
    }

    public function listBonusRows(?string $selectedTitle = null): Collection
    {
        $rows = Bonus::query()
            ->orderByDesc('date_from')
            ->orderByDesc('id')
            ->get();

        if ($selectedTitle === null || $selectedTitle === '') {
            return $rows;
        }

        return $rows
            ->filter(fn (Bonus $bonus): bool => $bonus->displayTitle() === $selectedTitle)
            ->values();
    }

    public function listBonusTitles(): array
    {
        $titles = [];

        foreach ($this->listBonusRows() as $bonus) {
            $title = $bonus->displayTitle();
            if ($title === '') {
                continue;
            }

            $titles[$title] = true;
        }

        return array_keys($titles);
    }

    public function listServiceSlots(int $serviceId): Collection
    {
        return ServiceSlot::query()
            ->where('service_id', $serviceId)
            ->withCount('dates')
            ->withMin('dates', 'slot_date')
            ->withMax('dates', 'slot_date')
            ->orderByDesc('date_start')
            ->orderByDesc('id')
            ->get();
    }

    public function getServiceSlotStats(int $serviceId): array
    {
        $rows = DB::table('service_slots as ss')
            ->leftJoin('service_dates as sd', 'sd.slot_id', '=', 'ss.id')
            ->leftJoin('booking_dates as bd', 'bd.service_date_id', '=', 'sd.id')
            ->leftJoin('bookings as b', 'b.id', '=', 'bd.booking_id')
            ->where('ss.service_id', $serviceId)
            ->select([
                'ss.id as slot_id',
                'b.id as booking_id',
                'b.customer_id',
                'b.sum_paid',
                'b.paid',
            ])
            ->distinct()
            ->get();

        $stats = [];
        $bookingSets = [];
        $customerSets = [];

        foreach ($rows as $row) {
            $slotId = (int) ($row->slot_id ?? 0);
            if ($slotId <= 0) {
                continue;
            }

            if (! isset($stats[$slotId])) {
                $stats[$slotId] = [
                    'bookings_count' => 0,
                    'customers_count' => 0,
                    'sum_total' => 0.0,
                    'paid_total' => 0.0,
                ];
                $bookingSets[$slotId] = [];
                $customerSets[$slotId] = [];
            }

            $bookingId = (int) ($row->booking_id ?? 0);
            if ($bookingId <= 0 || isset($bookingSets[$slotId][$bookingId])) {
                continue;
            }

            $bookingSets[$slotId][$bookingId] = true;
            $stats[$slotId]['bookings_count']++;
            $stats[$slotId]['sum_total'] += (float) ($row->sum_paid ?? 0);
            $stats[$slotId]['paid_total'] += (float) ($row->paid ?? 0);

            $customerId = (int) ($row->customer_id ?? 0);
            if ($customerId > 0 && ! isset($customerSets[$slotId][$customerId])) {
                $customerSets[$slotId][$customerId] = true;
                $stats[$slotId]['customers_count']++;
            }
        }

        return $stats;
    }

    public function getServiceSlotBookingRows(int $slotId): array
    {
        $rows = DB::table('booking_dates as bd')
            ->leftJoin('service_dates as sd', 'sd.id', '=', 'bd.service_date_id')
            ->leftJoin('bookings as b', 'b.id', '=', 'bd.booking_id')
            ->leftJoin('customers as c', 'c.id', '=', 'b.customer_id')
            ->leftJoin('locations as l', 'l.id', '=', 'b.location')
            ->where('sd.slot_id', $slotId)
            ->orderBy('c.lastname')
            ->orderBy('c.firstname')
            ->orderBy('sd.slot_date')
            ->select([
                'b.id as booking_id',
                'b.sum_paid',
                'b.paid',
                'c.id as customer_id',
                'c.firstname',
                'c.lastname',
                'c.parent_phone',
                'l.location_from',
                'l.location_to',
                'l.from_time',
                'l.to_time',
                'sd.slot_date',
            ])
            ->get()
            ->all();

        /** @var list<object> $rows */
        return $rows;
    }

    public function getBonusStats(): array
    {
        $rows = DB::table('bookings')
            ->where('service_type', 2)
            ->groupBy('service')
            ->selectRaw('service as bonus_id')
            ->selectRaw('COUNT(*) as bookings_count')
            ->selectRaw('COUNT(DISTINCT customer_id) as customers_count')
            ->selectRaw('COALESCE(SUM(sum_paid), 0) as sum_total')
            ->selectRaw('COALESCE(SUM(paid), 0) as paid_total')
            ->get();

        $stats = [];
        foreach ($rows as $row) {
            $bonusId = (int) ($row->bonus_id ?? 0);
            if ($bonusId <= 0) {
                continue;
            }

            $stats[$bonusId] = [
                'bookings_count' => (int) ($row->bookings_count ?? 0),
                'customers_count' => (int) ($row->customers_count ?? 0),
                'sum_total' => (float) ($row->sum_total ?? 0),
                'paid_total' => (float) ($row->paid_total ?? 0),
            ];
        }

        return $stats;
    }

    public function getBonusBookingRows(int $bonusId): array
    {
        $rows = DB::table('bookings as b')
            ->leftJoin('customers as c', 'c.id', '=', 'b.customer_id')
            ->leftJoin('locations as l', 'l.id', '=', 'b.location')
            ->where('b.service_type', 2)
            ->where('b.service', $bonusId)
            ->orderBy('c.lastname')
            ->orderBy('c.firstname')
            ->select([
                'b.id as booking_id',
                'b.sum_paid',
                'b.paid',
                'c.id as customer_id',
                'c.firstname',
                'c.lastname',
                'c.parent_phone',
                'l.location_from',
                'l.location_to',
                'l.from_time',
                'l.to_time',
            ])
            ->get()
            ->all();

        /** @var list<object> $rows */
        return $rows;
    }

    public function findServiceSlot(int $slotId): ?ServiceSlot
    {
        return ServiceSlot::query()->find($slotId);
    }

    public function findService(int $serviceId): ?Service
    {
        return Service::query()->find($serviceId);
    }

    public function findOverlappingSlot(
        int $serviceId,
        string $startDate,
        string $endDate,
        ?int $excludeSlotId = null,
    ): ?ServiceSlot {
        return ServiceSlot::query()
            ->where('service_id', $serviceId)
            ->whereNotNull('date_start')
            ->whereNotNull('date_end')
            ->when($excludeSlotId !== null && $excludeSlotId > 0, fn ($query) => $query->where('id', '<>', $excludeSlotId))
            ->where('date_end', '>=', $startDate)
            ->where('date_start', '<', \Carbon\CarbonImmutable::parse($endDate)->addDay()->toDateString())
            ->orderBy('id')
            ->first();
    }

    public function getSlotDateBounds(int $slotId): array
    {
        $row = DB::table('service_dates')
            ->where('slot_id', $slotId)
            ->selectRaw('MIN(slot_date) as min_date, MAX(slot_date) as max_date')
            ->first();

        return [
            'min' => ($minDate = data_get($row, 'min_date')) !== null ? (string) $minDate : null,
            'max' => ($maxDate = data_get($row, 'max_date')) !== null ? (string) $maxDate : null,
        ];
    }

    public function slotHasBookings(int $slotId): bool
    {
        return DB::table('booking_dates as bd')
            ->join('service_dates as sd', 'sd.id', '=', 'bd.service_date_id')
            ->where('sd.slot_id', $slotId)
            ->exists();
    }

    public function createServiceSeason(
        int $serviceId,
        string $name,
        string $startDate,
        string $endDate,
        int $max,
    ): ServiceSlot {
        return ServiceSlot::query()->create([
            'service_id' => $serviceId,
            'name' => $name,
            'date_start' => $startDate,
            'date_end' => $endDate,
            'max' => $max,
            'season_type' => 'other',
        ]);
    }

    public function updateServiceSeason(
        ServiceSlot $slot,
        string $name,
        string $startDate,
        string $endDate,
    ): ServiceSlot {
        $slot->name = $name;
        $slot->date_start = $startDate;
        $slot->date_end = $endDate;
        $slot->save();

        return $slot->refresh();
    }

    public function deleteServiceSeason(ServiceSlot $slot): void
    {
        $slot->delete();
    }
}
