<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Bonus\BonusData;
use App\DTOs\Bonus\BonusFilterData;
use App\Models\Bonus;
use App\Models\User;
use App\Repositories\Contracts\Bonus\BonusRepositoryInterface;
use App\Services\Concerns\ManagesCatalogCrud;
use App\Support\Catalog\CatalogCrudDescriptor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class BonusCatalogService
{
    use ManagesCatalogCrud;

    public function __construct(
        private BonusRepositoryInterface $repository,
        private ManagerActionNotificationService $managerActionNotifier,
    ) {}

    /**
     * @return LengthAwarePaginator<int, \App\Models\Bonus>
     */
    public function paginate(BonusFilterData $filters): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }

    public function create(BonusData $data, ?User $actor = null): Bonus
    {
        return $this->createCatalogEntity(
            data: $data,
            actor: $actor,
            create: fn (BonusData $data): Bonus => $this->repository->create($data),
        );
    }

    public function findOrFail(int $id): Bonus
    {
        /** @var Bonus $bonus */
        $bonus = $this->findCatalogEntityOrFail(
            id: $id,
            find: fn (int $id): ?Bonus => $this->repository->findWithRelations($id),
        );

        return $bonus;
    }

    public function update(Bonus $bonus, BonusData $data, ?User $actor = null): Bonus
    {
        return $this->updateCatalogEntity(
            entity: $bonus,
            data: $data,
            actor: $actor,
            update: fn (Bonus $bonus, BonusData $data): Bonus => $this->repository->update($bonus, $data),
        );
    }

    public function delete(Bonus $bonus, ?User $actor = null): void
    {
        $this->deleteCatalogEntity(
            entity: $bonus,
            actor: $actor,
            delete: function (Bonus $bonus): void {
                $this->repository->delete($bonus);
            },
        );
    }

    protected function catalogCrudDescriptor(): CatalogCrudDescriptor
    {
        return new CatalogCrudDescriptor(
            moduleKey: 'bonus',
            titleEntityLabel: 'Bonus',
            messageEntityLabel: 'bonus',
            entityType: 'bonus',
            linkPath: '/crm/bonus',
            notFoundErrorCode: 'bonus_not_found',
            notFoundMessage: 'Bonus service not found.',
        );
    }

    protected function catalogActionNotifier(): ManagerActionNotificationService
    {
        return $this->managerActionNotifier;
    }
}
