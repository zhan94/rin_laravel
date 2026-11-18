<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Seasons\SeasonDefaultsData;
use App\DTOs\Seasons\SeasonOverviewFilterData;
use App\DTOs\Seasons\ServiceSeasonData;
use App\Http\Controllers\Api\Concerns\InteractsWithApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Seasons\StoreServiceSeasonRequest;
use App\Http\Requests\Api\V1\Seasons\UpdateSeasonDefaultsRequest;
use App\Http\Requests\Api\V1\Seasons\UpdateServiceSeasonRequest;
use App\Http\Resources\Api\V1\Seasons\SeasonDefaultsResource;
use App\Http\Resources\Api\V1\Seasons\SeasonOverviewResource;
use App\Http\Resources\Api\V1\Seasons\SeasonSummaryResource;
use App\Services\SeasonManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SeasonController extends Controller
{
    use InteractsWithApiResponses;

    public function __construct(
        private readonly SeasonManagementService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $overview = $this->service->overview(SeasonOverviewFilterData::fromArray($request->query()));

        return $this->success(
            data: (new SeasonOverviewResource($overview))->resolve(),
        );
    }

    public function store(StoreServiceSeasonRequest $request): JsonResponse
    {
        $created = $this->service->createServiceSeason(ServiceSeasonData::fromArray($request->validated()));

        return $this->success(
            data: (new SeasonSummaryResource($created))->resolve(),
            status: JsonResponse::HTTP_CREATED,
        );
    }

    public function update(UpdateServiceSeasonRequest $request, int $season): JsonResponse
    {
        $updated = $this->service->updateServiceSeason(
            slotId: $season,
            data: ServiceSeasonData::fromArray($request->validated()),
        );

        return $this->success(
            data: (new SeasonSummaryResource($updated))->resolve(),
        );
    }

    public function destroy(int $season): JsonResponse
    {
        $this->service->deleteServiceSeason($season);

        return $this->success(
            data: ['deleted' => true],
        );
    }

    public function updateDefaults(UpdateSeasonDefaultsRequest $request): JsonResponse
    {
        $defaults = $this->service->updateDefaults(SeasonDefaultsData::fromArray($request->validated()));

        return $this->success(
            data: (new SeasonDefaultsResource($this->service->serializeDefaults($defaults)))->resolve(),
        );
    }
}
