<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Locations\LocationData;
use App\DTOs\Locations\LocationFilterData;
use App\Exceptions\ApiException;
use App\Models\Location;
use App\Models\Service;
use App\Models\User;
use App\Repositories\Contracts\Locations\LocationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final readonly class LocationCatalogService
{
    public function __construct(
        private LocationRepositoryInterface $repository,
        private ManagerActionNotificationService $managerActionNotifier,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Location>
     */
    public function paginate(LocationFilterData $filters): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }

    public function create(LocationData $data, ?User $actor = null): Location
    {
        $this->assertServiceExists($data->serviceId);

        return DB::transaction(function () use ($data, $actor): Location {
            $created = $this->repository->create($data);

            if ($actor instanceof User) {
                $label = $created->displayLabel(separator: ' -> ');

                $this->managerActionNotifier->notifyAdminsForManagerEntityAction(
                    actor: $actor,
                    moduleKey: 'locations',
                    actionKey: 'created',
                    actionText: 'created',
                    titleEntityLabel: 'Location',
                    messageEntityLabel: 'location',
                    label: $label,
                    entityType: 'location',
                    entityId: (int) $created->id,
                    linkPath: '/crm/locations',
                );
            }

            return $created;
        });
    }

    public function findOrFail(int $id): Location
    {
        $location = $this->repository->findWithRelations($id);

        if ($location !== null) {
            return $location;
        }

        throw new ApiException(
            errorCode: 'location_not_found',
            message: 'Location not found.',
            statusCode: 404,
        );
    }

    public function update(Location $location, LocationData $data, ?User $actor = null): Location
    {
        $this->assertServiceExists($data->serviceId);

        return DB::transaction(function () use ($location, $data, $actor): Location {
            $updated = $this->repository->update($location, $data);

            if ($actor instanceof User) {
                $label = $updated->displayLabel(separator: ' -> ');

                $this->managerActionNotifier->notifyAdminsForManagerEntityAction(
                    actor: $actor,
                    moduleKey: 'locations',
                    actionKey: 'updated',
                    actionText: 'updated',
                    titleEntityLabel: 'Location',
                    messageEntityLabel: 'location',
                    label: $label,
                    entityType: 'location',
                    entityId: (int) $updated->id,
                    linkPath: '/crm/locations',
                );
            }

            return $updated;
        });
    }

    public function delete(Location $location, ?User $actor = null): void
    {
        DB::transaction(function () use ($location, $actor): void {
            $label = $location->displayLabel(separator: ' -> ');

            $this->repository->delete($location);

            if ($actor instanceof User) {
                $this->managerActionNotifier->notifyAdminsForManagerEntityAction(
                    actor: $actor,
                    moduleKey: 'locations',
                    actionKey: 'deleted',
                    actionText: 'deleted',
                    titleEntityLabel: 'Location',
                    messageEntityLabel: 'location',
                    label: $label,
                    entityType: 'location',
                    entityId: (int) $location->id,
                    linkPath: '/crm/locations',
                );
            }
        });
    }

    private function assertServiceExists(int $serviceId): void
    {
        if ($serviceId <= 0) {
            return;
        }

        if (! Service::query()->whereKey($serviceId)->exists()) {
            throw new ApiException(
                errorCode: 'location_service_not_found',
                message: 'Selected service does not exist.',
                statusCode: 422,
            );
        }
    }
}
