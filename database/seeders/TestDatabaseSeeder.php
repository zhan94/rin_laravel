<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TestDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DatabaseSeeder::assertDevelopmentDatabase();

        $this->call([
            CoreBusinessSeeder::class,
            TestUsersSeeder::class,
            TestLocationsSeeder::class,
        ]);
    }
}
