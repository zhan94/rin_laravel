<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Connection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DemoDatabaseSeeder::class);
    }

    public static function assertDevelopmentDatabase(?Connection $connection = null): void
    {
        $connection ??= DB::connection();
        $designated = config('database.development_database');

        if (self::isLegacyDatabase($connection)) {
            throw new RuntimeException('The legacy database is read-only.');
        }
        if (! app()->environment(['local', 'testing'])
            || ! is_string($designated) || $designated === ''
            || $connection->getDatabaseName() !== $designated) {
            throw new RuntimeException(
                'Seeding and destructive commands require an explicitly designated development database. '
                .'Set DB_DEVELOPMENT_DATABASE to the isolated database you intend to use in a local or testing environment.',
            );
        }
    }

    public static function isLegacyDatabase(Connection $connection): bool
    {
        $legacy = config('database.connections.legacy.database');
        $database = (string) $connection->getDatabaseName();

        return $connection->getName() === 'legacy'
            || (is_string($legacy) && $legacy !== '' && strcasecmp($database, $legacy) === 0);
    }
}
