<?php

declare(strict_types=1);

namespace App\Repositories\Contracts\Catalog;

use App\DTOs\Catalog\CatalogFilterData;
use App\DTOs\Catalog\EquipData;
use App\Models\Equip;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EquipRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, \App\Models\Equip>
     */
    public function paginate(CatalogFilterData $filters): LengthAwarePaginator;

    public function create(EquipData $data): Equip;

    public function update(Equip $equip, EquipData $data): Equip;

    public function delete(Equip $equip): void;

    public function findWithRelations(int $id): ?Equip;
}
