<?php

declare(strict_types=1);

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Database\Seeders\CoreBusinessSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class CoreBusinessSeederTest extends TestCase
{
    public function test_fresh_install_provisions_the_demo_without_a_legacy_connection(): void
    {
        config(['database.connections.legacy' => null]);
        self::assertFalse(DB::getSchemaBuilder()->hasTable('migrations'));

        self::assertSame(0, Artisan::call('migrate:fresh', ['--seed' => true, '--no-interaction' => true]));
        if (! is_readable('/run/rin/demo-password')) {
            self::assertSame(1, preg_match('/^([A-Za-z0-9]{32})$/m', Artisan::output(), $credentials));
            $hash = DB::table('users')->where('username', 'demo.manager')->value('password');
            self::assertIsString($hash);
            self::assertTrue(Hash::check($credentials[1], $hash));
        }
        $this->assertDatabaseCount('users', 5);
        $this->assertDatabaseCount('roles', 5);
        $this->assertDatabaseCount('customers', 60);
        $this->assertDatabaseCount('bookings', 22);
        $this->assertDatabaseCount('booking_payments', 22);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('services', 5);
        $this->assertDatabaseCount('locations', 40);
        $this->assertDatabaseCount('service_slots', 15);
        $this->assertDatabaseCount('service_dates', 280);
        $this->assertDatabaseCount('services_price', 15);
        $this->assertDatabaseCount('cards', 4);
        $this->assertDatabaseCount('cards_price', 12);
        $this->assertDatabaseCount('equips', 4);
        $this->assertDatabaseCount('equips_price', 12);
        $this->assertDatabaseCount('bonus', 3);
        $this->assertDatabaseCount('bonus_price', 5);
        $this->assertDatabaseHas('services', ['id' => 11, 'legacy_title_key' => 'snowboard_school', 'max' => 10]);
        $this->assertDatabaseHas('locations', ['id' => 3, 'service_id' => 11]);
        $this->assertDatabaseHas('cards', ['id' => 3, 'service' => 11]);
        $this->assertDatabaseHas('equips', ['id' => 2, 'service' => 11]);
        $this->assertDatabaseHas('services_price', ['services' => 1, 'days' => 3, 'price' => 84.36]);
        $this->assertDatabaseHas('season_defaults', ['start_month' => 9, 'start_day' => 1, 'end_month' => 5, 'end_day' => 31]);

        $today = CarbonImmutable::today();
        $dates = DB::table('service_dates as dates')
            ->join('service_slots as slots', 'slots.id', '=', 'dates.slot_id')
            ->whereBetween('dates.slot_date', [$today->toDateString(), $today->addDays(13)->toDateString()])
            ->where('dates.is_active', true)
            ->select('slots.service_id')
            ->selectRaw('COUNT(*) AS days')
            ->groupBy('slots.service_id')
            ->pluck('days');
        self::assertCount(5, $dates);
        foreach ($dates as $days) {
            self::assertSame(14, (int) $days);
        }

        self::assertSame(0, Artisan::call('db:seed', ['--no-interaction' => true]));
        $this->assertDatabaseCount('bookings', 22);
        $this->assertDatabaseCount('service_slots', 15);
    }

    public function test_core_only_seed_can_be_extended_with_the_demo(): void
    {
        self::assertSame(0, Artisan::call('migrate', ['--no-interaction' => true]));
        self::assertSame(0, Artisan::call('db:seed', ['--class' => CoreBusinessSeeder::class, '--no-interaction' => true]));
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('bookings', 0);

        DB::table('locations')->where('id', 1)->update(['location_from' => 'Updated example station']);
        self::assertSame(0, Artisan::call('db:seed', ['--no-interaction' => true]));
        $this->assertDatabaseHas('locations', ['id' => 1, 'location_from' => 'Updated example station']);
        $this->assertDatabaseCount('locations', 40);
        $this->assertDatabaseCount('bookings', 22);
    }
}
