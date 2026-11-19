<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Service;
use App\Repositories\Contracts\Bookings\BookingSupportRepositoryInterface;
use App\Support\Bookings\BookingSeasonRangeResolver;
use App\Support\Bookings\BookingSupportPresenter;

final readonly class BookingSupportService
{
    public function __construct(
        private BookingSupportRepositoryInterface $repository,
        private BookingSupportPresenter $presenter,
        private BookingSeasonRangeResolver $seasonRangeResolver,
    ) {}

    /**
     * @return list<array<string,mixed>>
     */
    public function autocompleteCustomers(string $query): array
    {
        $rows = $this->repository->searchCustomers($query);

        /** @var list<array<string,mixed>> $customers */
        $customers = array_map(
            fn (array $row): array => $this->presenter->autocompleteCustomer($row),
            $rows,
        );

        return $customers;
    }

    /**
     * @return array<string,mixed>
     */
    public function customerById(int $customerId): array
    {
        $customer = $this->repository->findCustomer($customerId);

        if ($customer === null) {
            throw new ApiException(
                errorCode: 'customer_not_found',
                message: 'Customer not found.',
                statusCode: 404,
            );
        }

        return $this->presenter->customer($customer);
    }

    /**
     * @return array<string,mixed>
     */
    public function serviceOptions(int $serviceId): array
    {
        $this->assertServiceExists($serviceId);

        return [
            'cards' => $this->repository->listCardsByService($serviceId),
            'equips' => $this->repository->listEquipsByService($serviceId),
        ];
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function serviceSlots(int $serviceId): array
    {
        $this->assertServiceExists($serviceId);

        return $this->repository->listServiceSlots($serviceId);
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function serviceDates(int $serviceId, int $slotId = 0): array
    {
        $this->assertServiceExists($serviceId);

        return $this->repository->listServiceDates($serviceId, $slotId);
    }

    /**
     * @return array<string,mixed>
     */
    public function serviceAvailability(int $serviceId, int $customerId = 0, int $slotId = 0): array
    {
        $this->assertServiceExists($serviceId);

        $locations = $this->repository->listLocationsByService($serviceId);
        $disabledDates = $this->repository->listCustomerBookedDates($serviceId, $customerId, $slotId);

        return [
            ...$this->presenter->availability($locations, $disabledDates),
            'full_dates' => $this->repository->listFullServiceDates($serviceId, $slotId),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function bonusContext(int $bonusId): array
    {
        return $this->presenter->bonusContext(
            $this->repository->findBonusContext($bonusId),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function customerServicePrefill(int $customerId, int $serviceId, ?string $selectedDate): array
    {
        if ($customerId <= 0 || $serviceId <= 0) {
            return [
                'found' => false,
                'booking' => null,
            ];
        }

        if (! Service::query()->whereKey($serviceId)->exists()) {
            return [
                'found' => false,
                'booking' => null,
            ];
        }

        $range = $this->seasonRangeResolver->forSelectedDate($selectedDate);

        $booking = $this->repository->findCustomerServicePrefill(
            $customerId,
            $serviceId,
            $range['start'],
            $range['end'],
        );

        if ($booking === null) {
            return $this->presenter->emptyPrefill($range);
        }

        return $this->presenter->prefill($booking, $range, $serviceId);
    }

    private function assertServiceExists(int $serviceId): void
    {
        if ($serviceId <= 0) {
            throw new ApiException(
                errorCode: 'booking_service_required',
                message: 'Service is required.',
                statusCode: 422,
            );
        }

        if (! Service::query()->whereKey($serviceId)->exists()) {
            throw new ApiException(
                errorCode: 'booking_service_not_found',
                message: 'Service not found.',
                statusCode: 404,
            );
        }
    }
}
