<?php

declare(strict_types=1);

use App\Support\Seasons\SeasonLabel;

test('season label helper builds year ranges from years and dates', function () {
    expect(SeasonLabel::fromYears(2026, 2026))->toBe('2026')
        ->and(SeasonLabel::fromYears(2026, 2027))->toBe('2026/2027')
        ->and(SeasonLabel::fromDateRange('2026-09-01', '2027-08-31'))->toBe('2026/2027')
        ->and(SeasonLabel::fromDateRange('2026-01-01', '2026-12-31'))->toBe('2026')
        ->and(SeasonLabel::fromDateRange('', '', 'No season'))->toBe('No season');
});

test('season label helper builds compact labels and normalized matching keys', function () {
    expect(SeasonLabel::shortFromYears(2026, 2027))->toBe('26/27')
        ->and(SeasonLabel::normalize(' 2026 / 2027 '))->toBe('2026/2027')
        ->and(SeasonLabel::normalize(" 26/27 \n"))->toBe('26/27');
});
