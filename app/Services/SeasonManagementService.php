<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Seasons\SeasonDefaultsData;
use App\DTOs\Seasons\SeasonOverviewFilterData;
use App\DTOs\Seasons\ServiceSeasonData;
use App\Enums\SeasonViewType;
use App\Exceptions\ApiException;
use App\Models\SeasonDefault;
use App\Models\Service;
use App\Models\ServiceSlot;
use App\Repositories\Contracts\Seasons\SeasonRepositoryInterface;
use App\Support\Seasons\SeasonLabel;
use App\Support\Seasons\SeasonPresenter;
use Illuminate\Support\Facades\DB;

final readonly class SeasonManagementService
{
    public function __construct(
        private SeasonRepositoryInterface $repository,
        private SeasonPresenter $presenter,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function overview(SeasonOverviewFilterData $filters): array
    {
        $defaults = $this->repository->getSeasonDefaults();
        $services = $this->repository->listServices();
        $bonusTitles = $this->repository->listBonusTitles();

        $selectedServiceId = $filters->serviceId;
        $selectedBonusTitle = $filters->bonusTitle;

        if ($filters->type === SeasonViewType::SERVICE) {
            if (($selectedServiceId ?? 0) <= 0 && $services->isNotEmpty()) {
                $selectedServiceId = (int) $services->first()->id;
            }

            $result = $this->buildServiceSeasonsData($selectedServiceId ?? 0, $filters->seasonId);
        } else {
            if (($selectedBonusTitle ?? '') === '' && $bonusTitles !== []) {
                $selectedBonusTitle = $bonusTitles[0];
            }

            $result = $this->buildBonusSeasonsData($selectedBonusTitle, $filters->seasonId);
        }

        return [
            'type' => $filters->type->value,
            'services' => $this->presenter->services($services),
            'bonus_titles' => $bonusTitles,
            'selected_service_id' => $selectedServiceId > 0 ? $selectedServiceId : null,
            'selected_bonus_title' => $selectedBonusTitle,
            'season_defaults' => $this->presenter->defaults($defaults),
            'seasons' => $result['seasons'],
            'selected_season' => $result['selected_season'],
            'season_bookings' => $result['season_bookings'],
        ];
    }

    public function updateDefaults(SeasonDefaultsData $data): SeasonDefault
    {
        $this->assertValidDayMonth($data->startMonth, $data->startDay);
        $this->assertValidDayMonth($data->endMonth, $data->endDay);

        return DB::transaction(
            fn (): SeasonDefault => $this->repository->updateSeasonDefaults($data),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function serializeDefaults(SeasonDefault $defaults): array
    {
        return $this->presenter->defaults($defaults);
    }

    /**
     * @return array<string,mixed>
     */
    public function createServiceSeason(ServiceSeasonData $data): array
    {
        $service = $this->repository->findService($data->serviceId);
        if (! $service instanceof Service) {
            throw new ApiException(
                errorCode: 'season_service_invalid',
                message: 'Invalid service.',
                statusCode: 422,
            );
        }

        $this->assertDateRange($data->startDate, $data->endDate);

        $slot = DB::transaction(function () use ($data, $service): ServiceSlot {
            $current = Service::query()->lockForUpdate()->findOrFail($service->id);
            $this->assertNoOverlap((int) $current->id, $data->startDate, $data->endDate, null);
            $seasonName = $this->resolveSeasonName($data->name, $data->startDate, $data->endDate);

            return $this->repository->createServiceSeason(
                serviceId: (int) $service->id,
                name: $seasonName,
                startDate: $data->startDate,
                endDate: $data->endDate,
                max: max(0, (int) ($current->max ?? 0)),
            );
        }, 3);

        $statsMap = $this->repository->getServiceSlotStats((int) $service->id);

        return $this->presenter->serviceSeason($slot, $statsMap[(int) $slot->id] ?? null);
    }

    /**
     * @return array<string,mixed>
     */
    public function updateServiceSeason(int $slotId, ServiceSeasonData $data): array
    {
        $slot = $this->repository->findServiceSlot($slotId);
        if (! $slot instanceof ServiceSlot) {
            throw new ApiException(
                errorCode: 'season_not_found',
                message: 'Season not found.',
                statusCode: 404,
            );
        }

        $this->assertDateRange($data->startDate, $data->endDate);

        $updated = DB::transaction(function () use ($data, $slot): ServiceSlot {
            Service::query()->lockForUpdate()->findOrFail($slot->service_id);
            $current = ServiceSlot::query()->findOrFail($slot->id);
            $this->assertNoOverlap((int) $current->service_id, $data->startDate, $data->endDate, (int) $current->id);
            $this->assertSlotDateBoundsInsideRange((int) $current->id, $data->startDate, $data->endDate);
            $seasonName = $this->resolveSeasonName($data->name, $data->startDate, $data->endDate);

            return $this->repository->updateServiceSeason(
                slot: $current,
                name: $seasonName,
                startDate: $data->startDate,
                endDate: $data->endDate,
            );
        }, 3);

        $statsMap = $this->repository->getServiceSlotStats((int) $updated->service_id);

        return $this->presenter->serviceSeason($updated, $statsMap[(int) $updated->id] ?? null);
    }

    public function deleteServiceSeason(int $slotId): void
    {
        $slot = $this->repository->findServiceSlot($slotId);
        if (! $slot instanceof ServiceSlot) {
            throw new ApiException(
                errorCode: 'season_not_found',
                message: 'Season not found.',
                statusCode: 404,
            );
        }

        DB::transaction(function () use ($slot): void {
            Service::query()->lockForUpdate()->findOrFail($slot->service_id);
            $current = ServiceSlot::query()->findOrFail($slot->id);
            if ($this->repository->slotHasBookings((int) $current->id)) {
                throw new ApiException('season_has_bookings', 'Season cannot be deleted because it has bookings.', 422);
            }

            $this->repository->deleteServiceSeason($current);
        }, 3);
    }

    /**
     * @return array{seasons:list<array<string,mixed>>,selected_season:array<string,mixed>|null,season_bookings:list<array<string,mixed>>}
     */
    private function buildServiceSeasonsData(int $serviceId, ?int $requestedSeasonId): array
    {
        $seasons = [];
        $service = $serviceId > 0 ? $this->repository->findService($serviceId) : null;

        if ($serviceId > 0) {
            $slots = $this->repository->listServiceSlots($serviceId);
            $statsMap = $this->repository->getServiceSlotStats($serviceId);

            foreach ($slots as $slot) {
                $slotId = (int) $slot->id;
                $stats = $statsMap[$slotId] ?? [
                    'bookings_count' => 0,
                    'customers_count' => 0,
                    'sum_total' => 0.0,
                    'paid_total' => 0.0,
                ];

                $seasons[] = $this->presenter->serviceSeason($slot, $stats, $service instanceof Service ? $service : null);
            }
        }

        $selectedSeasonId = $this->resolveSelectedSeasonId($seasons, $requestedSeasonId);
        $selectedSeason = null;

        foreach ($seasons as &$season) {
            $isSelected = (int) ($season['id'] ?? 0) === $selectedSeasonId;
            $season['is_selected'] = $isSelected;
            if ($isSelected) {
                $selectedSeason = $season;
            }
        }
        unset($season);

        $bookings = $selectedSeasonId > 0
            ? $this->presenter->serviceBookings($this->repository->getServiceSlotBookingRows($selectedSeasonId))
            : [];

        return [
            'seasons' => $seasons,
            'selected_season' => $selectedSeason,
            'season_bookings' => $bookings,
        ];
    }

    /**
     * @return array{seasons:list<array<string,mixed>>,selected_season:array<string,mixed>|null,season_bookings:list<array<string,mixed>>}
     */
    private function buildBonusSeasonsData(?string $selectedBonusTitle, ?int $requestedSeasonId): array
    {
        $statsMap = $this->repository->getBonusStats();
        $bonuses = $this->repository->listBonusRows($selectedBonusTitle);

        $seasons = [];

        foreach ($bonuses as $bonus) {
            $bonusId = (int) $bonus->id;
            $stats = $statsMap[$bonusId] ?? [
                'bookings_count' => 0,
                'customers_count' => 0,
                'sum_total' => 0.0,
                'paid_total' => 0.0,
            ];

            $seasons[] = $this->presenter->bonusSeason($bonus, $stats);
        }

        $selectedSeasonId = $this->resolveSelectedSeasonId($seasons, $requestedSeasonId);
        $selectedSeason = null;

        foreach ($seasons as &$season) {
            $isSelected = (int) ($season['id'] ?? 0) === $selectedSeasonId;
            $season['is_selected'] = $isSelected;
            if ($isSelected) {
                $selectedSeason = $season;
            }
        }
        unset($season);

        $bookings = $selectedSeasonId > 0
            ? $this->presenter->bonusBookings($this->repository->getBonusBookingRows($selectedSeasonId))
            : [];

        return [
            'seasons' => $seasons,
            'selected_season' => $selectedSeason,
            'season_bookings' => $bookings,
        ];
    }

    /**
     * @param  list<array<string,mixed>>  $seasons
     */
    private function resolveSelectedSeasonId(array $seasons, ?int $requestedId): int
    {
        if ($seasons === []) {
            return 0;
        }

        if (($requestedId ?? 0) > 0) {
            foreach ($seasons as $season) {
                if ($season['id'] === (int) $requestedId) {
                    return (int) $requestedId;
                }
            }
        }

        return $seasons[0]['id'];
    }

    private function assertDateRange(string $startDate, string $endDate): void
    {
        if ($startDate === '' || $endDate === '') {
            throw new ApiException(
                errorCode: 'season_range_invalid',
                message: 'Season start and end dates are required.',
                statusCode: 422,
            );
        }

        if ($startDate > $endDate) {
            throw new ApiException(
                errorCode: 'season_range_invalid',
                message: 'Season start date cannot be after end date.',
                statusCode: 422,
            );
        }
    }

    private function assertNoOverlap(int $serviceId, string $startDate, string $endDate, ?int $excludeSlotId): void
    {
        $overlap = $this->repository->findOverlappingSlot($serviceId, $startDate, $endDate, $excludeSlotId);

        if (! $overlap instanceof ServiceSlot) {
            return;
        }

        $name = trim((string) $overlap->name);
        if ($name === '') {
            $name = SeasonLabel::fromDateRange(
                $this->presenter->dateString($overlap->date_start),
                $this->presenter->dateString($overlap->date_end),
                'Season #'.(int) $overlap->id,
            );
        }

        $range = $this->presenter->dateRangeLabel(
            $this->presenter->dateString($overlap->date_start),
            $this->presenter->dateString($overlap->date_end),
            'No dates',
        );

        throw new ApiException(
            errorCode: 'season_overlap',
            message: sprintf('Season overlap with "%s" (%s).', $name, $range),
            statusCode: 422,
        );
    }

    private function assertSlotDateBoundsInsideRange(int $slotId, string $startDate, string $endDate): void
    {
        $bounds = $this->repository->getSlotDateBounds($slotId);

        if (($bounds['min'] ?? null) !== null && $bounds['min'] < $startDate) {
            throw new ApiException(
                errorCode: 'season_dates_outside_range',
                message: sprintf('Cannot set start date after existing service date %s.', $this->presenter->dateLabel($bounds['min'])),
                statusCode: 422,
            );
        }

        if (($bounds['max'] ?? null) !== null && $bounds['max'] > $endDate) {
            throw new ApiException(
                errorCode: 'season_dates_outside_range',
                message: sprintf('Cannot set end date before existing service date %s.', $this->presenter->dateLabel($bounds['max'])),
                statusCode: 422,
            );
        }
    }

    private function resolveSeasonName(string $name, string $startDate, string $endDate): string
    {
        $name = trim($name);
        if ($name !== '') {
            return $name;
        }

        return SeasonLabel::fromDateRange($startDate, $endDate, '');
    }

    private function assertValidDayMonth(int $month, int $day): void
    {
        if (! checkdate($month, $day, 2024)) {
            throw new ApiException(
                errorCode: 'season_defaults_invalid',
                message: 'Invalid default season dates.',
                statusCode: 422,
            );
        }
    }
}
