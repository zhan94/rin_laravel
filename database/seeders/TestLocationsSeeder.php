<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestLocationsSeeder extends Seeder
{
    public function run(): void
    {
        DatabaseSeeder::assertDevelopmentDatabase();

        if (! Schema::hasTable('locations')) {
            return;
        }

        DB::table('locations')->upsert(
            $this->rows(),
            ['id'],
            ['location_from', 'location_to', 'from_time', 'to_time', 'service_id'],
        );
    }

    /**
     * @return list<array{id:int,location_from:string,location_to:string,from_time:string,to_time:string,service_id:int}>
     */
    private function rows(): array
    {
        return [
            [
                'id' => 9001,
                'location_from' => 'Central Station',
                'location_to' => 'Ski School Base',
                'from_time' => '07:45',
                'to_time' => '16:30',
                'service_id' => 1,
            ],
            [
                'id' => 9002,
                'location_from' => 'Business Park',
                'location_to' => 'Mountain Walking Centre',
                'from_time' => '08:00',
                'to_time' => '17:15',
                'service_id' => 4,
            ],
            [
                'id' => 9003,
                'location_from' => 'City Mall',
                'location_to' => 'Swimming Centre',
                'from_time' => '08:30',
                'to_time' => '15:30',
                'service_id' => 3,
            ],
            [
                'id' => 9004,
                'location_from' => 'South Park',
                'location_to' => 'Snowboard Meeting Point',
                'from_time' => '08:10',
                'to_time' => '16:40',
                'service_id' => 11,
            ],
            [
                'id' => 9005,
                'location_from' => 'Airport Terminal 2',
                'location_to' => 'Training Centre',
                'from_time' => '09:00',
                'to_time' => '18:00',
                'service_id' => 5,
            ],
            [
                'id' => 9006,
                'location_from' => 'North Campus',
                'location_to' => 'Student Swimming Pool',
                'from_time' => '13:15',
                'to_time' => '16:00',
                'service_id' => 3,
            ],
        ];
    }
}
