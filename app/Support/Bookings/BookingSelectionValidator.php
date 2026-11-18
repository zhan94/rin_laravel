<?php

declare(strict_types=1);

namespace App\Support\Bookings;

use App\DTOs\Bookings\BookingData;
use App\Exceptions\ApiException;
use App\Models\Bonus;
use App\Models\Booking;
use App\Models\Service;
use App\Repositories\Contracts\Bookings\BookingRepositoryInterface;

final readonly class BookingSelectionValidator
{
    public function __construct(
        private BookingRepositoryInterface $repository,
    ) {}

    public function assertCatalogSelection(BookingData $data): void
    {
        if ($data->serviceId <= 0) {
            throw new ApiException(
                errorCode: 'booking_service_required',
                message: 'Service is required.',
                statusCode: 422,
            );
        }

        if ($data->serviceType === Booking::SERVICE_TYPE_SERVICE) {
            $exists = Service::query()->whereKey($data->serviceId)->exists();

            if (! $exists) {
                throw new ApiException(
                    errorCode: 'booking_service_not_found',
                    message: 'Service not found.',
                    statusCode: 404,
                );
            }

            return;
        }

        $exists = Bonus::query()->whereKey($data->serviceId)->exists();

        if (! $exists) {
            throw new ApiException(
                errorCode: 'booking_bonus_not_found',
                message: 'Bonus not found.',
                statusCode: 404,
            );
        }
    }

    public function assertPersistableSelection(BookingData $data): void
    {
        $this->assertCatalogSelection($data);

        if ($data->serviceType === Booking::SERVICE_TYPE_BONUS && $data->slotId > 0) {
            throw new ApiException(
                errorCode: 'booking_slot_not_allowed',
                message: 'Slot selection is allowed only for service bookings.',
                statusCode: 422,
            );
        }

        if ($data->serviceType !== Booking::SERVICE_TYPE_SERVICE) {
            return;
        }

        if ($data->locationId === null || $data->locationId <= 0) {
            throw new ApiException(
                errorCode: 'booking_location_required',
                message: 'Location is required for this booking.',
                statusCode: 422,
            );
        }

        if (! $this->repository->isLocationAllowedForService($data->locationId, $data->serviceId)) {
            throw new ApiException(
                errorCode: 'booking_location_not_allowed',
                message: 'Selected location is not available for this service.',
                statusCode: 422,
            );
        }

        if ($data->dates === []) {
            throw new ApiException(
                errorCode: 'booking_dates_required',
                message: 'At least one booking date is required.',
                statusCode: 422,
            );
        }
    }
}
