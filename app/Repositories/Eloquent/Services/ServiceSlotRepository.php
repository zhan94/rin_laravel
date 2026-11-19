<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Services;

use App\DTOs\Services\ServiceSlotData;
use App\DTOs\Services\ServiceSlotFilterData;
use App\Exceptions\ApiException;
use App\Models\Service;
use App\Models\ServiceSlot;
use App\Repositories\Contracts\Services\ServiceSlotRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

final class ServiceSlotRepository implements ServiceSlotRepositoryInterface
{
    private ?bool $seasonTypeColumnAvailable = null;

    /**
     * @return LengthAwarePaginator<int, ServiceSlot>
     */
    public function paginateByService(Service $service, ServiceSlotFilterData $filters): LengthAwarePaginator
    {
        return $service->slots()
            ->with([
                'dates' => static fn ($query) => $query->withCount('bookingDates'),
            ])
            ->orderByDesc('id')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    public function create(Service $service, ServiceSlotData $data): ServiceSlot
    {
        $slotPayload = $data->toSlotPersistence();
        $slotPayload['service_id'] = $service->id;

        if (! $this->hasSeasonTypeColumn()) {
            unset($slotPayload['season_type']);
        }

        $slot = ServiceSlot::query()->create($slotPayload);
        $this->syncDates($slot, $data);

        return $slot->load('dates');
    }

    public function update(ServiceSlot $slot, ServiceSlotData $data): ServiceSlot
    {
        $slotPayload = $data->toSlotPersistence();
        $slotPayload['service_id'] = $slot->service_id;

        if (! $this->hasSeasonTypeColumn()) {
            unset($slotPayload['season_type']);
        }

        $slot->fill($slotPayload);
        $slot->save();

        $this->syncDates($slot, $data);

        return $slot->load('dates');
    }

    public function delete(ServiceSlot $slot): void
    {
        if ($slot->dates()->has('bookingDates')->exists()) {
            throw new ApiException('slot_has_bookings', 'A slot with bookings cannot be deleted.', 422);
        }

        $slot->delete();
    }

    private function syncDates(ServiceSlot $slot, ServiceSlotData $data): void
    {
        $existing = $slot->dates()->withCount(['bookingDates', 'activeBookingDates'])->get();
        $requested = array_fill_keys($data->dates, true);

        foreach ($existing as $date) {
            $key = substr((string) $date->getRawOriginal('slot_date'), 0, 10);
            $kept = isset($requested[$key]);
            if ((! $kept && $date->booking_dates_count > 0) || $date->active_booking_dates_count > $data->max) {
                throw new ApiException('slot_has_bookings', 'Slot changes must accommodate the existing bookings.', 422, ['date' => $key]);
            }

            if ($kept) {
                unset($requested[$key]);
            } else {
                $date->delete();
            }
        }

        if ($requested !== []) {
            $slot->dates()->insert(array_map(
                static fn (string $date): array => ['slot_id' => $slot->id, 'slot_date' => $date, 'is_active' => true],
                array_keys($requested),
            ));
        }
    }

    private function hasSeasonTypeColumn(): bool
    {
        if ($this->seasonTypeColumnAvailable !== null) {
            return $this->seasonTypeColumnAvailable;
        }

        $this->seasonTypeColumnAvailable = Schema::hasColumn('service_slots', 'season_type');

        return $this->seasonTypeColumnAvailable;
    }
}
