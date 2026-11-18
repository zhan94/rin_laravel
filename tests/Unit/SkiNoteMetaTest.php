<?php

declare(strict_types=1);

use App\Support\Bookings\SkiNoteMeta;

test('ski note meta extracts clean note and normalized fields', function () {
    $meta = SkiNoteMeta::fromNote("Needs focus\n\n[[SKI_META]]\n{\"shoe_size\":\"41\",\"height\":\"150\",\"skill\":\"2\",\"equip\":\"0\"}\n[[/SKI_META]]");

    expect($meta->note)->toBe('Needs focus')
        ->and($meta->shoeSize)->toBe('41')
        ->and($meta->height)->toBe('150')
        ->and($meta->skillId)->toBe('2')
        ->and($meta->skillIdAsInt())->toBe(2)
        ->and($meta->equipEnabled)->toBe('0')
        ->and($meta->equipEnabledState())->toBe(0);
});

test('ski note meta handles missing or invalid metadata gracefully', function () {
    $empty = SkiNoteMeta::fromNote(null);
    $invalid = SkiNoteMeta::fromNote("Note\n\n[[SKI_META]]\nnot-json\n[[/SKI_META]]");

    expect($empty->note)->toBeNull()
        ->and($empty->toArray())->toBe([
            'shoe_size' => '',
            'height' => '',
            'skill_id' => '',
            'equip_enabled' => '',
        ])
        ->and($invalid->note)->toBe('Note')
        ->and($invalid->equipEnabledState())->toBe(-1);
});

test('ski note meta appends a single encoded block to the note', function () {
    $note = SkiNoteMeta::append(' Driver note ', [
        'shoe_size' => '',
        'height' => '',
        'equip_enabled' => '1',
        'skill_id' => '4',
    ]);

    expect($note)->toContain('Driver note')
        ->and($note)->toContain('[[SKI_META]]')
        ->and($note)->toContain('"skill_id":"4"')
        ->and(substr_count((string) $note, '[[SKI_META]]'))->toBe(1);
});
