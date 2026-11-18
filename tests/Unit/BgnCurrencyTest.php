<?php

declare(strict_types=1);

use App\Support\Pricing\BgnCurrency;

test('bgn currency normalizes and converts eur amounts', function () {
    expect(BgnCurrency::normalizeEurAmount('30,5'))->toBe(30.5)
        ->and(BgnCurrency::normalizeEurAmount('-1'))->toBeNull()
        ->and(BgnCurrency::normalizeEurAmount('abc'))->toBeNull()
        ->and(BgnCurrency::fromEur(30.0))->toBe(58.67)
        ->and(BgnCurrency::fromEurValue('45'))->toBe(88.01)
        ->and(BgnCurrency::intFromEurValue('45'))->toBe(88)
        ->and(BgnCurrency::toEur(88.01))->toBe(45.0)
        ->and(BgnCurrency::formatDecimal(58.67))->toBe('58.67');
});
