<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Bookings;

use App\DTOs\Bookings\BookingData;
use App\DTOs\Bookings\BookingFilterData;
use App\Exceptions\ApiException;
use App\Models\Booking;
use App\Models\BookingDate;
use App\Models\BookingPayment;
use App\Models\BookingWinterDetail;
use App\Models\Location;
use App\Models\SeasonDefault;
use App\Models\Service;
use App\Models\ServiceDate;
use App\Models\ServiceSlot;
use App\Repositories\Contracts\Bookings\BookingRepositoryInterface;
use App\Support\Localization\LocalizedText;
use App\Support\Seasons\SeasonCalendar;
use App\Support\Seasons\SeasonLabel;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class BookingRepository implements BookingRepositoryInterface
{
    private ?bool $seasonTypeColumnAvailable = null;

    /**
     * @return LengthAwarePaginator<int, Booking>
     */
    public function paginate(BookingFilterData $filters): LengthAwarePaginator
    {
        return Booking::query()
            ->with($this->baseRelations())
            ->when($filters->search !== null, function ($query) use ($filters): void {
                $q = '%'.$filters->search.'%';
                $searchId = ctype_digit($filters->search) ? (int) $filters->search : null;

                $query->where(function ($nested) use ($q, $searchId): void {
                    $nested->where('note', 'like', $q)
                        ->orWhereHas('customer', function ($customerQuery) use ($q): void {
                            $customerQuery->where('firstname', 'like', $q)
                                ->orWhere('surname', 'like', $q)
                                ->orWhere('lastname', 'like', $q)
                                ->orWhere('email', 'like', $q)
                                ->orWhere('parent_phone', 'like', $q);
                        })
                        ->orWhereHas('serviceModel', function ($serviceQuery) use ($q): void {
                            $serviceQuery->where(function ($serviceNested) use ($q): void {
                                $serviceNested->where('title', 'like', $q);

                                foreach (LocalizedText::supportedLocales() as $locale) {
                                    $serviceNested->orWhere("title_translations->{$locale}", 'like', $q);
                                }
                            });
                        });

                    if ($searchId !== null) {
                        $nested->orWhere('id', $searchId);
                    }
                });
            })
            ->when($filters->serviceType !== null, fn ($query) => $query->where('service_type', $filters->serviceType))
            ->when($filters->serviceId !== null, fn ($query) => $query->where('service', $filters->serviceId))
            ->when($filters->customerId !== null, fn ($query) => $query->where('customer_id', $filters->customerId))
            ->when($filters->active !== null, fn ($query) => $query->where('active', $filters->active))
            ->when($filters->date !== null, function ($query) use ($filters): void {
                $query->whereHas('bookingDates.serviceDate', fn ($dateQuery) => $dateQuery->whereDate('slot_date', $filters->date));
            })
            ->orderByDesc('id')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    public function findWithRelations(int $id): ?Booking
    {
        return Booking::query()
            ->with($this->baseRelations())
            ->find($id);
    }

    public function create(BookingData $data, int $customerId): Booking
    {
        $payload = $data->toBookingPersistence($customerId);
        $payload['active'] = true;

        $booking = Booking::query()->create($payload);

        return $this->loadRelations($booking);
    }

    public function update(Booking $booking, BookingData $data, int $customerId, bool $preservePaid = false): Booking
    {
        $payload = $data->toBookingPersistence($customerId);

        if ($preservePaid) {
            unset($payload['paid']);
        }
        $booking->fill($payload);
        $booking->save();

        return $this->loadRelations($booking);
    }

    public function deactivate(Booking $booking): Booking
    {
        $booking->active = false;
        $booking->save();

        return $this->loadRelations($booking);
    }

    public function isLocationAllowedForService(int $locationId, int $serviceId): bool
    {
        if ($locationId <= 0 || $serviceId <= 0) {
            return false;
        }

        return Location::query()
            ->whereKey($locationId)
            ->where(function ($query) use ($serviceId): void {
                $query->where('service_id', 0)
                    ->orWhere('service_id', $serviceId);
            })
            ->exists();
    }

    public function resolveSlotIdForDate(int $serviceId, string $slotDate, int $requestedSlotId = 0): int
    {
        if ($serviceId <= 0) {
            throw new ApiException(
                errorCode: 'booking_service_required',
                message: 'Service is required.',
                statusCode: 422,
            );
        }

        if ($requestedSlotId > 0 && $this->slotBelongsToService($requestedSlotId, $serviceId)) {
            if (! $this->extendSlotToDate($requestedSlotId, $slotDate)) {
                throw new ApiException(
                    errorCode: 'booking_slot_overlap',
                    message: 'The selected service period cannot be extended across another period.',
                    statusCode: 422,
                );
            }

            return $requestedSlotId;
        }

        $existingSlotId = $this->findSeasonSlotForDate($serviceId, $slotDate);
        if ($existingSlotId > 0) {
            return $existingSlotId;
        }

        $date = CarbonImmutable::parse($slotDate);

        $labelSlotId = $this->findSeasonSlotByLabelForDate($serviceId, $date);
        if ($labelSlotId > 0 && $this->extendSlotToDate($labelSlotId, $slotDate)) {
            return $labelSlotId;
        }

        $extendedSlotId = $this->extendAdjacentSeasonToDate($serviceId, $date);
        if ($extendedSlotId > 0) {
            return $extendedSlotId;
        }

        return $this->createSeasonSlotForDate($serviceId, $date);
    }

    public function getOrCreateServiceDateId(int $slotId, string $date): int
    {
        $existing = ServiceDate::query()
            ->where('slot_id', $slotId)
            ->whereDate('slot_date', $date)
            ->first();

        if ($existing !== null) {
            if (! $existing->is_active) {
                $existing->is_active = true;
                $existing->save();
            }

            return (int) $existing->id;
        }

        $created = ServiceDate::query()->create([
            'slot_id' => $slotId,
            'slot_date' => $date,
            'is_active' => true,
        ]);

        return (int) $created->id;
    }

    public function hasCustomerServiceDateConflict(
        int $bookingId,
        int $customerId,
        int $serviceId,
        int $serviceType,
        string $slotDate,
    ): bool {
        return DB::table('booking_dates as bd')
            ->join('bookings as b', 'b.id', '=', 'bd.booking_id')
            ->join('service_dates as sd', 'sd.id', '=', 'bd.service_date_id')
            ->whereDate('sd.slot_date', $slotDate)
            ->where('b.customer_id', $customerId)
            ->where('b.service', $serviceId)
            ->where('b.service_type', $serviceType)
            ->where('b.active', true)
            ->where('b.id', '<>', $bookingId)
            ->exists();
    }

    public function hasCapacity(int $serviceDateId, int $bookingId): bool
    {
        $date = ServiceDate::query()
            ->select('service_dates.*', 'service_slots.max as capacity')
            ->join('service_slots', 'service_slots.id', '=', 'service_dates.slot_id')
            ->withCount('activeBookingDates')
            ->withExists(['activeBookingDates as already_booked' => static fn ($query) => $query->where('booking_id', $bookingId)])
            ->find($serviceDateId);

        return $date !== null && (
            (bool) $date->getAttribute('already_booked')
            || (int) $date->active_booking_dates_count < (int) $date->getAttribute('capacity')
        );
    }

    /**
     * @param  array<string,int>  $dateServiceDateMap
     */
    public function syncBookingDates(int $bookingId, array $dateServiceDateMap): void
    {
        if ($dateServiceDateMap === []) {
            BookingDate::query()->where('booking_id', $bookingId)->delete();

            return;
        }

        $existingRows = BookingDate::query()
            ->select('booking_dates.id', 'booking_dates.service_date_id', 'service_dates.slot_date')
            ->join('service_dates', 'service_dates.id', '=', 'booking_dates.service_date_id')
            ->where('booking_dates.booking_id', $bookingId)
            ->get();

        $existingByDate = [];
        foreach ($existingRows as $row) {
            $date = $this->normalizeDateValue(data_get($row, 'slot_date'));
            if ($date === null) {
                continue;
            }
            $existingByDate[$date] = [
                'id' => (int) $row->id,
                'service_date_id' => (int) $row->service_date_id,
            ];
        }

        foreach ($dateServiceDateMap as $date => $serviceDateId) {
            $existing = $existingByDate[$date] ?? null;

            if ($existing !== null) {
                if ((int) $existing['service_date_id'] !== $serviceDateId) {
                    BookingDate::query()
                        ->whereKey((int) $existing['id'])
                        ->update(['service_date_id' => $serviceDateId]);
                }

                unset($existingByDate[$date]);

                continue;
            }

            BookingDate::query()->create([
                'booking_id' => $bookingId,
                'service_date_id' => $serviceDateId,
            ]);
        }

        $staleIds = array_map(
            static fn (array $row): int => (int) $row['id'],
            array_values($existingByDate),
        );

        if ($staleIds !== []) {
            BookingDate::query()->whereIn('id', $staleIds)->delete();
        }
    }

    public function syncPaymentTotals(int $bookingId, ?int $sumPaid, ?int $paid): void
    {
        BookingPayment::query()->updateOrCreate(
            ['booking_id' => $bookingId],
            [
                'sum_paid' => $sumPaid,
                'paid' => $paid,
            ],
        );
    }

    public function findLatestSkillForCustomerService(int $customerId, int $serviceId, int $serviceType): ?int
    {
        $row = DB::table('booking_winter_details as bwd')
            ->join('bookings as b', 'b.id', '=', 'bwd.booking_id')
            ->where('b.customer_id', $customerId)
            ->where('b.service', $serviceId)
            ->where('b.service_type', $serviceType)
            ->whereNotNull('bwd.skill_id')
            ->orderByDesc('b.id')
            ->select('bwd.skill_id')
            ->first();

        $skillId = $row !== null ? (int) ($row->skill_id ?? 0) : 0;

        return $skillId > 0 ? $skillId : null;
    }

    public function syncWinterDetails(Booking $booking, BookingData $data, ?int $skillId): void
    {
        if (! $data->isSkiService()) {
            $this->deleteWinterDetails((int) $booking->id);

            return;
        }

        $equipId = $data->isEquipService() && $data->equipEnabled
            ? $data->equipId
            : null;

        $cardId = $data->isCardService()
            ? $data->cardId
            : null;

        $hasPayload = ($skillId !== null && $skillId > 0)
            || ($equipId !== null && $equipId > 0)
            || ($cardId !== null && $cardId > 0)
            || ($data->skiShoeSize !== null && $data->skiShoeSize > 0)
            || ($data->skiHeight !== null && $data->skiHeight > 0);

        if (! $hasPayload) {
            $this->deleteWinterDetails((int) $booking->id);

            return;
        }

        BookingWinterDetail::query()->updateOrCreate(
            ['booking_id' => (int) $booking->id],
            [
                'skill_id' => $skillId,
                'equip_id' => $equipId,
                'equip_enabled' => $data->equipEnabled,
                'card_id' => $cardId,
                'shoe_size' => $data->skiShoeSize,
                'height' => $data->skiHeight,
            ],
        );
    }

    public function deleteWinterDetails(int $bookingId): void
    {
        BookingWinterDetail::query()
            ->where('booking_id', $bookingId)
            ->delete();
    }

    /**
     * @return array<int,string>
     */
    private function baseRelations(): array
    {
        return [
            'customer',
            'serviceModel',
            'locationRecord',
            'bookingDates.serviceDate.slot',
            'payment',
            'winterDetail',
        ];
    }

    private function loadRelations(Booking $booking): Booking
    {
        return $booking->loadMissing($this->baseRelations());
    }

    private function slotBelongsToService(int $slotId, int $serviceId): bool
    {
        return ServiceSlot::query()
            ->whereKey($slotId)
            ->where('service_id', $serviceId)
            ->exists();
    }

    private function extendSlotToDate(int $slotId, string $slotDate): bool
    {
        /** @var ServiceSlot|null $slot */
        $slot = ServiceSlot::query()->find($slotId);
        if ($slot === null) {
            return false;
        }

        $currentStart = $this->normalizeDateValue($slot->date_start);
        $currentEnd = $this->normalizeDateValue($slot->date_end);

        $newStart = $currentStart !== null ? min($currentStart, $slotDate) : $slotDate;
        $newEnd = $currentEnd !== null ? max($currentEnd, $slotDate) : $slotDate;

        if ($newStart === $currentStart && $newEnd === $currentEnd) {
            return true;
        }

        if ($this->hasSeasonOverlap((int) $slot->service_id, $slotId, $newStart, $newEnd)) {
            return false;
        }

        $slot->date_start = $newStart;
        $slot->date_end = $newEnd;
        $slot->save();

        return true;
    }

    private function findSeasonSlotByLabelForDate(int $serviceId, CarbonImmutable $date): int
    {
        $defaults = $this->getSeasonDefaults();
        $range = SeasonCalendar::rangeForDate($date, $defaults);
        if ($date->toDateString() < $range['start'] || $date->toDateString() > $range['end']) {
            return 0;
        }

        $startYear = (int) substr($range['start'], 0, 4);
        $endYear = (int) substr($range['end'], 0, 4);

        $fullLabel = SeasonLabel::normalize(SeasonLabel::fromDateRange($range['start'], $range['end'], ''));
        $shortLabel = SeasonLabel::normalize(SeasonLabel::shortFromYears($startYear, $endYear));

        $rows = ServiceSlot::query()
            ->select(['id', 'name'])
            ->where('service_id', $serviceId)
            ->orderByDesc('id')
            ->get();

        foreach ($rows as $row) {
            $slotId = (int) $row->id;
            if ($slotId <= 0) {
                continue;
            }

            $normalizedName = SeasonLabel::normalize((string) ($row->name ?? ''));
            if ($normalizedName === '') {
                continue;
            }

            if ($fullLabel !== '' && str_contains($normalizedName, $fullLabel)) {
                return $slotId;
            }

            if ($shortLabel !== '' && str_contains($normalizedName, $shortLabel)) {
                return $slotId;
            }
        }

        return 0;
    }

    /**
     * @return array{start_month:int,start_day:int,end_month:int,end_day:int}
     */
    private function getSeasonDefaults(): array
    {
        $defaults = SeasonDefault::query()->find(1);

        if ($defaults === null) {
            $fallback = SeasonCalendar::defaultDefaults();

            SeasonDefault::query()->create([
                'id' => 1,
                'start_month' => $fallback['start_month'],
                'start_day' => $fallback['start_day'],
                'end_month' => $fallback['end_month'],
                'end_day' => $fallback['end_day'],
                'updated_at' => now(),
            ]);

            return $fallback;
        }

        return SeasonCalendar::normalizeDefaults($defaults);
    }

    private function findSeasonSlotForDate(int $serviceId, string $slotDate): int
    {
        $slot = ServiceSlot::query()
            ->where('service_id', $serviceId)
            ->whereNotNull('date_start')
            ->whereNotNull('date_end')
            ->whereDate('date_start', '<=', $slotDate)
            ->whereDate('date_end', '>=', $slotDate)
            ->orderByDesc('date_start')
            ->orderByDesc('id')
            ->first();

        return $slot !== null ? (int) $slot->id : 0;
    }

    private function extendAdjacentSeasonToDate(int $serviceId, CarbonImmutable $date): int
    {
        $dateYmd = $date->toDateString();
        $prevDay = $date->subDay()->toDateString();
        $nextDay = $date->addDay()->toDateString();

        $prevSlot = ServiceSlot::query()
            ->where('service_id', $serviceId)
            ->whereDate('date_end', $prevDay)
            ->whereNotNull('date_start')
            ->whereNotNull('date_end')
            ->orderByDesc('date_start')
            ->first();

        if ($prevSlot !== null) {
            $start = $this->normalizeDateValue($prevSlot->date_start);
            if ($start !== null && ! $this->hasSeasonOverlap($serviceId, (int) $prevSlot->id, $start, $dateYmd)) {
                $prevSlot->date_end = $dateYmd;
                $prevSlot->save();

                return (int) $prevSlot->id;
            }
        }

        $nextSlot = ServiceSlot::query()
            ->where('service_id', $serviceId)
            ->whereDate('date_start', $nextDay)
            ->whereNotNull('date_start')
            ->whereNotNull('date_end')
            ->orderBy('date_end')
            ->first();

        if ($nextSlot !== null) {
            $end = $this->normalizeDateValue($nextSlot->date_end);
            if ($end !== null && ! $this->hasSeasonOverlap($serviceId, (int) $nextSlot->id, $dateYmd, $end)) {
                $nextSlot->date_start = $dateYmd;
                $nextSlot->save();

                return (int) $nextSlot->id;
            }
        }

        return 0;
    }

    private function hasSeasonOverlap(int $serviceId, int $excludeSlotId, string $startYmd, string $endYmd): bool
    {
        return ServiceSlot::query()
            ->where('service_id', $serviceId)
            ->whereNotNull('date_start')
            ->whereNotNull('date_end')
            ->when($excludeSlotId > 0, fn ($query) => $query->where('id', '<>', $excludeSlotId))
            ->where(function ($query) use ($startYmd, $endYmd): void {
                $query->whereDate('date_end', '>=', $startYmd)
                    ->whereDate('date_start', '<=', $endYmd);
            })
            ->exists();
    }

    private function createSeasonSlotForDate(int $serviceId, CarbonImmutable $date): int
    {
        $slotDate = $date->toDateString();
        $defaults = $this->getSeasonDefaults();
        $defaultRange = SeasonCalendar::rangeForDate($date, $defaults);
        if ($slotDate < $defaultRange['start'] || $slotDate > $defaultRange['end']) {
            $defaultRange = ['start' => $slotDate, 'end' => $slotDate];
        }
        $range = $this->adjustRangeToAvailableGap(
            $serviceId,
            $slotDate,
            $defaultRange['start'],
            $defaultRange['end'],
        );
        $label = SeasonLabel::fromDateRange($range['start'], $range['end'], '');

        if ($this->hasSeasonOverlap($serviceId, 0, $range['start'], $range['end'])) {
            throw new ApiException(
                errorCode: 'booking_slot_overlap',
                message: 'Cannot create season for the selected date because ranges overlap.',
                statusCode: 422,
            );
        }

        $service = Service::query()->find($serviceId);
        if ($service === null) {
            throw new ApiException(
                errorCode: 'booking_service_not_found',
                message: 'Service not found.',
                statusCode: 404,
            );
        }

        $slotPayload = [
            'service_id' => $serviceId,
            'name' => $label,
            'date_start' => $range['start'],
            'date_end' => $range['end'],
            'max' => max(0, (int) $service->max),
        ];

        if ($this->hasSeasonTypeColumn()) {
            $slotPayload['season_type'] = 'other';
        }

        $slot = ServiceSlot::query()->create($slotPayload);

        return (int) $slot->id;
    }

    /**
     * @return array{start:string,end:string}
     */
    private function adjustRangeToAvailableGap(int $serviceId, string $slotDate, string $startYmd, string $endYmd): array
    {
        if (! $this->hasSeasonOverlap($serviceId, 0, $startYmd, $endYmd)) {
            return [
                'start' => $startYmd,
                'end' => $endYmd,
            ];
        }

        $start = $startYmd;
        $end = $endYmd;

        $prevSlotEnd = $this->normalizeDateValue(ServiceSlot::query()
            ->where('service_id', $serviceId)
            ->whereNotNull('date_start')
            ->whereNotNull('date_end')
            ->whereDate('date_end', '<', $slotDate)
            ->orderByDesc('date_end')
            ->value('date_end'));

        if ($prevSlotEnd !== null) {
            $nextAfterPrev = CarbonImmutable::parse($prevSlotEnd)->addDay()->toDateString();
            if ($nextAfterPrev > $start) {
                $start = $nextAfterPrev;
            }
        }

        $nextSlotStart = $this->normalizeDateValue(ServiceSlot::query()
            ->where('service_id', $serviceId)
            ->whereNotNull('date_start')
            ->whereNotNull('date_end')
            ->whereDate('date_start', '>', $slotDate)
            ->orderBy('date_start')
            ->value('date_start'));

        if ($nextSlotStart !== null) {
            $prevBeforeNext = CarbonImmutable::parse($nextSlotStart)->subDay()->toDateString();
            if ($prevBeforeNext < $end) {
                $end = $prevBeforeNext;
            }
        }

        if ($start > $slotDate || $end < $slotDate || $start > $end) {
            $start = $slotDate;
            $end = $slotDate;
        }

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    private function hasSeasonTypeColumn(): bool
    {
        if ($this->seasonTypeColumnAvailable !== null) {
            return $this->seasonTypeColumnAvailable;
        }

        $this->seasonTypeColumnAvailable = Schema::hasColumn('service_slots', 'season_type');

        return $this->seasonTypeColumnAvailable;
    }

    private function normalizeDateValue(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value) && $value !== '') {
            return substr($value, 0, 10);
        }

        return null;
    }
}
