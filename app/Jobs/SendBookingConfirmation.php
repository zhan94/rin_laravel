<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\BookingConfirmationMail;
use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

final class SendBookingConfirmation implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $bookingId,
    ) {}

    public function handle(): void
    {
        $booking = Booking::query()
            ->with([
                'customer',
                'serviceModel',
                'locationRecord',
                'bookingDates.serviceDate',
            ])
            ->find($this->bookingId);

        if (! $booking instanceof Booking || ! $booking->customer instanceof \App\Models\Customer) {
            return;
        }

        $recipient = trim((string) ($booking->customer->email ?? ''));
        if ($recipient === '') {
            return;
        }

        Mail::to($recipient)->send(new BookingConfirmationMail($booking));
    }
}
