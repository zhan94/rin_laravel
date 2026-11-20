<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

final class ProtectedDatabaseCommandTest extends TestCase
{
    public function test_fresh_migration_requires_designation_of_the_selected_database(): void
    {
        config(['database.development_database' => 'another_database']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('explicitly designated development database');

        $this->checkBeforeStarting('migrate:fresh', ['--seed' => true, '--force' => true]);
    }

    public function test_legacy_connection_is_protected_even_when_designated(): void
    {
        config([
            'database.connections.legacy.database' => 'archived_source',
            'database.development_database' => 'archived_source',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The legacy database is read-only.');

        $this->checkBeforeStarting('migrate', ['--database' => 'legacy']);
    }

    public function test_database_url_override_must_match_designation(): void
    {
        config([
            'database.connections.mysql.database' => 'development_workspace',
            'database.connections.mysql.url' => 'mysql://127.0.0.1:1/unintended_target',
            'database.development_database' => 'development_workspace',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('explicitly designated development database');

        $this->checkBeforeStarting('db:seed', ['--database' => 'mysql']);
    }

    public function test_legacy_schema_is_protected_through_an_alternate_connection(): void
    {
        config([
            'database.connections.legacy.database' => 'archived_source',
            'database.connections.mysql.database' => 'ARCHIVED_SOURCE',
            'database.development_database' => 'ARCHIVED_SOURCE',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The legacy database is read-only.');

        $this->checkBeforeStarting('db:wipe', ['--database' => 'mysql']);
    }

    public function test_designated_database_passes_preflight_for_fresh_migration(): void
    {
        $this->checkBeforeStarting('migrate:fresh', ['--seed' => true]);

        self::assertFalse(DB::getSchemaBuilder()->hasTable('migrations'));
    }

    public function test_standard_migration_and_seeding_run_on_designated_database(): void
    {
        self::assertSame(0, Artisan::call('migrate', ['--no-interaction' => true]));
        self::assertSame(0, Artisan::call('db:seed', ['--no-interaction' => true]));
        self::assertSame(5, DB::table('roles')->count());
    }

    /** @param array<string, bool|string> $options */
    private function checkBeforeStarting(string $command, array $options): void
    {
        // Exercise Artisan's guard without executing the command.
        $definition = Artisan::all()[$command]->getDefinition();
        Event::dispatch(new CommandStarting($command, new ArrayInput($options, $definition), new BufferedOutput));
    }
}
