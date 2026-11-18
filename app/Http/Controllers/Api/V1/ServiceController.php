<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Services\ServiceData;
use App\DTOs\Services\ServiceFilterData;
use App\Http\Controllers\Api\Concerns\InteractsWithApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Services\StoreServiceRequest;
use App\Http\Requests\Api\V1\Services\UpdateServiceRequest;
use App\Http\Resources\Api\V1\Services\ServiceResource;
use App\Models\User;
use App\Services\ServiceCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ServiceController extends Controller
{
    use InteractsWithApiResponses;

    public function __construct(
        private readonly ServiceCatalogService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = ServiceFilterData::fromArray($request->query());
        $paginator = $this->service->paginate($filters);

        return $this->success(
            data: ServiceResource::collection($paginator->items())->resolve(),
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

    public function store(StoreServiceRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $created = $this->service->create(ServiceData::fromArray($request->validated()), $actor);

        return $this->success(
            data: (new ServiceResource($created->loadCount('slots')))->resolve(),
            status: JsonResponse::HTTP_CREATED,
        );
    }

    public function show(int $service): JsonResponse
    {
        $model = $this->service->findOrFail($service);

        return $this->success(
            data: (new ServiceResource($model))->resolve(),
        );
    }

    public function update(UpdateServiceRequest $request, int $service): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $model = $this->service->findOrFail($service);
        $updated = $this->service->update($model, ServiceData::fromArray($request->validated()), $actor);

        return $this->success(
            data: (new ServiceResource($updated->loadCount('slots')))->resolve(),
        );
    }

    public function destroy(Request $request, int $service): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $model = $this->service->findOrFail($service);
        $deactivated = $this->service->deactivate($model, $actor);

        return $this->success(
            data: (new ServiceResource($deactivated->loadCount('slots')))->resolve(),
        );
    }
}
