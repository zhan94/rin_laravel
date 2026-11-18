<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Bookings\BookingData;
use App\DTOs\Bookings\BookingFilterData;
use App\Http\Controllers\Api\Concerns\InteractsWithApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Bookings\StoreBookingRequest;
use App\Http\Requests\Api\V1\Bookings\UpdateBookingRequest;
use App\Http\Resources\Api\V1\Bookings\BookingResource;
use App\Models\User;
use App\Services\BookingService;
use App\Support\Bookings\BookingMutationReceipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BookingController extends Controller
{
    use InteractsWithApiResponses;

    public function __construct(
        private readonly BookingService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = BookingFilterData::fromArray($request->query());
        $paginator = $this->service->paginate($filters);

        return $this->success(
            data: BookingResource::collection($paginator->items())->resolve(),
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

    public function store(StoreBookingRequest $request, BookingMutationReceipt $receipts): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $command = BookingData::fromArray($request->validated());
        $result = $receipts->run($actor, 'booking.create', $request->validated('request_key'), get_object_vars($command),
            fn (): array => ['booking_id' => $this->service->create($command, $actor)->id],
        );
        $created = $this->service->findOrFail($result['booking_id']);

        return $this->success(
            data: (new BookingResource($created))->resolve(),
            status: JsonResponse::HTTP_CREATED,
        );
    }

    public function show(int $booking): JsonResponse
    {
        $model = $this->service->findOrFail($booking);

        return $this->success(
            data: (new BookingResource($model))->resolve(),
        );
    }

    public function update(UpdateBookingRequest $request, int $booking): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $model = $this->service->findOrFail($booking);
        $updated = $this->service->update($model, BookingData::fromArray($request->validated()), $actor,
            preservePaid: ! array_key_exists('paid', $request->validated()),
            expectedPaid: $request->has('expected_paid_bgn') ? $request->integer('expected_paid_bgn') : null,
        );

        return $this->success(
            data: (new BookingResource($updated))->resolve(),
        );
    }

    public function destroy(Request $request, int $booking): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $model = $this->service->findOrFail($booking);
        $deactivated = $this->service->deactivate($model, $actor);

        return $this->success(
            data: (new BookingResource($deactivated))->resolve(),
        );
    }
}
