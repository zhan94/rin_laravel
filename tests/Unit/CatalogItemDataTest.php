<?php

declare(strict_types=1);

use App\DTOs\Catalog\CardData;
use App\DTOs\Catalog\EquipData;
use Tests\TestCase;

uses(TestCase::class);

test('catalog item data classes share the same payload normalization rules', function () {
    $payload = [
        'translations' => [
            'en' => 'Ski card',
            'de' => 'Skikarte',
            'es' => 'Tarjeta',
        ],
        'service_id' => 7,
        'prices' => [
            ['days' => 2, 'price_eur' => 20.25],
            ['days' => 0, 'price_eur' => 10],
            'invalid',
        ],
    ];

    $card = CardData::fromArray($payload);
    $equip = EquipData::fromArray($payload);

    expect($card->title)->toBe('Ski card')
        ->and($equip->title)->toBe('Ski card')
        ->and($card->translations)->toBe($equip->translations)
        ->and($card->serviceId)->toBe(7)
        ->and($equip->serviceId)->toBe(7)
        ->and($card->toPersistence())->toBe($equip->toPersistence())
        ->and($card->prices)->toHaveCount(1)
        ->and($equip->prices)->toHaveCount(1)
        ->and($card->prices[0]->priceEur)->toBe(20.25)
        ->and($equip->prices[0]->perDayEur)->toBe(10.13);
});
