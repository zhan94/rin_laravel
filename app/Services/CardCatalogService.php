<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Catalog\CardData;
use App\DTOs\Catalog\CatalogFilterData;
use App\Models\Card;
use App\Models\User;
use App\Repositories\Contracts\Catalog\CardRepositoryInterface;
use App\Services\Concerns\ManagesCatalogCrud;
use App\Support\Catalog\CatalogCrudDescriptor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class CardCatalogService
{
    use ManagesCatalogCrud;

    public function __construct(
        private CardRepositoryInterface $repository,
        private ManagerActionNotificationService $managerActionNotifier,
    ) {}

    /**
     * @return LengthAwarePaginator<int, \App\Models\Card>
     */
    public function paginate(CatalogFilterData $filters): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }

    public function create(CardData $data, ?User $actor = null): Card
    {
        return $this->createCatalogEntity(
            data: $data,
            actor: $actor,
            create: fn (CardData $data): Card => $this->repository->create($data),
        );
    }

    public function findOrFail(int $id): Card
    {
        /** @var Card $card */
        $card = $this->findCatalogEntityOrFail(
            id: $id,
            find: fn (int $id): ?Card => $this->repository->findWithRelations($id),
        );

        return $card;
    }

    public function update(Card $card, CardData $data, ?User $actor = null): Card
    {
        return $this->updateCatalogEntity(
            entity: $card,
            data: $data,
            actor: $actor,
            update: fn (Card $card, CardData $data): Card => $this->repository->update($card, $data),
        );
    }

    public function delete(Card $card, ?User $actor = null): void
    {
        $this->deleteCatalogEntity(
            entity: $card,
            actor: $actor,
            delete: function (Card $card): void {
                $this->repository->delete($card);
            },
        );
    }

    protected function catalogCrudDescriptor(): CatalogCrudDescriptor
    {
        return new CatalogCrudDescriptor(
            moduleKey: 'cards',
            titleEntityLabel: 'Card',
            messageEntityLabel: 'card',
            entityType: 'card',
            linkPath: '/crm/cards',
            notFoundErrorCode: 'card_not_found',
            notFoundMessage: 'Card not found.',
        );
    }

    protected function catalogActionNotifier(): ManagerActionNotificationService
    {
        return $this->managerActionNotifier;
    }
}
