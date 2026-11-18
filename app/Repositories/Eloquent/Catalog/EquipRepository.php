<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Catalog;

use App\DTOs\Catalog\CatalogFilterData;
use App\DTOs\Catalog\CatalogPriceData;
use App\DTOs\Catalog\EquipData;
use App\Models\Equip;
use App\Repositories\Contracts\Catalog\EquipRepositoryInterface;
use App\Repositories\Eloquent\Catalog\Concerns\InteractsWithCatalogItems;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EquipRepository implements EquipRepositoryInterface
{
    use InteractsWithCatalogItems;

    /**
     * @return LengthAwarePaginator<int, Equip>
     */
    public function paginate(CatalogFilterData $filters): LengthAwarePaginator
    {
        return $this->paginateCatalogItems(Equip::class, $filters);
    }

    public function create(EquipData $data): Equip
    {
        $equip = Equip::query()->create($data->toPersistence());
        $this->replacePrices($equip, $data);

        return $equip->load(['serviceModel', 'prices']);
    }

    public function update(Equip $equip, EquipData $data): Equip
    {
        $equip->fill($data->toPersistence());
        $equip->save();

        $this->replacePrices($equip, $data);

        return $equip->load(['serviceModel', 'prices']);
    }

    public function delete(Equip $equip): void
    {
        $equip->delete();
    }

    public function findWithRelations(int $id): ?Equip
    {
        /** @var Equip|null $equip */
        $equip = $this->findCatalogItemWithRelations(Equip::class, $id);

        return $equip;
    }

    private function replacePrices(Equip $equip, EquipData $data): void
    {
        $equipId = (int) $equip->id;

        $this->replaceCatalogPriceRows(
            $equip->prices(),
            $data->prices,
            fn (CatalogPriceData $price): array => $price->toPersistence('equip_id', $equipId),
        );
    }
}
