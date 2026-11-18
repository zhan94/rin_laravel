<?php

declare(strict_types=1);

namespace App\Repositories\Contracts\Locations;

use App\DTOs\Locations\LocationData;
use App\DTOs\Locations\LocationFilterData;
use App\Models\Location;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LocationRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, \App\Models\Location>
     */
    public function paginate(LocationFilterData $filters): LengthAwarePaginator;

    public function create(LocationData $data): Location;

    public function update(Location $location, LocationData $data): Location;

    public function delete(Location $location): void;

    public function findWithRelations(int $id): ?Location;
}
