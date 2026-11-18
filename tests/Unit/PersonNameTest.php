<?php

declare(strict_types=1);

use App\Models\Customer;
use App\Models\User;
use App\Support\Names\PersonName;

test('person name helper trims parts and falls back when needed', function () {
    expect(PersonName::fromParts(['  Anna ', null, ' Petrova ']))->toBe('Anna Petrova')
        ->and(PersonName::fromParts(['', null, ''], ' guest-user '))->toBe('guest-user');
});

test('user and customer models expose shared formatted names', function () {
    $user = new User([
        'username' => 'manager',
        'firstname' => 'Mira',
        'surname' => 'V.',
        'lastname' => 'Petrova',
    ]);

    $customer = new Customer([
        'firstname' => 'Ani',
        'surname' => 'M.',
        'lastname' => 'Ivanova',
    ]);

    expect($user->name)->toBe('Mira V. Petrova');
    expect($customer->fullName())->toBe('Ani M. Ivanova');
    expect((string) $customer->getAttribute('full_name'))->toBe('Ani M. Ivanova');
});
