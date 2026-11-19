<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Booking;
use App\Support\Localization\DisplayDate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class BookingConfirmationMail extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly Booking $booking,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('Booking confirmation #%d', (int) $this->booking->id),
        );
    }

    public function content(): Content
    {
        $customer = $this->booking->customer;
        $serviceModel = $this->booking->serviceModel;

        $customerName = $customer === null
            ? ''
            : $customer->fullName();
        $serviceName = $serviceModel !== null
            ? $serviceModel->displayTitle(fallbackTitle: 'Service')
            : 'Service';

        $dates = $this->booking->bookingDates
            ->map(static fn ($row): string => DisplayDate::german($row->serviceDate?->slot_date, ''))
            ->filter()
            ->implode(', ');

        return new Content(
            view: 'emails.booking-confirmation',
            with: [
                'bookingId' => (int) $this->booking->id,
                'customerName' => $customerName !== '' ? $customerName : 'Customer',
                'serviceName' => $serviceName,
                'dates' => $dates,
                'totalEur' => number_format(((int) $this->booking->sum_paid) / 1.96, 2, '.', ''),
                'paidEur' => number_format(((int) $this->booking->paid) / 1.96, 2, '.', ''),
            ],
        );
    }
}
