<?php

declare(strict_types=1);

namespace App\Repositories\Contracts\Bonus;

use App\DTOs\Bonus\BonusData;
use App\DTOs\Bonus\BonusFilterData;
use App\Models\Bonus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BonusRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, \App\Models\Bonus>
     */
    public function paginate(BonusFilterData $filters): LengthAwarePaginator;

    public function create(BonusData $data): Bonus;

    public function update(Bonus $bonus, BonusData $data): Bonus;

    public function delete(Bonus $bonus): void;

    public function findWithRelations(int $id): ?Bonus;
}
