<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Locations\LocationData;
use App\DTOs\Locations\LocationFilterData;
use App\Http\Controllers\Api\Concerns\InteractsWithApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Locations\StoreLocationRequest;
use App\Http\Requests\Api\V1\Locations\UpdateLocationRequest;
use App\Http\Resources\Api\V1\Locations\LocationResource;
use App\Models\User;
use App\Services\LocationCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LocationController extends Controller
{
    use InteractsWithApiResponses;

    public function __construct(
        private readonly LocationCatalogService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = LocationFilterData::fromArray($request->query());
        $paginator = $this->service->paginate($filters);

        return $this->success(
            data: LocationResource::collection($paginator->items())->resolve(),
            meta: [
                'pagination' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                ],
            ],
        );
    }

    public function store(StoreLocationRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $created = $this->service->create(LocationData::fromArray($request->validated()), $actor);

        return $this->success(
            data: (new LocationResource($created))->resolve(),
            status: JsonResponse::HTTP_CREATED,
        );
    }

    public function show(int $location): JsonResponse
    {
        $model = $this->service->findOrFail($location);

        return $this->success(
            data: (new LocationResource($model))->resolve(),
        );
    }

    public function update(UpdateLocationRequest $request, int $location): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $model = $this->service->findOrFail($location);
        $updated = $this->service->update($model, LocationData::fromArray($request->validated()), $actor);

        return $this->success(
            data: (new LocationResource($updated))->resolve(),
        );
    }

    public function destroy(Request $request, int $location): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $model = $this->service->findOrFail($location);
        $this->service->delete($model, $actor);

        return $this->success(
            data: ['deleted' => true],
        );
    }
}
