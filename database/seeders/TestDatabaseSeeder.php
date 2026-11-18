<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TestDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DatabaseSeeder::assertIsolatedDatabase();

        $this->call([
            RoleSeeder::class,
            LegacyLookupSeeder::class,
            LegacyCatalogSeeder::class,
            TestUsersSeeder::class,
            TestLocationsSeeder::class,
        ]);
    }
}
