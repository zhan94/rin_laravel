<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Services\ServiceData;
use App\DTOs\Services\ServiceFilterData;
use App\Exceptions\ApiException;
use App\Models\Service;
use App\Models\User;
use App\Repositories\Contracts\Services\ServiceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final readonly class ServiceCatalogService
{
    public function __construct(
        private ServiceRepositoryInterface $repository,
        private ManagerActionNotificationService $managerActionNotifier,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Service>
     */
    public function paginate(ServiceFilterData $filters): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }

    public function create(ServiceData $data, ?User $actor = null): Service
    {
        return DB::transaction(function () use ($data, $actor): Service {
            $created = $this->repository->create($data);

            if ($actor instanceof User) {
                $label = $created->displayTitleOrId();

                $this->managerActionNotifier->notifyAdminsForManagerEntityAction(
                    actor: $actor,
                    moduleKey: 'services',
                    actionKey: 'created',
                    actionText: 'created',
                    titleEntityLabel: 'Service',
                    messageEntityLabel: 'service',
                    label: $label,
                    entityType: 'service',
                    entityId: (int) $created->id,
                    linkPath: '/crm/services',
                );
            }

            return $created;
        });
    }

    public function findOrFail(int $id): Service
    {
        return Service::query()->withCount('slots')->findOr(
            $id,
            fn (): never => throw new ApiException(
                errorCode: 'service_not_found',
                message: 'Service not found.',
                statusCode: 404,
            ),
        );
    }

    public function update(Service $service, ServiceData $data, ?User $actor = null): Service
    {
        return DB::transaction(function () use ($service, $data, $actor): Service {
            $updated = $this->repository->update($service, $data);

            if ($actor instanceof User) {
                $label = $updated->displayTitleOrId();

                $this->managerActionNotifier->notifyAdminsForManagerEntityAction(
                    actor: $actor,
                    moduleKey: 'services',
                    actionKey: 'updated',
                    actionText: 'updated',
                    titleEntityLabel: 'Service',
                    messageEntityLabel: 'service',
                    label: $label,
                    entityType: 'service',
                    entityId: (int) $updated->id,
                    linkPath: '/crm/services',
                );
            }

            return $updated;
        });
    }

    public function deactivate(Service $service, ?User $actor = null): Service
    {
        if ((int) $service->id > 0 && (int) $service->id <= 4) {
            throw new ApiException(
                errorCode: 'service_protected',
                message: 'Core services cannot be disabled.',
                statusCode: 403,
            );
        }

        return DB::transaction(function () use ($service, $actor): Service {
            $deactivated = $this->repository->deactivate($service);

            if ($actor instanceof User) {
                $label = $deactivated->displayTitleOrId();

                $this->managerActionNotifier->notifyAdminsForManagerEntityAction(
                    actor: $actor,
                    moduleKey: 'services',
                    actionKey: 'deactivated',
                    actionText: 'deactivated',
                    titleEntityLabel: 'Service',
                    messageEntityLabel: 'service',
                    label: $label,
                    entityType: 'service',
                    entityId: (int) $deactivated->id,
                    linkPath: '/crm/services',
                );
            }

            return $deactivated;
        });
    }
}
