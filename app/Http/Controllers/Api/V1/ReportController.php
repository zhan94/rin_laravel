<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Reports\ReportFilterData;
use App\Enums\RoleKey;
use App\Http\Controllers\Api\Concerns\InteractsWithApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Reports\IndexReportRequest;
use App\Http\Resources\Api\V1\Reports\ReportOverviewResource;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

final class ReportController extends Controller
{
    use InteractsWithApiResponses;

    public function __construct(
        private readonly ReportService $service,
    ) {}

    public function index(IndexReportRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $overview = $this->service->overview(ReportFilterData::fromArray(
            $request->validated(),
            isDriver: $user->resolvedRoleKey() === RoleKey::DRIVER,
        ));

        return $this->success(
            data: (new ReportOverviewResource($overview))->resolve(),
            meta: [
                'pagination' => (array) ($overview['pagination'] ?? []),
            ],
        );
    }
}
