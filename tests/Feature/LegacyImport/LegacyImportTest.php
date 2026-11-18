<?php

declare(strict_types=1);

namespace Tests\Feature\LegacyImport;

use App\Services\LegacyImport\LegacyImporter;
use App\Services\LegacyImport\LegacyImportPlan;
use App\Services\LegacyImport\LegacyImportReport;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\PendingCommand;
use Tests\TestCase;

final class LegacyImportTest extends TestCase
{
    private Connection $source;

    private Connection $target;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'import_target',
            'database.connections.import_target' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true],
            'database.connections.import_source' => ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true],
        ]);
        $this->target = DB::connection('import_target');
        $this->source = DB::connection('import_source');
        $migration = $this->artisan('migrate', ['--database' => 'import_target']);
        self::assertInstanceOf(PendingCommand::class, $migration);
        $migration->assertSuccessful()->run();

        foreach (app(LegacyImportPlan::class)->tables() as $mapping) {
            $this->source->getSchemaBuilder()->create($mapping->source, function (Blueprint $table) use ($mapping): void {
                foreach ($mapping->sourceColumns() as $column) {
                    if (in_array($column, $mapping->keys, true) && ! in_array($column, ['operation', 'request_key'], true)) {
                        $table->bigInteger($column);
                    } else {
                        $table->text($column)->nullable();
                    }
                }
                $table->primary($mapping->keys);
                foreach ($mapping->unique as $unique) {
                    $table->unique($unique);
                }
            });
        }
    }

    public function test_dry_run_import_and_idempotent_retry_preserve_mapped_values_and_relationships(): void
    {
        $this->loadFixture();
        $dry = $this->import(dryRun: true);
        self::assertSame([], $dry->errors);
        self::assertSame(1, $dry->tables['customers']['new']);
        self::assertSame(0, $this->target->table('customers')->count());

        $report = $this->import();
        self::assertSame([], $report->errors);
        self::assertSame(1, $report->tables['customers']['after']);
        self::assertSame('Мария 🎿', $this->target->table('customers')->value('firstname'));
        self::assertSame('2012-02-29', $this->target->table('customers')->value('date_birth'));
        self::assertSame('ski_lessons', $this->target->table('services')->value('legacy_title_key'));
        self::assertSame(0, (int) $this->target->table('locations')->value('active'));
        self::assertSame(42, (int) $this->target->table('booking_winter_details')->value('card_id'));
        self::assertSame(28.75, (float) $this->target->table('cards_price')->value('price'));
        self::assertSame(2, $this->target->table('booking_dates')->where('booking_id', 23)->count());
        self::assertSame('/crm/customers?customer=71', $this->target->table('user_notifications')->value('link_path'));
        self::assertSame(2, $this->target->table('legacy_mutation_receipts')->count());
        self::assertSame([], $this->target->select('PRAGMA foreign_key_check'));

        $retry = $this->import();
        self::assertSame([], $retry->errors);
        self::assertSame(1, $retry->tables['customers']['matched']);
        self::assertSame(0, $retry->tables['customers']['new']);
        self::assertSame(2, $retry->tables['legacy_mutation_receipts']['matched']);
    }

    public function test_invalid_values_or_relationships_abort_before_writing(): void
    {
        $this->loadFixture();
        $this->source->table('bookings')->where('id', 23)->update(['location' => 999]);
        $this->source->table('booking_payments')->where('booking_id', 23)->update(['sum_paid' => '45.1250']);
        $this->source->table('customers')->where('id', 71)->update(['date_birth' => '2025-02-30']);

        $report = $this->import();

        self::assertArrayHasKey('source / bookings.location orphan', $report->errors);
        self::assertArrayHasKey('source / booking payment mirror disagrees', $report->errors);
        self::assertArrayHasKey('booking_payments / sum_paid would lose precision or exceed the destination range', $report->errors);
        self::assertArrayHasKey('customers / date_birth has an unrecognized or invalid date', $report->errors);
        self::assertSame(0, $this->target->table('services')->count());
        self::assertSame(0, $this->target->table('bookings')->count());
    }

    public function test_only_the_approved_migration_seed_can_be_replaced(): void
    {
        $this->source->table('season_defaults')->insert(['id' => 1, 'start_month' => 9, 'start_day' => 1, 'end_month' => 5, 'end_day' => 31]);
        $unapproved = $this->import();
        self::assertArrayHasKey('season_defaults / migration-seeded row differs; explicit replacement approval is required', $unapproved->errors);
        self::assertSame(8, (int) $this->target->table('season_defaults')->value('end_month'));

        self::assertSame([], $this->import(replaceSeason: true)->errors);
        self::assertSame(5, (int) $this->target->table('season_defaults')->value('end_month'));

        $this->target->table('season_defaults')->update(['end_month' => 6]);
        $conflict = $this->import(replaceSeason: true);
        self::assertArrayHasKey('season_defaults / existing primary key has different data', $conflict->errors);
        self::assertSame(6, (int) $this->target->table('season_defaults')->value('end_month'));
    }

    public function test_secondary_unique_conflict_and_fk_guard_preserve_existing_destination_data(): void
    {
        $this->loadFixture();
        $this->target->table('roles')->insert(['id' => 40, 'role_key' => 'admin', 'title_en' => 'Existing']);
        $report = $this->import();
        self::assertArrayHasKey('roles / different primary key occupies unique role_key', $report->errors);
        self::assertSame(40, (int) $this->target->table('roles')->value('id'));
        self::assertSame(0, $this->target->table('customers')->count());
        self::assertSame([], $this->target->select('PRAGMA foreign_key_check'));
    }

    public function test_a_database_failure_rolls_back_previously_inserted_batches(): void
    {
        $this->loadFixture();
        $this->target->unprepared("CREATE TRIGGER reject_card BEFORE INSERT ON cards BEGIN SELECT RAISE(ABORT, 'test failure'); END");
        try {
            $this->import();
            self::fail('Expected an insert failure.');
        } catch (\Illuminate\Database\QueryException) {
            self::assertSame(0, $this->target->table('roles')->count());
            self::assertSame(0, $this->target->table('services')->count());
            self::assertSame(0, $this->target->table('locations')->count());
            self::assertSame(1, $this->source->table('cards')->count());
        }
    }

    public function test_imports_one_hundred_thousand_customers_in_bounded_batches(): void
    {
        $customers = 100000;
        for ($first = 1; $first <= $customers; $first += 500) {
            $batch = [];
            for ($id = $first; $id < $first + 500; $id++) {
                $batch[] = ['id' => $id, 'firstname' => 'Synthetic', 'lastname' => 'Test', 'active' => 1];
            }
            $this->source->table('customers')->insert($batch);
        }
        $peakBefore = memory_get_peak_usage(true);
        $report = $this->import(chunk: 1000);

        self::assertSame([], $report->errors);
        self::assertSame($customers, $report->tables['customers']['after']);
        self::assertSame($customers, (int) $this->target->table('customers')->max('id'));
        self::assertLessThan(32 * 1024 * 1024, memory_get_peak_usage(true) - $peakBefore);
    }

    public function test_command_rejects_invalid_chunk_and_an_unexpected_database(): void
    {
        $invalidChunk = $this->artisan('legacy:import', ['--chunk' => '0']);
        self::assertInstanceOf(PendingCommand::class, $invalidChunk);
        $invalidChunk->assertExitCode(2)->run();

        $unexpectedDatabase = $this->artisan('legacy:import', ['--dry-run' => true]);
        self::assertInstanceOf(PendingCommand::class, $unexpectedDatabase);
        $unexpectedDatabase->expectsOutputToContain('Expected legacy/briliabg_shadmin')->assertFailed()->run();
    }

    public function test_attachment_bytes_are_validated_and_existing_files_are_never_replaced(): void
    {
        Storage::fake('local');
        $directory = sys_get_temp_dir().'/ski2-legacy-test-'.bin2hex(random_bytes(8));
        mkdir($directory);
        file_put_contents($directory.'/synthetic.txt', 'test');
        config(['legacy-import.attachment_directory' => $directory]);
        try {
            $this->loadFixture();
            $this->source->table('issue_reports')->insert([
                'id' => 11, 'created_by' => 2, 'module_key' => 'crm', 'issue_key' => 'test',
                'priority' => 'normal', 'status' => 'open', 'title' => 'Synthetic issue',
            ]);
            $this->source->table('issue_report_attachments')->insert([
                'id' => 13, 'issue_id' => 11, 'stored_name' => 'synthetic.txt', 'original_name' => 'synthetic.txt',
                'mime_type' => 'text/plain', 'file_size' => 4,
            ]);
            self::assertSame([], $this->import(dryRun: true)->errors);
            Storage::disk('local')->assertMissing('issue_attachments/synthetic.txt');
            self::assertSame([], $this->import()->errors);
            self::assertSame('test', Storage::disk('local')->get('issue_attachments/synthetic.txt'));

            Storage::disk('local')->put('issue_attachments/synthetic.txt', 'else');
            $blocked = $this->import();
            self::assertArrayHasKey('issue_report_attachments / attachment destination exists with different content or could not be published', $blocked->errors);
            self::assertSame('else', Storage::disk('local')->get('issue_attachments/synthetic.txt'));
        } finally {
            unlink($directory.'/synthetic.txt');
            rmdir($directory);
        }
    }

    private function import(bool $dryRun = false, bool $replaceSeason = false, int $chunk = 1): LegacyImportReport
    {
        return app(LegacyImporter::class)->run($this->source, $this->target, $chunk, $dryRun, $replaceSeason, static function (): void {});
    }

    private function loadFixture(): void
    {
        $this->source->table('roles')->insert(['id' => 1, 'role_key' => 'admin', 'title_bg' => 'Администратор', 'title_en' => 'Admin', 'sort_order' => 1, 'is_active' => 1]);
        $this->source->table('users')->insert(['id' => 2, 'username' => 'fixture', 'password' => password_hash('synthetic-test', PASSWORD_BCRYPT, ['cost' => 4]), 'firstname' => 'Synthetic', 'lastname' => 'Operator', 'role_id' => 1, 'active' => 1]);
        $this->source->table('services')->insert(['id' => 1, 'title_bg' => 'Ски', 'title_en' => 'Ski', 'title_key' => 'ski_lessons', 'max' => 20, 'active' => 1]);
        $this->source->table('service_slots')->insert(['id' => 15, 'service_id' => 1, 'name' => 'Fixture slot', 'max' => 20, 'active' => 1]);
        $this->source->table('locations')->insert(['id' => 18, 'service_id' => 1, 'location_from' => 'Lift', 'location_to' => 'Lodge', 'from_time' => '09:00', 'to_time' => '10:00', 'active' => 0]);
        $this->source->table('cards')->insert(['id' => 42, 'title' => 'Lift card', 'service' => 1, 'active' => 1]);
        $this->source->table('cards_price')->insert(['id' => 9, 'card_id' => 42, 'price' => '28.7500', 'days' => 1, 'per_day' => '28.7500']);
        $this->source->table('customers')->insert(['id' => 71, 'firstname' => 'Мария 🎿', 'lastname' => 'Тест', 'date_birth' => '29.02.2012', 'age' => 14, 'email' => 'fixture@example.test', 'active' => 1]);
        $this->source->table('bookings')->insert(['id' => 23, 'customer_id' => 71, 'service' => 1, 'service_type' => 1, 'sum_paid' => '45.0000', 'paid' => '5.0000', 'location' => 18, 'card_id' => 42, 'active' => 1, 'lunch' => 0]);
        $this->source->table('booking_payments')->insert(['booking_id' => 23, 'sum_paid' => '45.0000', 'paid' => '5.0000']);
        $this->source->table('booking_winter_details')->insert(['booking_id' => 23, 'customer_id' => 71, 'service_id' => 1, 'service_type' => 1, 'card_id' => 42, 'shoe_size' => '040', 'equip_enabled' => 0, 'created_at' => '2025-02-28 14:15:16']);
        foreach ([15 => '2025-02-27', 16 => '2025-02-28'] as $id => $day) {
            $this->source->table('service_dates')->insert(['id' => $id, 'slot_id' => 15, 'slot_date' => $day, 'is_active' => 1]);
            $this->source->table('booking_dates')->insert(['id' => $id, 'booking_id' => 23, 'service_date_id' => $id]);
        }
        $this->source->table('user_notifications')->insert([
            'id' => 12, 'user_id' => 2, 'notification_type' => 'fixture', 'module_key' => 'customers',
            'action_key' => 'created', 'entity_type' => 'customers', 'entity_id' => 71, 'title' => 'Fixture customer',
            'message' => 'Test', 'link_path' => 'customer_view.php?id=71', 'status' => 'unread', 'priority' => 'normal',
            'delivery_channel' => 'in_app', 'is_sent' => 1, 'created_at' => '2025-02-28 14:15:16',
        ]);
        foreach (['a', 'b'] as $suffix) {
            $this->source->table('mutation_requests')->insert(['operation' => 'fixture', 'actor_id' => 2, 'request_key' => str_repeat($suffix, 32), 'request_hash' => str_repeat('a', 64), 'result_json' => '{"fixture":true}', 'created_at' => '2025-02-28 14:15:16']);
        }
    }
}
