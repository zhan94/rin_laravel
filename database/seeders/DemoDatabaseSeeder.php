<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\DTOs\Bookings\BookingData;
use App\DTOs\Services\ServiceSlotData;
use App\Enums\RoleKey;
use App\Models\Card;
use App\Models\Customer;
use App\Models\Equip;
use App\Models\Location;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingService;
use App\Services\ServiceSlotService;
use App\Support\Pricing\BgnCurrency;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

final class DemoDatabaseSeeder extends Seeder
{
    public function run(BookingService $bookings, ServiceSlotService $slots): void
    {
        if (! app()->environment('local') || DB::getDriverName() !== 'mysql'
            || DB::getDatabaseName() !== 'rin_laravel_demo'
            || DB::scalar('SELECT DATABASE()') !== 'rin_laravel_demo'
            || ! is_readable('/run/rin/demo-password')) {
            throw new RuntimeException('Synthetic demonstration seeding requires the isolated Compose database.');
        }

        DB::transaction(function () use ($bookings, $slots): void {
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

            $today = CarbonImmutable::today();
            $customerIds = [];
            for ($id = 1; $id <= 60; $id++) {
                $customerIds[] = Customer::query()->create([
                    'firstname' => 'Sample',
                    'lastname' => sprintf('Customer %03d', $id),
                    'date_birth' => $today->subYears(10 + ($id % 6)),
                    'parent_name' => 'Demo Guardian '.$id,
                    'parent_phone' => sprintf('+441632960%03d', $id),
                    'email' => 'customer.'.$id.'@example.test',
                    'active' => true,
                ])->id;
            }

            $this->seedService($bookings, $slots, $today, 'Ski school', 'Skischule', true, array_slice($customerIds, 0, 8));
            $this->seedService($bookings, $slots, $today, 'Mountain hiking', 'Bergwandern', false, array_slice($customerIds, 8, 8));

            $cache->forever('rin.compose.demo.v1', true);
        });
    }

    /** @param list<int> $customerIds */
    private function seedService(
        BookingService $bookings,
        ServiceSlotService $slots,
        CarbonImmutable $today,
        string $title,
        string $german,
        bool $ski,
        array $customerIds,
    ): void {
        $capacity = $ski ? 12 : 16;
        $service = Service::query()->create([
            'title' => $title,
            'title_translations' => ['en' => $title, 'de' => $german],
            'max' => $capacity,
            'active' => true,
        ]);
        $location = Location::query()->create([
            'location_from' => 'Example Central Station',
            'location_to' => $title.' Centre',
            'from_time' => '08:30',
            'to_time' => '16:15',
            'service_id' => $service->id,
        ]);
        $passPrice = $ski ? 18 : 10;
        $card = Card::query()->create(['title' => $title.' day pass', 'service' => $service->id]);
        $card->prices()->create([
            'days' => 1,
            'price' => BgnCurrency::fromEur($passPrice),
            'per_day' => BgnCurrency::fromEur($passPrice),
        ]);
        $rental = null;
        if ($ski) {
            $rental = Equip::query()->create(['title' => 'Junior ski set', 'service' => $service->id]);
            $rental->prices()->create([
                'days' => 1,
                'price' => BgnCurrency::fromEur(12),
                'per_day' => BgnCurrency::fromEur(12),
            ]);
        }

        $dates = static fn (array $offsets): array => array_map(
            static fn (int $offset): string => $today->addDays($offset)->toDateString(),
            $offsets,
        );
        foreach ([[-28, -15], [-7, 13], [22, 42]] as [$start, $end]) {
            $slots->create($service, ServiceSlotData::fromArray([
                'name' => 'Activity block '.$today->addDays($start)->toDateString(),
                'max' => $capacity,
                'dates' => $dates(range($start, $end)),
            ], $service->id));
        }

        foreach ([[0, 1], [0], [0, 2], [-2, -1], [-24, -23], [-21], [6, 7], [25, 26]] as $index => $offsets) {
            $bookPass = $ski && $index % 2 === 0;
            $bookRental = $rental !== null && $index % 3 === 0;
            $total = count($offsets) * (($ski ? 42 : 32) + ($bookPass ? $passPrice : 0) + ($bookRental ? 12 : 0));
            $paid = match ($index) {
                0, 3, 4 => $total,
                1, 5, 7 => $total / 2,
                default => 0,
            };

            $bookings->create(BookingData::fromArray([
                'customer_id' => $customerIds[$index],
                'service_id' => $service->id,
                'location_id' => $location->id,
                'dates' => $dates($offsets),
                'skill_id' => $ski ? 1 + ($index % 3) : null,
                'card_id' => $bookPass ? $card->id : null,
                'equip_id' => $bookRental ? $rental?->id : null,
                'equip_enabled' => $bookRental,
                'ski_shoe_size' => $bookRental ? 36 + ($index % 4) : null,
                'ski_height' => $bookRental ? 140 + ($index * 2) : null,
                'sum_paid' => $total,
                'paid' => $paid,
                'approved' => $index !== 2,
            ]));
        }
    }
}
