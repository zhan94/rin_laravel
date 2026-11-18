<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Support\SeedCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacyLookupSeeder extends Seeder
{
    public function run(): void
    {
        DatabaseSeeder::assertIsolatedDatabase();

        $this->seedSeasonDefaults();
        $this->seedServices();
        $this->seedServicePrices();
        $this->seedSkills();
        $this->seedLocations();
    }

    private function seedSeasonDefaults(): void
    {
        if (! Schema::hasTable('season_defaults')) {
            return;
        }

        DB::table('season_defaults')->upsert([
            [
                'id' => 1,
                'start_month' => 9,
                'start_day' => 1,
                'end_month' => 8,
                'end_day' => 31,
                'updated_at' => '2026-02-16 11:25:52',
            ],
        ], ['id'], ['start_month', 'start_day', 'end_month', 'end_day', 'updated_at']);
    }

    private function seedServices(): void
    {
        if (! Schema::hasTable('services')) {
            return;
        }

        DB::table('services')->upsert(
            array_map(static function (array $row): array {
                $row['title_translations'] = json_encode($row['title_translations'], JSON_THROW_ON_ERROR);

                return $row;
            }, SeedCatalog::services()),
            ['id'],
            ['title', 'title_bg', 'title_en', 'title_translations', 'title_key', 'max', 'active'],
        );
    }

    private function seedServicePrices(): void
    {
        if (! Schema::hasTable('services_price')) {
            return;
        }

        DB::table('services_price')->upsert(
            SeedCatalog::servicePrices(),
            ['id'],
            ['services', 'price', 'days', 'per_day'],
        );
    }

    private function seedSkills(): void
    {
        if (! Schema::hasTable('skills')) {
            return;
        }

        DB::table('skills')->upsert([
            ['id' => 1, 'title' => 'Beginner'],
            ['id' => 2, 'title' => 'Intermediate'],
            ['id' => 3, 'title' => 'Advanced'],
        ], ['id'], ['title']);
    }

    private function seedLocations(): void
    {
        if (! Schema::hasTable('locations')) {
            return;
        }

        DB::table('locations')->upsert(
            SeedCatalog::locations(),
            ['id'],
            ['location_from', 'location_to', 'from_time', 'to_time', 'service_id'],
        );
    }
}
