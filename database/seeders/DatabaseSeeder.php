<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        self::assertIsolatedDatabase();
        $this->call(RoleSeeder::class);
    }

    public static function assertIsolatedDatabase(): void
    {
        $db = DB::connection();
        $database = $db->getDatabaseName();

        if (! app()->environment(['local', 'testing'])
            || ! match ($db->getDriverName()) {
                'sqlite' => in_array($database, [':memory:', database_path('demo.sqlite')], true),
                'mysql' => in_array($database, ['rin_laravel_demo', 'rin_laravel_test'], true)
                    && $db->scalar('SELECT DATABASE()') === $database,
                default => false,
            }) {
            throw new RuntimeException('Seeding requires an isolated Laravel demo or test database.');
        }
    }
}
