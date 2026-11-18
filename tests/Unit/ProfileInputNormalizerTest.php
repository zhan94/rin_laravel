<?php

declare(strict_types=1);

use App\Support\Profiles\ProfileInputNormalizer;

test('registration normalizer fills missing name parts from legacy full name input', function () {
    $normalized = ProfileInputNormalizer::forRegistration([
        'name' => 'Anna Maria Petrova',
    ]);

    expect($normalized)->toMatchArray([
        'firstname' => 'Anna',
        'surname' => 'Maria',
        'lastname' => 'Petrova',
    ]);
});

test('registration normalizer preserves an explicitly provided surname', function () {
    $normalized = ProfileInputNormalizer::forRegistration([
        'name' => 'Anna Maria Petrova',
        'surname' => 'Manual',
    ]);

    expect($normalized)->toMatchArray([
        'firstname' => 'Anna',
        'surname' => 'Manual',
        'lastname' => 'Petrova',
    ]);
});

test('profile update normalizer restores the current username and parses legacy full name input', function () {
    $normalized = ProfileInputNormalizer::forProfileUpdate([
        'name' => 'Test User',
        'username' => '',
    ], 'existing-user');

    expect($normalized)->toMatchArray([
        'username' => 'existing-user',
        'firstname' => 'Test',
        'surname' => null,
        'lastname' => 'User',
    ]);
});

test('profile update normalizer keeps provided name fields and only backfills the username', function () {
    $normalized = ProfileInputNormalizer::forProfileUpdate([
        'firstname' => 'Teo',
        'surname' => 'Manual',
        'lastname' => 'User',
        'username' => '',
    ], 'existing-user');

    expect($normalized)->toMatchArray([
        'username' => 'existing-user',
        'firstname' => 'Teo',
        'surname' => 'Manual',
        'lastname' => 'User',
    ]);
});
