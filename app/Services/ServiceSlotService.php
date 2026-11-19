<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Services\ServiceSlotData;
use App\DTOs\Services\ServiceSlotFilterData;
use App\Exceptions\ApiException;
use App\Models\Service;
use App\Models\ServiceSlot;
use App\Models\User;
use App\Repositories\Contracts\Seasons\SeasonRepositoryInterface;
use App\Repositories\Contracts\Services\ServiceSlotRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final readonly class ServiceSlotService
{
    public function __construct(
        private ServiceSlotRepositoryInterface $repository,
        private SeasonRepositoryInterface $seasons,
        private ManagerActionNotificationService $managerActionNotifier,
    ) {}

    /**
     * @return LengthAwarePaginator<int, ServiceSlot>
     */
    public function paginate(Service $service, ServiceSlotFilterData $filters): LengthAwarePaginator
    {
        return $this->repository->paginateByService($service, $filters);
    }

    public function create(Service $service, ServiceSlotData $data, ?User $actor = null): ServiceSlot
    {
        if ($data->dates === []) {
            throw new ApiException(
                errorCode: 'slot_dates_required',
                message: 'At least one slot date is required.',
                statusCode: 422,
            );
        }

        return DB::transaction(function () use ($service, $data, $actor): ServiceSlot {
            $current = Service::query()->lockForUpdate()->findOrFail($service->id);
            $this->ensureNonOverlappingRange($data);
            $created = $this->repository->create($current, $data);

            if ($actor instanceof User) {
                $slotLabel = $created->displayLabel();
                $serviceLabel = $service->displayTitleOrId();

                $this->managerActionNotifier->notifyAdminsForManagerAction(
                    actor: $actor,
                    moduleKey: 'services',
                    actionKey: 'slot_created',
                    title: sprintf('Service slot created: %s', $slotLabel),
                    message: sprintf(
                        '%s created slot %s for service %s.',
                        $this->managerActionNotifier->actorLabel($actor),
                        $slotLabel,
                        $serviceLabel,
                    ),
                    entityType: 'service_slot',
                    entityId: (int) $created->id,
                    linkPath: '/crm/services',
                );
            }

            return $created;
        }, 3);
    }

    public function findOrFail(int $id): ServiceSlot
    {
        return ServiceSlot::query()->with(['service', 'dates'])->findOr(
            $id,
            fn (): never => throw new ApiException(
                errorCode: 'service_slot_not_found',
                message: 'Service slot not found.',
                statusCode: 404,
            ),
        );
    }

    public function update(ServiceSlot $slot, ServiceSlotData $data, ?User $actor = null): ServiceSlot
    {
        if ($data->dates === []) {
            throw new ApiException(
                errorCode: 'slot_dates_required',
                message: 'At least one slot date is required.',
                statusCode: 422,
            );
        }

        return DB::transaction(function () use ($slot, $data, $actor): ServiceSlot {
            Service::query()->lockForUpdate()->findOrFail($slot->service_id);
            $current = ServiceSlot::query()->findOrFail($slot->id);
            $this->ensureNonOverlappingRange($data, $current);
            $updated = $this->repository->update($current, $data);

            if ($actor instanceof User) {
                $slotLabel = $updated->displayLabel();

                $this->managerActionNotifier->notifyAdminsForManagerEntityAction(
                    actor: $actor,
                    moduleKey: 'services',
                    actionKey: 'slot_updated',
                    actionText: 'updated',
                    titleEntityLabel: 'Service slot',
                    messageEntityLabel: 'slot',
                    label: $slotLabel,
                    entityType: 'service_slot',
                    entityId: (int) $updated->id,
                    linkPath: '/crm/services',
                );
            }

            return $updated;
        }, 3);
    }

    private function ensureNonOverlappingRange(ServiceSlotData $data, ?ServiceSlot $current = null): void
    {
        $start = $data->dateStart();
        $end = $data->dateEnd();

        if ($start === null || $end === null) {
            return;
        }

        // A capacity or label change can retain a range imported from the legacy schedule.
        if ($current !== null
            && substr((string) $current->getRawOriginal('date_start'), 0, 10) === $start
            && substr((string) $current->getRawOriginal('date_end'), 0, 10) === $end) {
            return;
        }

        if ($this->seasons->findOverlappingSlot($data->serviceId, $start, $end, $current?->id) !== null) {
            throw new ApiException(
                errorCode: 'season_overlap',
                message: 'The selected dates overlap another period for this service.',
                statusCode: 422,
            );
        }
    }

    public function delete(ServiceSlot $slot, ?User $actor = null): void
    {
        DB::transaction(function () use ($slot, $actor): void {
            Service::query()->lockForUpdate()->findOrFail($slot->service_id);
            $current = ServiceSlot::query()->findOrFail($slot->id);
            $slotLabel = $current->displayLabel();
            $this->repository->delete($current);

            if ($actor instanceof User) {
                $this->managerActionNotifier->notifyAdminsForManagerEntityAction(
                    actor: $actor,
                    moduleKey: 'services',
                    actionKey: 'slot_deleted',
                    actionText: 'deleted',
                    titleEntityLabel: 'Service slot',
                    messageEntityLabel: 'slot',
                    label: $slotLabel,
                    entityType: 'service_slot',
                    entityId: (int) $slot->id,
                    linkPath: '/crm/services',
                );
            }
        }, 3);
    }
}
