<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Database\Seeders\Support\SeedCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LegacyCatalogSeeder extends Seeder
{
    public function run(): void
    {
        DatabaseSeeder::assertDevelopmentDatabase();

        foreach ([
            'cards' => SeedCatalog::cards(),
            'cards_price' => SeedCatalog::cardPrices(),
            'equips' => SeedCatalog::equips(),
            'equips_price' => SeedCatalog::equipPrices(),
            'bonus' => SeedCatalog::bonuses(CarbonImmutable::today()),
            'bonus_price' => SeedCatalog::bonusPrices(),
        ] as $table => $rows) {
            DB::table($table)->upsert($rows, ['id'], array_diff(array_keys($rows[0]), ['id']));
        }
    }
}
