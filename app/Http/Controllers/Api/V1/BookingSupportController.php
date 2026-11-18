<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\InteractsWithApiResponses;
use App\Http\Controllers\Controller;
use App\Services\BookingSupportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class BookingSupportController extends Controller
{
    use InteractsWithApiResponses;

    public function __construct(
        private readonly BookingSupportService $service,
    ) {}

    public function autocompleteCustomers(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        return $this->success(
            data: $query === '' ? [] : $this->service->autocompleteCustomers($query),
        );
    }

    public function customer(int $customer): JsonResponse
    {
        return $this->success(
            data: $this->service->customerById($customer),
        );
    }

    public function serviceOptions(int $service): JsonResponse
    {
        return $this->success(
            data: $this->service->serviceOptions($service),
        );
    }

    public function serviceSlots(int $service): JsonResponse
    {
        return $this->success(
            data: $this->service->serviceSlots($service),
        );
    }

    public function serviceDates(Request $request, int $service): JsonResponse
    {
        $slotId = max(0, (int) $request->query('slot_id', 0));

        return $this->success(
            data: $this->service->serviceDates($service, $slotId),
        );
    }

    public function serviceAvailability(Request $request, int $service): JsonResponse
    {
        $customerId = max(0, (int) $request->query('customer_id', 0));
        $slotId = max(0, (int) $request->query('slot_id', 0));

        return $this->success(
            data: $this->service->serviceAvailability($service, $customerId, $slotId),
        );
    }

    public function bonusContext(int $bonus): JsonResponse
    {
        return $this->success(
            data: $this->service->bonusContext($bonus),
        );
    }

    public function prefill(Request $request): JsonResponse
    {
        $customerId = max(0, (int) $request->query('customer_id', 0));
        $serviceId = max(0, (int) $request->query('service_id', 0));
        $selectedDate = $request->query('selected_date');

        return $this->success(
            data: $this->service->customerServicePrefill(
                $customerId,
                $serviceId,
                is_string($selectedDate) ? $selectedDate : null,
            ),
        );
    }
}
