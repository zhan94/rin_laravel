<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Concerns\InteractsWithApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Bookings\ApplyBookingPaymentRequest;
use App\Http\Requests\Api\V1\Bookings\ValidateBookingPaymentRequest;
use App\Http\Resources\Api\V1\Bookings\BookingResource;
use App\Models\User;
use App\Services\BookingPaymentService;
use App\Services\BookingService;
use App\Support\Bookings\BookingMutationReceipt;
use App\Support\Pricing\EurCurrency;
use Illuminate\Http\JsonResponse;

final class BookingPaymentController extends Controller
{
    use InteractsWithApiResponses;

    public function __construct(
        private readonly BookingService $bookingService,
        private readonly BookingPaymentService $paymentService,
    ) {}

    public function validateAmount(ValidateBookingPaymentRequest $request, int $booking): JsonResponse
    {
        $model = $this->bookingService->findOrFail($booking);

        return $this->success(
            data: $this->paymentService->validateAmount(
                $model,
                (float) $request->validated('amount'),
            ),
        );
    }

    public function apply(ApplyBookingPaymentRequest $request, int $booking, BookingMutationReceipt $receipts): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $amount = round((float) $request->validated('amount'), 2);
        $mode = (string) $request->validated('mode', 'increment');
        $expectedPaid = $mode === 'set' ? EurCurrency::normalizeAmount($request->validated('expected_paid_eur')) : null;
        $payment = $receipts->run($actor, 'booking.payment', $request->validated('request_key'), [
            'booking_id' => $booking,
            'amount' => $amount,
            'mode' => $mode,
            'expected_paid_eur' => $expectedPaid,
        ], fn (): array => $this->paymentService->apply(
            $booking, $amount, $mode, $actor, $expectedPaid,
        ));

        return $this->success(data: [
            'booking' => (new BookingResource($this->bookingService->findOrFail($booking)))->resolve(),
            'payment' => $payment,
        ]);
    }
}
