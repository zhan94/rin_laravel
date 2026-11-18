<?php

declare(strict_types=1);

use App\DTOs\Bookings\BookingData;
use App\Events\BookingCreated;
use App\Models\Booking;
use App\Models\Location;
use App\Models\Service;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('creates booking records with customer, dates, and payment totals', function (): void {
    Event::fake([BookingCreated::class]);

    $service = app(BookingService::class);
    $catalogService = Service::factory()->create(['max' => 4]);
    $location = Location::factory()->create(['service_id' => 0]);

    $payload = BookingData::fromArray([
        'firstname' => 'Test',
        'surname' => 'Middle',
        'lastname' => 'Client',
        'parent_name' => 'Parent Client',
        'parent_phone' => '0888123456',
        'email' => 'test.client@example.com',
        'service_type' => Booking::SERVICE_TYPE_SERVICE,
        'service_id' => $catalogService->id,
        'location_id' => $location->id,
        'dates' => ['2026-02-22', '2026-02-21'],
        'sum_paid' => 100,
        'paid' => 30,
        'skill_id' => 1,
    ]);

    $booking = $service->create($payload);

    expect($booking->id)->toBeInt()
        ->and((int) $booking->service)->toBe((int) $catalogService->id)
        ->and((int) $booking->location)->toBe((int) $location->id)
        ->and((int) $booking->sum_paid)->toBe(196)
        ->and((int) $booking->paid)->toBe(59)
        ->and($booking->customer)->not()->toBeNull()
        ->and($booking->bookingDates)->toHaveCount(2);

    Event::assertDispatched(BookingCreated::class);
});

it('updates an existing booking and keeps relations synchronized', function (): void {
    $service = app(BookingService::class);
    $catalogService = Service::factory()->create(['max' => 4]);
    $location = Location::factory()->create(['service_id' => 0]);

    $created = $service->create(BookingData::fromArray([
        'firstname' => 'First',
        'lastname' => 'State',
        'service_type' => Booking::SERVICE_TYPE_SERVICE,
        'service_id' => $catalogService->id,
        'location_id' => $location->id,
        'dates' => ['2026-03-01'],
        'sum_paid' => 50,
        'paid' => 10,
        'skill_id' => 1,
    ]));

    $updated = $service->update($created, BookingData::fromArray([
        'customer_id' => $created->customer_id,
        'service_type' => Booking::SERVICE_TYPE_SERVICE,
        'service_id' => $catalogService->id,
        'location_id' => $location->id,
        'dates' => ['2026-03-02', '2026-03-03'],
        'sum_paid' => 60,
        'paid' => 20,
        'skill_id' => 1,
    ]));

    expect((int) $updated->id)->toBe((int) $created->id)
        ->and((int) $updated->sum_paid)->toBe(117)
        ->and((int) $updated->paid)->toBe(39)
        ->and($updated->bookingDates)->toHaveCount(2);
});

it('deactivates bookings through service layer', function (): void {
    $service = app(BookingService::class);
    $catalogService = Service::factory()->create(['max' => 4]);
    $location = Location::factory()->create(['service_id' => 0]);

    $created = $service->create(BookingData::fromArray([
        'firstname' => 'Deactivate',
        'lastname' => 'Me',
        'service_type' => Booking::SERVICE_TYPE_SERVICE,
        'service_id' => $catalogService->id,
        'location_id' => $location->id,
        'dates' => ['2026-04-01'],
        'skill_id' => 1,
    ]));

    $deactivated = $service->deactivate($created);

    expect((bool) $deactivated->active)->toBeFalse();
});
