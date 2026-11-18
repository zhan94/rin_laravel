<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Location;
use App\Models\ServiceSlot;
use App\Models\User;

test('models expose shared display labels with id fallbacks', function () {
    $user = new User(['username' => '']);
    $user->id = 7;

    $customer = new Customer([
        'firstname' => '',
        'lastname' => '',
    ]);
    $customer->id = 12;

    $slot = new ServiceSlot(['name' => '']);
    $slot->id = 18;

    $location = new Location([
        'location_from' => '',
        'location_to' => '',
    ]);
    $location->id = 21;

    $booking = new Booking;
    $booking->id = 33;

    expect($user->displayLabel())->toBe('#7')
        ->and($customer->displayLabel())->toBe('#12')
        ->and($slot->displayLabel())->toBe('#18')
        ->and($location->displayLabel())->toBe('Location #21')
        ->and($location->displayLabel(' -> '))->toBe('Location #21')
        ->and($booking->displayLabel())->toBe('#33');
});

test('model display labels prefer human friendly values when present', function () {
    $user = new User(['username' => 'manager']);
    $user->id = 7;

    $customer = new Customer([
        'firstname' => 'Ani',
        'surname' => 'M.',
        'lastname' => 'Ivanova',
    ]);
    $customer->id = 12;

    $slot = new ServiceSlot([
        'name' => '2026/2027',
    ]);
    $slot->id = 18;

    $location = new Location([
        'location_from' => 'Hotel',
        'location_to' => 'Lift',
    ]);
    $location->id = 21;

    expect($user->displayLabel())->toBe('manager')
        ->and($customer->displayLabel())->toBe('Ani M. Ivanova')
        ->and($slot->displayLabel())->toBe('2026/2027')
        ->and($location->displayLabel())->toBe('Hotel - Lift')
        ->and($location->displayLabel(' -> '))->toBe('Hotel -> Lift');
});
