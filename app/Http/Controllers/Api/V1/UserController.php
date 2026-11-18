<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Users\UserData;
use App\DTOs\Users\UserFilterData;
use App\Http\Controllers\Api\Concerns\InteractsWithApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreUserRequest;
use App\Http\Requests\Api\V1\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class UserController extends Controller
{
    use InteractsWithApiResponses;

    public function __construct(
        private readonly UserService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', User::class);

        $filters = UserFilterData::fromArray($request->query());
        $paginator = $this->service->paginate($filters);

        return $this->success(
            data: UserResource::collection($paginator->items())->resolve(),
            meta: [
                'pagination' => [
                    'total' => $paginator->total(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                ],
                'roles' => $this->service->roleOptions($request->user()),
            ],
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $user = $this->service->create(UserData::fromArray($request->validated()), $actor);

        return $this->success(
            data: (new UserResource($user->loadMissing('role')))->resolve(),
            status: JsonResponse::HTTP_CREATED,
        );
    }

    public function show(User $user): JsonResponse
    {
        Gate::authorize('view', $user);

        return $this->success(
            data: (new UserResource($user->loadMissing('role')))->resolve(),
        );
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $updated = $this->service->update($user, UserData::fromArray($request->validated()), $actor);

        return $this->success(
            data: (new UserResource($updated->loadMissing('role')))->resolve(),
        );
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $this->service->delete($user, $actor);

        return $this->success(
            data: ['deleted' => true],
        );
    }
}
