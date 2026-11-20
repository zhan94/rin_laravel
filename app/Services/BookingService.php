<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Bookings\BookingData;
use App\DTOs\Bookings\BookingFilterData;
use App\Events\BookingCreated;
use App\Exceptions\ApiException;
use App\Models\Bonus;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Repositories\Contracts\Bookings\BookingRepositoryInterface;
use App\Support\Auth\StaffAudit;
use App\Support\Bookings\BookingCustomerResolver;
use App\Support\Bookings\BookingSelectionValidator;
use App\Support\Bookings\BookingSkiWorkflow;
use App\Support\Bookings\ServiceBookingDateSynchronizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final readonly class BookingService
{
    public function __construct(
        private BookingRepositoryInterface $repository,
        private ManagerActionNotificationService $managerActionNotifier,
        private BookingCustomerResolver $customerResolver,
        private BookingSelectionValidator $selectionValidator,
        private ServiceBookingDateSynchronizer $dateSynchronizer,
        private BookingSkiWorkflow $skiWorkflow,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Booking>
     */
    public function paginate(BookingFilterData $filters): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }

    public function findOrFail(int $id): Booking
    {
        $booking = $this->repository->findWithRelations($id);

        if ($booking !== null) {
            return $booking;
        }

        throw new ApiException(
            errorCode: 'booking_not_found',
            message: 'Booking not found.',
            statusCode: 404,
        );
    }

    public function create(BookingData $data, ?User $actor = null): Booking
    {
        return DB::transaction(fn (): Booking => $this->persist(null, $data, $actor), 3);
    }

    public function update(
        Booking $booking,
        BookingData $data,
        ?User $actor = null,
        bool $preservePaid = false,
        ?float $expectedPaid = null,
    ): Booking {
        return DB::transaction(function () use ($booking, $data, $actor, $preservePaid, $expectedPaid): Booking {
            $current = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            if ($actor !== null && ! $preservePaid && (float) ($current->paid ?? 0) !== $expectedPaid) {
                throw new ApiException('booking_payment_changed', 'The paid balance changed. Reload the booking and try again.', 409);
            }

            return $this->persist($current, $data, $actor, $preservePaid);
        }, 3);
    }

    public function deactivate(Booking $booking, ?User $actor = null): Booking
    {
        return DB::transaction(function () use ($booking, $actor): Booking {
            $current = Booking::query()->lockForUpdate()->findOrFail($booking->id);
            $deactivated = $this->repository->deactivate($current);

            if ($actor instanceof User) {
                StaffAudit::committed('booking.deactivated', $actor->id, $deactivated->id);
                $label = $deactivated->displayLabel();

                $this->managerActionNotifier->notifyAdminsForManagerEntityAction(
                    actor: $actor,
                    moduleKey: 'booking',
                    actionKey: 'deactivated',
                    actionText: 'deactivated',
                    titleEntityLabel: 'Booking',
                    messageEntityLabel: 'booking',
                    label: $label,
                    entityType: 'booking',
                    entityId: (int) $deactivated->id,
                    linkPath: '/crm/bookings',
                );
            }

            return $deactivated;
        }, 3);
    }

    private function persist(?Booking $existing, BookingData $data, ?User $actor, bool $preservePaid = false): Booking
    {
        // The same catalog row also coordinates slot and season writes.
        if ($data->serviceType === Booking::SERVICE_TYPE_SERVICE) {
            Service::query()->lockForUpdate()->find($data->serviceId);
        } else {
            Bonus::query()->lockForUpdate()->find($data->serviceId);
        }

        $paid = $preservePaid ? $existing?->paid : $data->paid;
        if ($data->sumPaid !== null && $paid !== null && $paid > $data->sumPaid) {
            throw new ApiException('booking_payment_exceeds_remaining', 'Payment exceeds the booking total.', 422);
        }

        $customerId = $this->customerResolver->resolveCustomerId($existing, $data);
        $this->selectionValidator->assertPersistableSelection($data);

        $booking = $existing === null
            ? $this->repository->create($data, $customerId)
            : $this->repository->update($existing, $data, $customerId, $preservePaid);

        if ($data->serviceType === Booking::SERVICE_TYPE_SERVICE) {
            $this->dateSynchronizer->sync($booking, $data, $customerId);
        } else {
            $this->repository->syncBookingDates((int) $booking->id, []);
        }

        $this->repository->syncPaymentTotals(
            (int) $booking->id,
            $booking->sum_paid !== null ? (float) $booking->sum_paid : null,
            $booking->paid !== null ? (float) $booking->paid : null,
        );

        $resolvedNote = $this->skiWorkflow->resolveNote($booking, $data, $customerId);

        if ($booking->note !== $resolvedNote) {
            $booking->note = $resolvedNote;
            $booking->save();
        }

        $result = $this->repository->findWithRelations((int) $booking->id) ?? $booking;

        if ($actor instanceof User) {
            $bookingLabel = $result->displayLabel();
            $actionKey = $existing === null ? 'created' : 'updated';
            StaffAudit::committed('booking.'.$actionKey, $actor->id, $result->id);

            $this->managerActionNotifier->notifyAdminsForManagerEntityAction(
                actor: $actor,
                moduleKey: 'booking',
                actionKey: $actionKey,
                actionText: $actionKey,
                titleEntityLabel: 'Booking',
                messageEntityLabel: 'booking',
                label: $bookingLabel,
                entityType: 'booking',
                entityId: (int) $result->id,
                linkPath: '/crm/bookings',
            );
        }

        if ($existing === null) {
            event(new BookingCreated($result, $actor));
        }

        return $result;
    }
}
