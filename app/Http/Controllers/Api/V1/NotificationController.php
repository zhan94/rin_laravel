<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Notifications\NotificationFilterData;
use App\Http\Controllers\Api\Concerns\InteractsWithApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Notifications\IndexNotificationRequest;
use App\Http\Resources\Api\V1\Notifications\UserNotificationResource;
use App\Models\User;
use App\Services\UserNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificationController extends Controller
{
    use InteractsWithApiResponses;

    public function __construct(
        private readonly UserNotificationService $service,
    ) {}

    public function index(IndexNotificationRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $filters = NotificationFilterData::fromArray($request->validated());
        $result = $this->service->paginate($user, $filters);
        $paginator = $result['paginator'];

        return $this->success(
            data: UserNotificationResource::collection($paginator->items())->resolve(),
            meta: [
                'unread_count' => (int) $result['unread_count'],
                'pagination' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                ],
            ],
        );
    }

    public function read(Request $request, int $notification): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $updated = $this->service->markRead($user, $notification);

        return $this->success(
            data: (new UserNotificationResource($updated->loadMissing('actor')))->resolve(),
        );
    }

    public function readAll(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $updated = $this->service->markAllRead($user);

        return $this->success(
            data: [
                'updated' => $updated,
            ],
        );
    }
}
