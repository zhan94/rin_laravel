<?php

declare(strict_types=1);

namespace App\Support\Bookings;

use App\DTOs\Bookings\BookingData;
use App\Exceptions\ApiException;
use App\Models\Booking;
use App\Repositories\Contracts\Bookings\BookingRepositoryInterface;

final readonly class BookingSkiWorkflow
{
    public function __construct(
        private BookingRepositoryInterface $repository,
    ) {}

    public function resolveNote(Booking $booking, BookingData $data, int $customerId): ?string
    {
        if (! $data->isSkiService()) {
            $this->repository->deleteWinterDetails((int) $booking->id);

            return $data->note;
        }

        $skillId = $this->resolveSkillId($data, $customerId);
        $this->repository->syncWinterDetails($booking, $data, $skillId);

        return $data->composeSkiMetaNote($skillId);
    }

    private function resolveSkillId(BookingData $data, int $customerId): int
    {
        if ($data->skillId !== null && $data->skillId > 0) {
            return $data->skillId;
        }

        $fallbackSkillId = $this->repository->findLatestSkillForCustomerService(
            customerId: $customerId,
            serviceId: $data->serviceId,
            serviceType: $data->serviceType,
        );

        if ($fallbackSkillId !== null && $fallbackSkillId > 0) {
            return $fallbackSkillId;
        }

        throw new ApiException(
            errorCode: 'booking_skill_required',
            message: 'Skill is required for ski services.',
            statusCode: 422,
        );
    }
}
