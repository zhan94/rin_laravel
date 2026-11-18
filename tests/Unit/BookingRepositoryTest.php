<?php

declare(strict_types=1);

use App\DTOs\Bookings\BookingData;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Service;
use App\Models\ServiceDate;
use App\Models\ServiceSlot;
use App\Repositories\Contracts\Bookings\BookingRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('validates location availability for service and shared locations', function (): void {
    $repository = app(BookingRepositoryInterface::class);
    $service = Service::factory()->create();
    $otherService = Service::factory()->create();

    $sharedLocation = Location::factory()->create(['service_id' => 0]);
    $serviceLocation = Location::factory()->create(['service_id' => $service->id]);
    $otherLocation = Location::factory()->create(['service_id' => $otherService->id]);

    expect($repository->isLocationAllowedForService((int) $sharedLocation->id, (int) $service->id))->toBeTrue()
        ->and($repository->isLocationAllowedForService((int) $serviceLocation->id, (int) $service->id))->toBeTrue()
        ->and($repository->isLocationAllowedForService((int) $otherLocation->id, (int) $service->id))->toBeFalse();
});

it('synchronizes booking dates by adding and pruning stale rows', function (): void {
    $repository = app(BookingRepositoryInterface::class);
    $service = Service::factory()->create();
    $customer = Customer::factory()->create();
    $location = Location::factory()->create(['service_id' => 0]);
    $slot = ServiceSlot::factory()->create(['service_id' => $service->id]);

    $dateA = ServiceDate::factory()->create(['slot_id' => $slot->id, 'slot_date' => '2026-02-10']);
    $dateB = ServiceDate::factory()->create(['slot_id' => $slot->id, 'slot_date' => '2026-02-11']);
    $dateC = ServiceDate::factory()->create(['slot_id' => $slot->id, 'slot_date' => '2026-02-12']);

    $booking = Booking::factory()->create([
        'customer_id' => $customer->id,
        'service' => $service->id,
        'service_type' => Booking::SERVICE_TYPE_SERVICE,
        'location' => $location->id,
    ]);

    $repository->syncBookingDates((int) $booking->id, [
        '2026-02-10' => (int) $dateA->id,
        '2026-02-11' => (int) $dateB->id,
    ]);

    $booking->refresh();
    expect($booking->bookingDates()->count())->toBe(2);

    $repository->syncBookingDates((int) $booking->id, [
        '2026-02-12' => (int) $dateC->id,
    ]);

    $booking->refresh();
    expect($booking->bookingDates()->count())->toBe(1)
        ->and((int) $booking->bookingDates()->firstOrFail()->service_date_id)->toBe((int) $dateC->id);
});

it('creates and updates booking payment totals', function (): void {
    $repository = app(BookingRepositoryInterface::class);
    $service = Service::factory()->create();
    $customer = Customer::factory()->create();
    $location = Location::factory()->create(['service_id' => 0]);

    $booking = Booking::factory()->create([
        'customer_id' => $customer->id,
        'service' => $service->id,
        'service_type' => Booking::SERVICE_TYPE_SERVICE,
        'location' => $location->id,
    ]);

    $repository->syncPaymentTotals((int) $booking->id, 300, 140);
    $repository->syncPaymentTotals((int) $booking->id, 400, 220);

    $booking->refresh();
    $payment = $booking->payment;

    expect($payment)->not()->toBeNull();

    if ($payment === null) {
        return;
    }

    expect((int) $payment->sum_paid)->toBe(400)
        ->and((int) $payment->paid)->toBe(220);
});

it('creates and deactivates bookings through repository persistence', function (): void {
    $repository = app(BookingRepositoryInterface::class);
    $service = Service::factory()->create();
    $customer = Customer::factory()->create();
    $location = Location::factory()->create(['service_id' => 0]);

    $data = BookingData::fromArray([
        'customer_id' => $customer->id,
        'service_type' => Booking::SERVICE_TYPE_SERVICE,
        'service_id' => $service->id,
        'location_id' => $location->id,
        'approved' => true,
        'lunch' => false,
    ]);

    $booking = $repository->create($data, (int) $customer->id);
    $deactivated = $repository->deactivate($booking);

    expect((bool) $deactivated->active)->toBeFalse();
});
