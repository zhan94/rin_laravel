<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use Closure;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class SyntheticDatabase
{
    /** @param list<string> $emptyTables */
    public static function seed(string $marker, array $emptyTables, Closure $populate): void
    {
        $connection = DB::connection();
        DatabaseSeeder::assertDevelopmentDatabase($connection);

        $connection->transaction(function () use ($connection, $marker, $emptyTables, $populate): void {
            $connection->table('season_defaults')->where('id', 1)->lockForUpdate()->first();
            $cache = Cache::build([
                ...config('cache.stores.database'),
                'connection' => $connection->getName(),
                'lock_connection' => $connection->getName(),
            ]);
            if ($cache->get($marker) === true) {
                return;
            }

            foreach ($emptyTables as $table) {
                if ($connection->table($table)->exists()) {
                    throw new RuntimeException('This fixture requires an empty development dataset. Use a separate database or Compose project.');
                }
            }

            $populate();
            $cache->forever($marker, true);
        });
    }
}
