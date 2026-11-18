<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Catalog\CardData;
use App\DTOs\Catalog\CatalogFilterData;
use App\Http\Controllers\Api\Concerns\InteractsWithApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Catalog\StoreCardRequest;
use App\Http\Requests\Api\V1\Catalog\UpdateCardRequest;
use App\Http\Resources\Api\V1\Catalog\CardResource;
use App\Models\User;
use App\Services\CardCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CardController extends Controller
{
    use InteractsWithApiResponses;

    public function __construct(
        private readonly CardCatalogService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = CatalogFilterData::fromArray($request->query());
        $paginator = $this->service->paginate($filters);

        return $this->success(
            data: CardResource::collection($paginator->items())->resolve(),
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

    public function store(StoreCardRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $created = $this->service->create(CardData::fromArray($request->validated()), $actor);

        return $this->success(
            data: (new CardResource($created))->resolve(),
            status: JsonResponse::HTTP_CREATED,
        );
    }

    public function show(int $card): JsonResponse
    {
        $model = $this->service->findOrFail($card);

        return $this->success(
            data: (new CardResource($model))->resolve(),
        );
    }

    public function update(UpdateCardRequest $request, int $card): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $model = $this->service->findOrFail($card);
        $updated = $this->service->update($model, CardData::fromArray($request->validated()), $actor);

        return $this->success(
            data: (new CardResource($updated))->resolve(),
        );
    }

    public function destroy(Request $request, int $card): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $model = $this->service->findOrFail($card);
        $this->service->delete($model, $actor);

        return $this->success(
            data: ['deleted' => true],
        );
    }
}
