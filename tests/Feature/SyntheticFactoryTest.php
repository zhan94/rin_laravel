<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingDate;
use App\Models\BookingPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SyntheticFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_booked_date_factory_uses_the_same_service_for_the_day_and_pickup(): void
    {
        $link = BookingDate::factory()->create();

        $date = $link->serviceDate()->firstOrFail();
        $period = $date->slot()->firstOrFail();
        $booking = $link->booking()->firstOrFail();
        $pickup = $booking->locationRecord()->firstOrFail();

        self::assertSame($period->getRawOriginal('date_start'), $date->getRawOriginal('slot_date'));
        self::assertSame($period->service_id, $booking->service);
        self::assertSame($period->service_id, $pickup->service_id);
    }

    public function test_booking_payment_factory_mirrors_its_booking(): void
    {
        $booking = Booking::factory()->create(['sum_paid' => 300, 'paid' => 125]);

        $payment = BookingPayment::factory()->create(['booking_id' => $booking->id]);

        self::assertSame('300.00', $payment->sum_paid);
        self::assertSame('125.00', $payment->paid);
    }
}
