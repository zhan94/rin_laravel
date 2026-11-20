<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class EurCutoverTest extends TestCase
{
    public function test_existing_bookings_prices_and_payment_receipts_are_converted(): void
    {
        $migrations = glob(database_path('migrations/*.php')) ?: [];
        $schemaMigration = database_path('migrations/2026_11_20_120000_allow_cents_in_booking_amounts.php');
        $cutoverMigration = database_path('migrations/2026_11_20_120100_convert_stored_amounts_to_eur.php');
        $previousMigrations = array_values(array_filter($migrations, fn (string $path): bool => $path < $schemaMigration));

        self::assertSame(0, Artisan::call('migrate', ['--realpath' => true, '--path' => $previousMigrations]));

        $actor = User::factory()->create();
        DB::table('services')->insert(['id' => 1, 'title' => 'Ski school']);
        DB::table('service_slots')->insert(['id' => 1, 'service_id' => 1, 'date_start' => '2026-12-02', 'date_end' => '2026-12-02']);
        DB::table('service_dates')->insert(['id' => 1, 'slot_id' => 1, 'slot_date' => '2026-12-02']);
        DB::table('bookings')->insert([
            ['id' => 1, 'sum_paid' => 196, 'paid' => 98],
            ['id' => 2, 'sum_paid' => 2147483647, 'paid' => null],
        ]);
        DB::table('booking_dates')->insert(['booking_id' => 1, 'service_date_id' => 1]);
        DB::table('booking_payments')->insert(['booking_id' => 1, 'sum_paid' => 196, 'paid' => 98]);
        DB::table('cards')->insert(['id' => 1, 'service' => 1]);
        DB::table('equips')->insert(['id' => 1, 'service' => 1]);
        DB::table('bonus')->insert(['id' => 1, 'price' => 58.67]);
        $prices = ['cards_price' => 'card_id', 'equips_price' => 'equip_id', 'bonus_price' => 'bonus_id', 'services_price' => 'services'];
        foreach ($prices as $table => $foreignKey) {
            DB::table($table)->insert([$foreignKey => 1, 'days' => 2, 'price' => 58.67, 'per_day' => 29.34]);
        }

        DB::table('payments')->insert(['kid' => 1, 'sum_service' => 196, 'paid_service' => 98, 'equip_sum' => -0.03]);
        $requestKey = Str::uuid()->toString();
        DB::table('crm_mutation_receipts')->insert([
            'user_id' => $actor->id,
            'operation' => 'booking.payment',
            'request_key' => $requestKey,
            'payload_hash' => hash('sha256', json_encode([
                'booking_id' => 1, 'amount' => 50, 'mode' => 'increment', 'expected_paid_bgn' => null,
            ], JSON_THROW_ON_ERROR)),
            'result' => json_encode([
                'mode' => 'increment', 'amount_eur' => 50, 'amount_bgn' => 98,
                'paid_eur' => 50.11, 'paid_bgn' => 98, 'sum_paid_eur' => 100.21, 'sum_paid_bgn' => 196,
                'remaining_eur' => 50.11, 'remaining_bgn' => 98,
            ], JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);

        self::assertSame(0, Artisan::call('migrate', ['--realpath' => true, '--path' => [$schemaMigration, $cutoverMigration]]));
        self::assertSame(0, Artisan::call('migrate', ['--realpath' => true, '--path' => [$schemaMigration, $cutoverMigration]]));

        $this->assertDatabaseHas('bookings', ['id' => 1, 'sum_paid' => 100.21, 'paid' => 50.11]);
        $this->assertDatabaseHas('bookings', ['id' => 2, 'sum_paid' => 1097990953.71, 'paid' => null]);
        $this->assertDatabaseHas('booking_payments', ['booking_id' => 1, 'sum_paid' => 100.21, 'paid' => 50.11]);
        $this->assertDatabaseHas('booking_dates', ['booking_id' => 1, 'service_date_id' => 1]);
        $this->assertDatabaseHas('bonus', ['id' => 1, 'price' => 30]);
        foreach ($prices as $table => $foreignKey) {
            $this->assertDatabaseHas($table, [$foreignKey => 1, 'price' => 30, 'per_day' => 15]);
        }

        $this->assertDatabaseHas('payments', ['kid' => 1, 'sum_service' => 100.21, 'paid_service' => 50.11, 'equip_sum' => -0.02]);
        self::assertEquals([
            'mode' => 'increment', 'amount_eur' => 50, 'paid_eur' => 50.11,
            'sum_paid_eur' => 100.21, 'remaining_eur' => 50.1,
        ], json_decode(DB::table('crm_mutation_receipts')->value('result'), true, flags: JSON_THROW_ON_ERROR));

        $this->actingAs($actor)->postJson('/api/v1/bookings/1/payments/apply', ['amount' => 50, 'request_key' => $requestKey])
            ->assertConflict()->assertJsonPath('errors.0.code', 'idempotency_key_conflict');
        $this->assertDatabaseHas('bookings', ['id' => 1, 'paid' => 50.11]);
    }
}
