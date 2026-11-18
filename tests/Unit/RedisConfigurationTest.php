<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class);

test('redis infrastructure uses dedicated named connections', function () {
    $redisConnections = config('database.redis');
    $queueConnection = (string) config('queue.connections.redis.connection');
    $cacheConnection = (string) config('cache.stores.redis.connection');
    $horizonConnection = (string) config('horizon.use');

    expect($redisConnections)->toBeArray();
    expect(array_keys($redisConnections))->toContain(
        'default',
        'cache',
        'queue',
        'session',
        'horizon',
    );

    expect(config('database.redis.cache.database'))->toBe('1');
    expect(config('database.redis.queue.database'))->toBe('2');
    expect(config('database.redis.session.database'))->toBe('3');
    expect(config('database.redis.horizon.database'))->toBe('4');
    expect($queueConnection)->not->toBe('');
    expect($cacheConnection)->not->toBe('');
    expect($horizonConnection)->not->toBe('');
    expect($redisConnections[$queueConnection] ?? null)->toBeArray();
    expect($redisConnections[$cacheConnection] ?? null)->toBeArray();
    expect($redisConnections[$horizonConnection] ?? null)->toBeArray();
});
