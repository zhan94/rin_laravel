<?php

declare(strict_types=1);

namespace App\Support\Bookings;

use App\DTOs\Bookings\BookingData;
use App\Exceptions\ApiException;
use App\Models\Booking;
use App\Repositories\Contracts\Bookings\BookingRepositoryInterface;
use App\Support\Localization\DisplayDate;

final readonly class ServiceBookingDateSynchronizer
{
    public function __construct(
        private BookingRepositoryInterface $repository,
    ) {}

    public function sync(Booking $booking, BookingData $data, int $customerId): void
    {
        $dateMap = [];

        foreach ($data->dates as $date) {
            $slotId = $this->repository->resolveSlotIdForDate($data->serviceId, $date, $data->slotId);
            $serviceDateId = $this->repository->getOrCreateServiceDateId($slotId, $date);

            $hasConflict = $this->repository->hasCustomerServiceDateConflict(
                bookingId: (int) $booking->id,
                customerId: $customerId,
                serviceId: $data->serviceId,
                serviceType: $data->serviceType,
                slotDate: $date,
            );

            if ($hasConflict) {
                throw new ApiException(
                    errorCode: 'booking_date_conflict',
                    message: sprintf('Customer already has a booking for %s.', DisplayDate::german($date)),
                    statusCode: 422,
                    details: ['date' => $date],
                );
            }

            if (! $this->repository->hasCapacity($serviceDateId, (int) $booking->id)) {
                throw new ApiException(
                    errorCode: 'booking_capacity_exceeded',
                    message: sprintf('No capacity for %s.', DisplayDate::german($date)),
                    statusCode: 422,
                    details: ['date' => $date],
                );
            }

            $dateMap[$date] = $serviceDateId;
        }

        $this->repository->syncBookingDates((int) $booking->id, $dateMap);
    }
}
