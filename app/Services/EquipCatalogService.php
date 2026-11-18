<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Catalog\CatalogFilterData;
use App\DTOs\Catalog\EquipData;
use App\Models\Equip;
use App\Models\User;
use App\Repositories\Contracts\Catalog\EquipRepositoryInterface;
use App\Services\Concerns\ManagesCatalogCrud;
use App\Support\Catalog\CatalogCrudDescriptor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class EquipCatalogService
{
    use ManagesCatalogCrud;

    public function __construct(
        private EquipRepositoryInterface $repository,
        private ManagerActionNotificationService $managerActionNotifier,
    ) {}

    /**
     * @return LengthAwarePaginator<int, \App\Models\Equip>
     */
    public function paginate(CatalogFilterData $filters): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }

    public function create(EquipData $data, ?User $actor = null): Equip
    {
        return $this->createCatalogEntity(
            data: $data,
            actor: $actor,
            create: fn (EquipData $data): Equip => $this->repository->create($data),
        );
    }

    public function findOrFail(int $id): Equip
    {
        /** @var Equip $equip */
        $equip = $this->findCatalogEntityOrFail(
            id: $id,
            find: fn (int $id): ?Equip => $this->repository->findWithRelations($id),
        );

        return $equip;
    }

    public function update(Equip $equip, EquipData $data, ?User $actor = null): Equip
    {
        return $this->updateCatalogEntity(
            entity: $equip,
            data: $data,
            actor: $actor,
            update: fn (Equip $equip, EquipData $data): Equip => $this->repository->update($equip, $data),
        );
    }

    public function delete(Equip $equip, ?User $actor = null): void
    {
        $this->deleteCatalogEntity(
            entity: $equip,
            actor: $actor,
            delete: function (Equip $equip): void {
                $this->repository->delete($equip);
            },
        );
    }

    protected function catalogCrudDescriptor(): CatalogCrudDescriptor
    {
        return new CatalogCrudDescriptor(
            moduleKey: 'equips',
            titleEntityLabel: 'Equipment',
            messageEntityLabel: 'equipment',
            entityType: 'equip',
            linkPath: '/crm/equips',
            notFoundErrorCode: 'equip_not_found',
            notFoundMessage: 'Equipment not found.',
        );
    }

    protected function catalogActionNotifier(): ManagerActionNotificationService
    {
        return $this->managerActionNotifier;
    }
}
