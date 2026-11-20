<?php

use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schedule;

// Laravel skips console event forwarding by default in the testing environment.
$kernel = app(Kernel::class);
if ($kernel instanceof ConsoleKernel) {
    $kernel->rerouteSymfonyCommandEvents();
}

Event::listen(CommandStarting::class, static function (CommandStarting $event): void {
    $destructive = in_array($event->command, ['db:wipe', 'migrate:fresh', 'migrate:refresh', 'migrate:reset', 'migrate:rollback'], true);
    $migration = in_array($event->command, ['migrate', 'migrate:install'], true);
    $seeding = $event->command === 'db:seed'
        || ($event->command === 'migrate' && $event->input->getOption('seed') && ! $event->input->getOption('pretend'));
    if (! $destructive && ! $migration && ! $seeding) {
        return;
    }

    $name = $event->input->getOption('database');
    $connection = DB::connection(is_string($name) ? $name : null);
    if ($destructive || $seeding) {
        DatabaseSeeder::assertDevelopmentDatabase($connection);
    } elseif (DatabaseSeeder::isLegacyDatabase($connection)) {
        throw new RuntimeException('The legacy database is read-only.');
    }
});

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('horizon:snapshot')->everyFiveMinutes();
