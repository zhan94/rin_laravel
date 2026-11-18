<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Jobs\SendBookingConfirmation;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

final class SendBookingEmail implements ShouldHandleEventsAfterCommit
{
    public function handle(BookingCreated $event): void
    {
        SendBookingConfirmation::dispatch((int) $event->booking->id);
    }
}
