<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Services\ServiceSlotData;
use App\DTOs\Services\ServiceSlotFilterData;
use App\Http\Controllers\Api\Concerns\InteractsWithApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Services\StoreServiceSlotRequest;
use App\Http\Requests\Api\V1\Services\UpdateServiceSlotRequest;
use App\Http\Resources\Api\V1\Services\ServiceSlotResource;
use App\Models\User;
use App\Services\ServiceCatalogService;
use App\Services\ServiceSlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ServiceSlotController extends Controller
{
    use InteractsWithApiResponses;

    public function __construct(
        private readonly ServiceCatalogService $catalogService,
        private readonly ServiceSlotService $slotService,
    ) {}

    public function index(Request $request, int $service): JsonResponse
    {
        $serviceModel = $this->catalogService->findOrFail($service);
        $filters = ServiceSlotFilterData::fromArray($request->query());
        $paginator = $this->slotService->paginate($serviceModel, $filters);

        return $this->success(
            data: ServiceSlotResource::collection($paginator->items())->resolve(),
            meta: [
                'service_id' => $serviceModel->id,
                'pagination' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                ],
            ],
        );
    }

    public function store(StoreServiceSlotRequest $request, int $service): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $serviceModel = $this->catalogService->findOrFail($service);
        $created = $this->slotService->create(
            $serviceModel,
            ServiceSlotData::fromArray($request->validated(), $serviceModel->id),
            $actor,
        );

        return $this->success(
            data: (new ServiceSlotResource($created))->resolve(),
            status: JsonResponse::HTTP_CREATED,
        );
    }

    public function show(int $slot): JsonResponse
    {
        $model = $this->slotService->findOrFail($slot);

        return $this->success(
            data: (new ServiceSlotResource($model))->resolve(),
        );
    }

    public function update(UpdateServiceSlotRequest $request, int $slot): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $model = $this->slotService->findOrFail($slot);
        $updated = $this->slotService->update(
            $model,
            ServiceSlotData::fromArray($request->validated(), (int) $model->service_id),
            $actor,
        );

        return $this->success(
            data: (new ServiceSlotResource($updated))->resolve(),
        );
    }

    public function destroy(Request $request, int $slot): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $model = $this->slotService->findOrFail($slot);
        $this->slotService->delete($model, $actor);

        return $this->success(
            data: ['deleted' => true],
        );
    }
}
