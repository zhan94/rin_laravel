<?php

declare(strict_types=1);

use App\Enums\RoleKey;
use App\Models\Role;
use App\Models\Service;
use Tests\TestCase;

uses(TestCase::class);

test('localized title helpers resolve translations and id fallbacks', function () {
    $service = new Service([
        'title' => 'Ski School',
        'title_translations' => [
            'de' => 'Skischule',
            'en' => 'Ski School',
        ],
    ]);
    $service->id = 17;

    expect($service->displayTranslations())->toBe([
        'en' => 'Ski School',
        'de' => 'Skischule',
    ])
        ->and($service->displayTitle('de'))->toBe('Skischule')
        ->and($service->displayTitle('es'))->toBe('Ski School');

    $untitledService = new Service;
    $untitledService->id = 19;

    expect($untitledService->displayTitleOrId())->toBe('#19');
});

test('role titles fall back to the role catalog when translations are missing', function () {
    $role = new Role([
        'role_key' => RoleKey::MANAGER,
        'title_translations' => [
            'de' => 'Leitung',
        ],
    ]);

    expect($role->displayTitle('de'))->toBe('Leitung')
        ->and($role->displayTitle('es'))->toBe('Leitung');

    $fallbackRole = new Role([
        'role_key' => RoleKey::DRIVER,
        'title_translations' => [],
    ]);

    expect($fallbackRole->displayTitle('es'))->toBe('Driver')
        ->and($fallbackRole->displayTranslations())->toBe([
            'en' => 'Driver',
        ]);
});
