<?php

declare(strict_types=1);

use App\Support\Pricing\EurCurrency;

test('euro amounts are normalized to cents', function () {
    expect(EurCurrency::normalizeAmount('30,5'))->toBe(30.5)
        ->and(EurCurrency::normalizeAmount('20.005'))->toBe(20.01)
        ->and(EurCurrency::normalizeAmount('-1'))->toBeNull()
        ->and(EurCurrency::normalizeAmount('abc'))->toBeNull()
        ->and(EurCurrency::normalizeAmount('1e999'))->toBeNull()
        ->and(EurCurrency::balance(0.3, 0.2))->toBe(0.1)
        ->and(EurCurrency::balance(0.2, 0.3))->toBe(0.0);
});
