<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Catalog;

use App\DTOs\Catalog\CardData;
use App\DTOs\Catalog\CatalogFilterData;
use App\DTOs\Catalog\CatalogPriceData;
use App\Models\Card;
use App\Repositories\Contracts\Catalog\CardRepositoryInterface;
use App\Repositories\Eloquent\Catalog\Concerns\InteractsWithCatalogItems;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class CardRepository implements CardRepositoryInterface
{
    use InteractsWithCatalogItems;

    /**
     * @return LengthAwarePaginator<int, Card>
     */
    public function paginate(CatalogFilterData $filters): LengthAwarePaginator
    {
        return $this->paginateCatalogItems(Card::class, $filters);
    }

    public function create(CardData $data): Card
    {
        $card = Card::query()->create($data->toPersistence());
        $this->replacePrices($card, $data);

        return $card->load(['serviceModel', 'prices']);
    }

    public function update(Card $card, CardData $data): Card
    {
        $card->fill($data->toPersistence());
        $card->save();

        $this->replacePrices($card, $data);

        return $card->load(['serviceModel', 'prices']);
    }

    public function delete(Card $card): void
    {
        $card->delete();
    }

    public function findWithRelations(int $id): ?Card
    {
        /** @var Card|null $card */
        $card = $this->findCatalogItemWithRelations(Card::class, $id);

        return $card;
    }

    private function replacePrices(Card $card, CardData $data): void
    {
        $cardId = (int) $card->id;

        $this->replaceCatalogPriceRows(
            $card->prices(),
            $data->prices,
            fn (CatalogPriceData $price): array => $price->toPersistence('card_id', $cardId),
        );
    }
}
