<?php

declare(strict_types=1);

use App\Models\SeasonDefault;
use App\Support\Seasons\SeasonCalendar;

test('season calendar falls back to default season boundaries when values are invalid', function () {
    $defaults = new SeasonDefault([
        'start_month' => 2,
        'start_day' => 31,
        'end_month' => 13,
        'end_day' => 1,
    ]);

    expect(SeasonCalendar::normalizeDefaults($defaults))->toBe([
        'start_month' => 9,
        'start_day' => 1,
        'end_month' => 8,
        'end_day' => 31,
    ]);
});

test('season calendar resolves cross year ranges and labels for dates', function () {
    $defaults = SeasonCalendar::defaultDefaults();
    $info = SeasonCalendar::infoForDate(new DateTimeImmutable('2026-12-15'), $defaults);
    $range = SeasonCalendar::rangeForDate(new DateTimeImmutable('2026-04-15'), [
        'start_month' => 1,
        'start_day' => 1,
        'end_month' => 12,
        'end_day' => 31,
    ]);

    expect($info['start']->format('Y-m-d'))->toBe('2026-09-01')
        ->and($info['end']->format('Y-m-d'))->toBe('2027-08-31')
        ->and($info['label'])->toBe('2026/2027')
        ->and($range)->toBe([
            'start' => '2026-01-01',
            'end' => '2026-12-31',
        ]);
});
