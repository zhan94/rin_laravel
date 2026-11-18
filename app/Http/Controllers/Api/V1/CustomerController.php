<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Customers\CustomerData;
use App\DTOs\Customers\CustomerFilterData;
use App\Http\Controllers\Api\Concerns\InteractsWithApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCustomerRequest;
use App\Http\Requests\Api\V1\UpdateCustomerRequest;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Models\User;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CustomerController extends Controller
{
    use InteractsWithApiResponses;

    public function __construct(
        private readonly CustomerService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = CustomerFilterData::fromArray($request->query());
        $paginator = $this->service->paginate($filters);

        return $this->success(
            data: CustomerResource::collection($paginator->items())->resolve(),
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

    public function unpaid(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $search = $query !== '' ? $query : null;

        $overview = $this->service->unpaidOverview($search);

        return $this->success(
            data: $overview['rows'],
            meta: [
                'records' => $overview['records'],
                'total_due' => $overview['total_due'],
                'current_unpaid_season_label' => $overview['current_unpaid_season_label'],
                'unpaid_tabs' => $overview['unpaid_tabs'],
            ],
        );
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $customer = $this->service->create(CustomerData::fromArray($request->validated()), $actor);

        return $this->success(
            data: (new CustomerResource($customer))->resolve(),
            status: JsonResponse::HTTP_CREATED,
        );
    }

    public function show(int $customer): JsonResponse
    {
        $model = $this->service->findOrFail($customer);

        return $this->success(
            data: (new CustomerResource($model))->resolve(),
        );
    }

    public function update(UpdateCustomerRequest $request, int $customer): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $model = $this->service->findOrFail($customer);
        $updated = $this->service->update($model, CustomerData::fromArray($request->validated()), $actor);

        return $this->success(
            data: (new CustomerResource($updated))->resolve(),
        );
    }

    public function destroy(Request $request, int $customer): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $model = $this->service->findOrFail($customer);
        $this->service->delete($model, $actor);

        return $this->success(
            data: ['deleted' => true],
        );
    }
}
