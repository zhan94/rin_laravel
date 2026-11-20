<?php

declare(strict_types=1);

namespace App\Repositories\Contracts\Bookings;

use App\DTOs\Bookings\BookingData;
use App\DTOs\Bookings\BookingFilterData;
use App\Models\Booking;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BookingRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, \App\Models\Booking>
     */
    public function paginate(BookingFilterData $filters): LengthAwarePaginator;

    public function findWithRelations(int $id): ?Booking;

    public function create(BookingData $data, int $customerId): Booking;

    public function update(Booking $booking, BookingData $data, int $customerId, bool $preservePaid = false): Booking;

    public function deactivate(Booking $booking): Booking;

    public function isLocationAllowedForService(int $locationId, int $serviceId): bool;

    public function resolveSlotIdForDate(int $serviceId, string $slotDate, int $requestedSlotId = 0): int;

    public function getOrCreateServiceDateId(int $slotId, string $date): int;

    public function hasCustomerServiceDateConflict(
        int $bookingId,
        int $customerId,
        int $serviceId,
        int $serviceType,
        string $slotDate,
    ): bool;

    public function hasCapacity(int $serviceDateId, int $bookingId): bool;

    /**
     * @param  array<string,int>  $dateServiceDateMap
     */
    public function syncBookingDates(int $bookingId, array $dateServiceDateMap): void;

    public function syncPaymentTotals(int $bookingId, ?float $sumPaid, ?float $paid): void;

    public function findLatestSkillForCustomerService(int $customerId, int $serviceId, int $serviceType): ?int;

    public function syncWinterDetails(Booking $booking, BookingData $data, ?int $skillId): void;

    public function deleteWinterDetails(int $bookingId): void;
}
