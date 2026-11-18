<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Catalog\Concerns;

use App\DTOs\Catalog\CatalogFilterData;
use App\Support\Localization\LocalizedText;
use App\Support\Pricing\PriceRowSynchronizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait InteractsWithCatalogItems
{
    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     * @return LengthAwarePaginator<int, TModel>
     */
    private function paginateCatalogItems(string $modelClass, CatalogFilterData $filters): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator<int, TModel> $items */
        $items = $modelClass::query()
            ->with(['serviceModel', 'prices'])
            ->when($filters->search !== null, function ($query) use ($filters): void {
                $q = '%'.$filters->search.'%';
                $searchId = ctype_digit($filters->search) ? (int) $filters->search : null;

                $query->where(function ($nested) use ($q, $searchId): void {
                    $nested->where('title', 'like', $q);

                    foreach (LocalizedText::supportedLocales() as $locale) {
                        $nested->orWhere("title_translations->{$locale}", 'like', $q);
                    }

                    if ($searchId !== null) {
                        $nested->orWhere('id', $searchId);
                    }
                });
            })
            ->when($filters->serviceId !== null, fn ($query) => $query->where('service', $filters->serviceId))
            ->orderByDesc('id')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page);

        return $items;
    }

    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     * @return TModel|null
     */
    private function findCatalogItemWithRelations(string $modelClass, int $id): ?Model
    {
        /** @var TModel|null $item */
        $item = $modelClass::query()
            ->with(['serviceModel', 'prices'])
            ->find($id);

        return $item;
    }

    /**
     * @template TPrice
     * @template TRelatedModel of Model
     * @template TDeclaringModel of Model
     *
     * @param  HasMany<TRelatedModel, TDeclaringModel>  $relation
     * @param  list<TPrice>  $prices
     * @param  callable(TPrice): array<string,mixed>  $toPersistence
     */
    private function replaceCatalogPriceRows(HasMany $relation, array $prices, callable $toPersistence): void
    {
        PriceRowSynchronizer::replace($relation, $prices, $toPersistence);
    }
}
