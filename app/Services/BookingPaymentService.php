<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Booking;
use App\Models\User;
use App\Repositories\Contracts\Bookings\BookingRepositoryInterface;
use App\Support\Auth\StaffAudit;
use App\Support\Pricing\BgnCurrency;
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
        $amountBgn = $this->eurToBgn($amountEur);
        $sumPaidBgn = $booking->sum_paid !== null ? (int) $booking->sum_paid : null;
        $paidBgn = $booking->paid !== null ? (int) $booking->paid : 0;
        $remainingBeforeBgn = $sumPaidBgn !== null ? max(0, $sumPaidBgn - $paidBgn) : null;

        $valid = $remainingBeforeBgn === null || $amountBgn <= $remainingBeforeBgn;
        $remainingAfterBgn = $remainingBeforeBgn !== null
            ? max(0, $remainingBeforeBgn - $amountBgn)
            : null;

        return [
            'booking_id' => (int) $booking->id,
            'valid' => $valid,
            'amount_eur' => $this->normalizeEur($amountEur),
            'amount_bgn' => $amountBgn,
            'remaining_before_eur' => $this->bgnToEur($remainingBeforeBgn),
            'remaining_before_bgn' => $remainingBeforeBgn,
            'remaining_after_eur' => $this->bgnToEur($remainingAfterBgn),
            'remaining_after_bgn' => $remainingAfterBgn,
            'message' => $valid
                ? 'Payment amount is valid.'
                : 'The amount is greater than the remaining balance.',
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function apply(int $bookingId, float $amountEur, string $mode, User $actor, ?int $expectedPaid): array
    {
        $mode = strtolower(trim($mode));

        if (! in_array($mode, ['increment', 'set'], true)) {
            throw new ApiException(
                errorCode: 'booking_payment_mode_invalid',
                message: 'Payment mode is invalid.',
                statusCode: 422,
            );
        }

        $amountBgn = $this->eurToBgn($amountEur);

        return DB::transaction(function () use ($bookingId, $amountBgn, $amountEur, $mode, $actor, $expectedPaid): array {
            $locked = Booking::query()->lockForUpdate()->find($bookingId);
            if ($locked === null) {
                throw new ApiException('booking_not_found', 'Booking not found.', 404);
            }

            $total = $locked->sum_paid;
            $currentPaid = $locked->paid ?? 0;
            if ($mode === 'set' && $currentPaid !== $expectedPaid) {
                throw new ApiException('booking_payment_changed', 'The paid balance changed. Reload the booking and try again.', 409);
            }

            $nextPaid = $mode === 'set' ? $amountBgn : $currentPaid + $amountBgn;
            if ($nextPaid > 2147483647 || ($total !== null && $nextPaid > $total)) {
                throw new ApiException(
                    errorCode: 'booking_payment_exceeds_remaining',
                    message: 'Payment exceeds remaining balance.',
                    details: ['sum_paid_bgn' => $total, 'paid_bgn' => $currentPaid, 'requested_paid_bgn' => $nextPaid],
                );
            }

            $locked->paid = $nextPaid;
            $locked->save();
            $this->repository->syncPaymentTotals($bookingId, $total, $nextPaid);
            StaffAudit::committed('booking.payment.updated', $actor->id, $bookingId);
            $remaining = $total !== null ? max(0, $total - $nextPaid) : null;
            $paidEur = $this->bgnToEur($nextPaid);
            $bookingLabel = $locked->displayLabel();

            $this->managerActionNotifier->notifyAdminsForManagerAction(
                actor: $actor,
                moduleKey: 'payments',
                actionKey: 'updated',
                title: sprintf('Payment updated for booking %s', $bookingLabel),
                message: sprintf(
                    '%s updated payment for booking %s to %s EUR.',
                    $this->managerActionNotifier->actorLabel($actor),
                    $bookingLabel,
                    $paidEur,
                ),
                entityType: 'booking',
                entityId: $bookingId,
                linkPath: '/crm/bookings',
            );

            return [
                'mode' => $mode,
                'amount_eur' => $this->normalizeEur($amountEur),
                'amount_bgn' => $amountBgn,
                'paid_eur' => $paidEur,
                'paid_bgn' => $nextPaid,
                'sum_paid_eur' => $this->bgnToEur($total),
                'sum_paid_bgn' => $total,
                'remaining_eur' => $this->bgnToEur($remaining),
                'remaining_bgn' => $remaining,
            ];
        }, 3);
    }

    private function eurToBgn(float $amountEur): int
    {
        return BgnCurrency::intFromEurValue($amountEur) ?? 0;
    }

    private function bgnToEur(?int $amountBgn): ?float
    {
        return BgnCurrency::toEur($amountBgn);
    }

    private function normalizeEur(float $amountEur): float
    {
        return round(max(0, $amountEur), 2);
    }
}
