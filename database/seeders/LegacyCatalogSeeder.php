<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Support\SeedCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacyCatalogSeeder extends Seeder
{
    public function run(): void
    {
        DatabaseSeeder::assertIsolatedDatabase();

        $this->seedCardsAndPrices();
        $this->seedEquipsAndPrices();
        $this->seedBonusAndPrices();
    }

    private function seedCardsAndPrices(): void
    {
        if (! Schema::hasTable('cards')) {
            return;
        }

        DB::table('cards')->upsert(array_map(static function (array $row): array {
            $row['title_translations'] = json_encode($row['title_translations'], JSON_THROW_ON_ERROR);

            return $row;
        }, SeedCatalog::cards()), ['id'], ['title', 'title_bg', 'title_en', 'title_translations', 'service']);

        if (! Schema::hasTable('cards_price')) {
            return;
        }

        DB::table('cards_price')->upsert(SeedCatalog::cardPrices(), ['id'], ['card_id', 'price', 'days', 'per_day']);
    }

    private function seedEquipsAndPrices(): void
    {
        if (! Schema::hasTable('equips')) {
            return;
        }

        DB::table('equips')->upsert(array_map(static function (array $row): array {
            $row['title_translations'] = json_encode($row['title_translations'], JSON_THROW_ON_ERROR);

            return $row;
        }, SeedCatalog::equips()), ['id'], ['title', 'title_bg', 'title_en', 'title_translations', 'service']);

        if (! Schema::hasTable('equips_price')) {
            return;
        }

        DB::table('equips_price')->upsert(SeedCatalog::equipPrices(), ['id'], ['equip_id', 'price', 'days', 'per_day']);
    }

    private function seedBonusAndPrices(): void
    {
        if (! Schema::hasTable('bonus')) {
            return;
        }

        DB::table('bonus')->upsert(array_map(static function (array $row): array {
            $row['title_translations'] = json_encode($row['title_translations'], JSON_THROW_ON_ERROR);

            return $row;
        }, SeedCatalog::bonuses()), ['id'], [
            'title',
            'title_bg',
            'title_en',
            'title_translations',
            'title_key',
            'location_from',
            'location_to',
            'date_from',
            'date_to',
            'price',
            'description',
        ]);

        if (! Schema::hasTable('bonus_price')) {
            return;
        }

        DB::table('bonus_price')->upsert(SeedCatalog::bonusPrices(), ['id'], ['bonus_id', 'price', 'days', 'per_day']);
    }
}
