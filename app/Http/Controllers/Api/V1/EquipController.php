<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Catalog\CatalogFilterData;
use App\DTOs\Catalog\EquipData;
use App\Http\Controllers\Api\Concerns\InteractsWithApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Catalog\StoreEquipRequest;
use App\Http\Requests\Api\V1\Catalog\UpdateEquipRequest;
use App\Http\Resources\Api\V1\Catalog\EquipResource;
use App\Models\User;
use App\Services\EquipCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class EquipController extends Controller
{
    use InteractsWithApiResponses;

    public function __construct(
        private readonly EquipCatalogService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = CatalogFilterData::fromArray($request->query());
        $paginator = $this->service->paginate($filters);

        return $this->success(
            data: EquipResource::collection($paginator->items())->resolve(),
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

    public function store(StoreEquipRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $created = $this->service->create(EquipData::fromArray($request->validated()), $actor);

        return $this->success(
            data: (new EquipResource($created))->resolve(),
            status: JsonResponse::HTTP_CREATED,
        );
    }

    public function show(int $equip): JsonResponse
    {
        $model = $this->service->findOrFail($equip);

        return $this->success(
            data: (new EquipResource($model))->resolve(),
        );
    }

    public function update(UpdateEquipRequest $request, int $equip): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $model = $this->service->findOrFail($equip);
        $updated = $this->service->update($model, EquipData::fromArray($request->validated()), $actor);

        return $this->success(
            data: (new EquipResource($updated))->resolve(),
        );
    }

    public function destroy(Request $request, int $equip): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $model = $this->service->findOrFail($equip);
        $this->service->delete($model, $actor);

        return $this->success(
            data: ['deleted' => true],
        );
    }
}
