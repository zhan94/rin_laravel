<?php

declare(strict_types=1);

namespace App\Repositories\Contracts\Seasons;

use App\DTOs\Seasons\SeasonDefaultsData;
use App\Models\SeasonDefault;
use App\Models\Service;
use App\Models\ServiceSlot;
use Illuminate\Support\Collection;

interface SeasonRepositoryInterface
{
    public function getSeasonDefaults(): SeasonDefault;

    public function updateSeasonDefaults(SeasonDefaultsData $data): SeasonDefault;

    /**
     * @return Collection<int,Service>
     */
    public function listServices(): Collection;

    /**
     * @return Collection<int,\App\Models\Bonus>
     */
    public function listBonusRows(?string $selectedTitle = null): Collection;

    /**
     * @return list<string>
     */
    public function listBonusTitles(): array;

    /**
     * @return Collection<int,ServiceSlot>
     */
    public function listServiceSlots(int $serviceId): Collection;

    /**
     * @return array<int,array{bookings_count:int,customers_count:int,sum_total:float,paid_total:float}>
     */
    public function getServiceSlotStats(int $serviceId): array;

    /**
     * @return list<object>
     */
    public function getServiceSlotBookingRows(int $slotId): array;

    /**
     * @return array<int,array{bookings_count:int,customers_count:int,sum_total:float,paid_total:float}>
     */
    public function getBonusStats(): array;

    /**
     * @return list<object>
     */
    public function getBonusBookingRows(int $bonusId): array;

    public function findServiceSlot(int $slotId): ?ServiceSlot;

    public function findService(int $serviceId): ?Service;

    public function findOverlappingSlot(
        int $serviceId,
        string $startDate,
        string $endDate,
        ?int $excludeSlotId = null,
    ): ?ServiceSlot;

    /**
     * @return array{min:?string,max:?string}
     */
    public function getSlotDateBounds(int $slotId): array;

    public function slotHasBookings(int $slotId): bool;

    public function createServiceSeason(
        int $serviceId,
        string $name,
        string $startDate,
        string $endDate,
        int $max,
    ): ServiceSlot;

    public function updateServiceSeason(
        ServiceSlot $slot,
        string $name,
        string $startDate,
        string $endDate,
    ): ServiceSlot;

    public function deleteServiceSeason(ServiceSlot $slot): void;
}
