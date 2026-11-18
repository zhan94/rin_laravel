<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\DTOs\Bookings\BookingData;
use App\Enums\RoleKey;
use App\Models\Card;
use App\Models\Customer;
use App\Models\Location;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

final class DemoDatabaseSeeder extends Seeder
{
    public function run(BookingService $bookings): void
    {
        if (! app()->environment('local') || DB::getDriverName() !== 'mysql'
            || DB::getDatabaseName() !== 'rin_laravel_demo'
            || DB::scalar('SELECT DATABASE()') !== 'rin_laravel_demo'
            || ! is_readable('/run/rin/demo-password')) {
            throw new RuntimeException('Synthetic demonstration seeding requires the isolated Compose database.');
        }

        DB::transaction(function () use ($bookings): void {
            DB::table('season_defaults')->where('id', 1)->lockForUpdate()->first();
            $cache = Cache::store('database');
            if ($cache->get('rin.compose.demo.v1') === true) {
                return;
            }

            foreach (['users', 'customers', 'services', 'locations', 'cards', 'bookings'] as $table) {
                if (DB::table($table)->exists()) {
                    throw new RuntimeException('Synthetic demonstration seeding requires an empty business database.');
                }
            }

            $this->call(RoleSeeder::class);
            DB::table('skills')->insert([
                ['id' => 1, 'title' => 'Beginner'],
                ['id' => 2, 'title' => 'Intermediate'],
                ['id' => 3, 'title' => 'Advanced'],
            ]);

            $password = file_get_contents('/run/rin/demo-password');
            if ($password === false || strlen($password) < 24) {
                throw new RuntimeException('The Compose demo needs its generated account password.');
            }
            $hash = Hash::make(rtrim($password));
            foreach (RoleKey::cases() as $role) {
                User::query()->create([
                    'username' => 'demo.'.$role->value,
                    'firstname' => 'Demo',
                    'lastname' => ucwords(str_replace('_', ' ', $role->value)),
                    'email' => $role->value.'@example.test',
                    'email_verified_at' => now(),
                    'password' => $hash,
                    'is_active' => true,
                    'role_id' => Role::query()->where('role_key', $role->value)->firstOrFail()->id,
                ]);
            }

            for ($id = 1; $id <= 60; $id++) {
                Customer::query()->create([
                    'firstname' => 'Sample',
                    'lastname' => sprintf('Customer %03d', $id),
                    'date_birth' => today()->subYears(10 + ($id % 6)),
                    'parent_name' => 'Demo Guardian '.$id,
                    'parent_phone' => sprintf('+441632960%03d', $id),
                    'email' => 'customer.'.$id.'@example.test',
                    'active' => true,
                ]);
            }

            foreach (['Ski school' => 'Skischule', 'Mountain hiking' => 'Bergwandern'] as $title => $german) {
                $service = Service::query()->create([
                    'title' => $title,
                    'title_translations' => ['en' => $title, 'de' => $german],
                    'max' => 18,
                    'active' => true,
                ]);
                $location = Location::query()->create([
                    'location_from' => 'Example Central Station',
                    'location_to' => $title.' Centre',
                    'from_time' => '08:30',
                    'to_time' => '16:15',
                    'service_id' => $service->id,
                ]);
                $card = Card::query()->create(['title' => $title.' day pass', 'service' => $service->id]);
                $card->prices()->create(['days' => 1, 'price' => 28.75, 'per_day' => 28.75]);

                foreach (Customer::query()->orderBy('id')->limit(4)->pluck('id') as $index => $customer) {
                    $bookings->create(BookingData::fromArray([
                        'customer_id' => $customer,
                        'service_id' => $service->id,
                        'location_id' => $location->id,
                        'dates' => [today()->toDateString(), today()->addDay()->toDateString()],
                        'skill_id' => 1,
                        'card_id' => $card->id,
                        'sum_paid' => 90,
                        'paid' => $index % 2 === 0 ? 30 : 90,
                    ]));
                }
            }

            $cache->forever('rin.compose.demo.v1', true);
        });
    }
}
