<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Logs\LogFilterData;
use App\Http\Controllers\Api\Concerns\InteractsWithApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Logs\DeleteSelectedLogsRequest;
use App\Http\Requests\Api\V1\Logs\IndexLogRequest;
use App\Http\Resources\Api\V1\Logs\LogResource;
use App\Models\User;
use App\Services\LogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LogController extends Controller
{
    use InteractsWithApiResponses;

    public function __construct(
        private readonly LogService $service,
    ) {}

    public function index(IndexLogRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $filters = LogFilterData::fromArray($request->validated());
        $paginator = $this->service->paginate($filters, $user);

        return $this->success(
            data: LogResource::collection($paginator->items())->resolve(),
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

    public function destroy(Request $request, int $log): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->service->deleteOne($log, $user);

        return $this->success(
            data: ['deleted' => true],
        );
    }

    public function deleteSelected(DeleteSelectedLogsRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var list<int> $ids */
        $ids = $request->validated('ids');
        $deleted = $this->service->deleteSelected($ids, $user);

        return $this->success(
            data: [
                'deleted' => $deleted,
            ],
        );
    }

    public function clear(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $deleted = $this->service->deleteAllVisible($user);

        return $this->success(
            data: [
                'deleted' => $deleted,
            ],
        );
    }
}
