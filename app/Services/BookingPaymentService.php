<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Booking;
use App\Models\User;
use App\Repositories\Contracts\Bookings\BookingRepositoryInterface;
use App\Support\Auth\StaffAudit;
use App\Support\Pricing\EurCurrency;
use Illuminate\Support\Facades\DB;

final readonly class BookingPaymentService
{
    public function __construct(
        private BookingRepositoryInterface $repository,
        private ManagerActionNotificationService $managerActionNotifier,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function validateAmount(Booking $booking, float $amountEur): array
    {
        $amountEur = EurCurrency::normalizeAmount($amountEur) ?? 0.0;
        $remaining = $booking->sum_paid !== null
            ? EurCurrency::balance((float) $booking->sum_paid, (float) $booking->paid)
            : null;
        $valid = $remaining === null || $amountEur <= $remaining;

        return [
            'booking_id' => (int) $booking->id,
            'valid' => $valid,
            'amount_eur' => $amountEur,
            'remaining_before_eur' => $remaining,
            'remaining_after_eur' => $remaining !== null ? EurCurrency::balance($remaining, $amountEur) : null,
            'message' => $valid
                ? 'Payment amount is valid.'
                : 'The amount is greater than the remaining balance.',
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function apply(int $bookingId, float $amountEur, string $mode, User $actor, ?float $expectedPaid): array
    {
        $mode = strtolower(trim($mode));

        if (! in_array($mode, ['increment', 'set'], true)) {
            throw new ApiException(
                errorCode: 'booking_payment_mode_invalid',
                message: 'Payment mode is invalid.',
                statusCode: 422,
            );
        }

        $amountEur = EurCurrency::normalizeAmount($amountEur) ?? 0.0;

        return DB::transaction(function () use ($bookingId, $amountEur, $mode, $actor, $expectedPaid): array {
            $locked = Booking::query()->lockForUpdate()->find($bookingId);
            if ($locked === null) {
                throw new ApiException('booking_not_found', 'Booking not found.', 404);
            }

            $total = $locked->sum_paid !== null ? (float) $locked->sum_paid : null;
            $currentPaid = (float) ($locked->paid ?? 0);
            if ($mode === 'set' && $currentPaid !== $expectedPaid) {
                throw new ApiException('booking_payment_changed', 'The paid balance changed. Reload the booking and try again.', 409);
            }

            $nextPaid = $mode === 'set' ? $amountEur : round($currentPaid + $amountEur, 2);
            if ($nextPaid > EurCurrency::MAX_BOOKING_AMOUNT || ($total !== null && $nextPaid > $total)) {
                throw new ApiException(
                    errorCode: 'booking_payment_exceeds_remaining',
                    message: 'Payment exceeds remaining balance.',
                    details: ['sum_paid_eur' => $total, 'paid_eur' => $currentPaid, 'requested_paid_eur' => $nextPaid],
                );
            }

            $locked->update(['paid' => $nextPaid]);
            $this->repository->syncPaymentTotals($bookingId, $total, $nextPaid);
            StaffAudit::committed('booking.payment.updated', $actor->id, $bookingId);
            $bookingLabel = $locked->displayLabel();

            $this->managerActionNotifier->notifyAdminsForManagerAction(
                actor: $actor,
                moduleKey: 'payments',
                actionKey: 'updated',
                title: sprintf('Payment updated for booking %s', $bookingLabel),
                message: sprintf(
                    '%s updated payment for booking %s to %.2F EUR.',
                    $this->managerActionNotifier->actorLabel($actor),
                    $bookingLabel,
                    $nextPaid,
                ),
                entityType: 'booking',
                entityId: $bookingId,
                linkPath: '/crm/bookings',
            );

            return [
                'mode' => $mode,
                'amount_eur' => $amountEur,
                'paid_eur' => $nextPaid,
                'sum_paid_eur' => $total,
                'remaining_eur' => $total !== null ? EurCurrency::balance($total, $nextPaid) : null,
            ];
        }, 3);
    }
}
