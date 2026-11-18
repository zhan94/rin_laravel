<?php

declare(strict_types=1);

use App\Support\Locations\LocationLabel;

test('location label builds routes and optional time windows', function () {
    expect(LocationLabel::route(' Gondola ', ' Hotel '))->toBe('Gondola - Hotel')
        ->and(LocationLabel::route('', 'Hotel', separator: ' -> '))->toBe('Hotel')
        ->and(LocationLabel::route('', '', fallback: 'Unknown'))->toBe('Unknown')
        ->and(LocationLabel::routeWithTimes('Gondola', 'Hotel', '08:00', '16:00'))->toBe('Gondola - Hotel (08:00 - 16:00)')
        ->and(LocationLabel::routeWithTimes('Gondola', '', '08:00', null))->toBe('Gondola (08:00)')
        ->and(LocationLabel::routeWithTimes('', '', null, '16:00', fallback: 'Unknown'))->toBe('Unknown (16:00)');
});
