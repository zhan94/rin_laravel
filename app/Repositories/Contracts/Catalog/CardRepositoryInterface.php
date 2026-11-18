<?php

declare(strict_types=1);

namespace App\Repositories\Contracts\Catalog;

use App\DTOs\Catalog\CardData;
use App\DTOs\Catalog\CatalogFilterData;
use App\Models\Card;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CardRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, \App\Models\Card>
     */
    public function paginate(CatalogFilterData $filters): LengthAwarePaginator;

    public function create(CardData $data): Card;

    public function update(Card $card, CardData $data): Card;

    public function delete(Card $card): void;

    public function findWithRelations(int $id): ?Card;
}
