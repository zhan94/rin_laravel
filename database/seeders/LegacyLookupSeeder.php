<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Support\SeedCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LegacyLookupSeeder extends Seeder
{
    public function run(): void
    {
        DatabaseSeeder::assertDevelopmentDatabase();

        DB::table('season_defaults')->where('id', 1)->update([
            'start_month' => 9,
            'start_day' => 1,
            'end_month' => 5,
            'end_day' => 31,
            'updated_at' => now(),
        ]);

        foreach ([
            'services' => SeedCatalog::services(),
            'services_price' => SeedCatalog::servicePrices(),
            'locations' => SeedCatalog::locations(),
            'skills' => [
                ['id' => 1, 'title' => 'Beginner'],
                ['id' => 2, 'title' => 'Intermediate'],
                ['id' => 3, 'title' => 'Advanced'],
            ],
        ] as $table => $rows) {
            DB::table($table)->upsert($rows, ['id'], array_diff(array_keys($rows[0]), ['id']));
        }
    }
}
