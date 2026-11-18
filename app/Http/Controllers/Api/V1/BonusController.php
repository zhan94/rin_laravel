<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Bonus\BonusData;
use App\DTOs\Bonus\BonusFilterData;
use App\Http\Controllers\Api\Concerns\InteractsWithApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Bonus\StoreBonusRequest;
use App\Http\Requests\Api\V1\Bonus\UpdateBonusRequest;
use App\Http\Resources\Api\V1\Bonus\BonusResource;
use App\Models\User;
use App\Services\BonusCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BonusController extends Controller
{
    use InteractsWithApiResponses;

    public function __construct(
        private readonly BonusCatalogService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = BonusFilterData::fromArray($request->query());
        $paginator = $this->service->paginate($filters);

        return $this->success(
            data: BonusResource::collection($paginator->items())->resolve(),
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

    public function store(StoreBonusRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $created = $this->service->create(BonusData::fromArray($request->validated()), $actor);

        return $this->success(
            data: (new BonusResource($created))->resolve(),
            status: JsonResponse::HTTP_CREATED,
        );
    }

    public function show(int $bonus): JsonResponse
    {
        $model = $this->service->findOrFail($bonus);

        return $this->success(
            data: (new BonusResource($model))->resolve(),
        );
    }

    public function update(UpdateBonusRequest $request, int $bonus): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $model = $this->service->findOrFail($bonus);
        $updated = $this->service->update($model, BonusData::fromArray($request->validated()), $actor);

        return $this->success(
            data: (new BonusResource($updated))->resolve(),
        );
    }

    public function destroy(Request $request, int $bonus): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $model = $this->service->findOrFail($bonus);
        $this->service->delete($model, $actor);

        return $this->success(
            data: ['deleted' => true],
        );
    }
}
