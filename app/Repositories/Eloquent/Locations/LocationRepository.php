<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Locations;

use App\DTOs\Locations\LocationData;
use App\DTOs\Locations\LocationFilterData;
use App\Models\Location;
use App\Repositories\Contracts\Locations\LocationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class LocationRepository implements LocationRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Location>
     */
    public function paginate(LocationFilterData $filters): LengthAwarePaginator
    {
        return Location::query()
            ->with('serviceModel')
            ->when($filters->search !== null, function ($query) use ($filters): void {
                $q = '%'.$filters->search.'%';
                $searchId = ctype_digit($filters->search) ? (int) $filters->search : null;

                $query->where(function ($nested) use ($q, $searchId): void {
                    $nested->where('location_from', 'like', $q)
                        ->orWhere('location_to', 'like', $q)
                        ->orWhere('from_time', 'like', $q)
                        ->orWhere('to_time', 'like', $q);

                    if ($searchId !== null) {
                        $nested->orWhere('id', $searchId);
                    }
                });
            })
            ->when($filters->serviceId !== null, fn ($query) => $query->where('service_id', $filters->serviceId))
            ->orderByDesc('id')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    public function create(LocationData $data): Location
    {
        $location = Location::query()->create($data->toPersistence());

        return $location->load('serviceModel');
    }

    public function update(Location $location, LocationData $data): Location
    {
        $location->fill($data->toPersistence());
        $location->save();

        return $location->load('serviceModel');
    }

    public function delete(Location $location): void
    {
        $location->delete();
    }

    public function findWithRelations(int $id): ?Location
    {
        return Location::query()->with('serviceModel')->find($id);
    }
}
