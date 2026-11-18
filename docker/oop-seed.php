<?php

declare(strict_types=1);

use App\Core\Config;
use App\Database\DemoDataset;
use App\Database\MigrationRunner;

require '/app/vendor/autoload.php';

try {
    Config::load('/app/.env');
    $config = (require '/app/config/database.php')['db'];

    if (Config::get('APP_ENV') !== 'local' || $config['host'] !== 'mysql' || $config['name'] !== 'rin_demo' || $config['user'] !== 'oop_demo') {
        throw new RuntimeException('Expected the private OOP Compose database.');
    }

    $db = new PDO('mysql:host=mysql;dbname=rin_demo;charset=utf8mb4', $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    if ($db->query('SELECT DATABASE()')->fetchColumn() !== 'rin_demo') {
        throw new RuntimeException('Connected database does not match the OOP demo.');
    }

    (new MigrationRunner($db, '/app/database/migrations'))->migrate();

    if ((int) $db->query("SELECT COUNT(*) FROM users WHERE username = 'demo.superadmin' AND email = 'superadmin@example.test'")->fetchColumn() === 1) {
        return;
    }

    foreach (['users', 'customers', 'bookings', 'issue_reports', 'user_notifications', 'logs'] as $table) {
        if ((int) $db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn() !== 0) {
            throw new RuntimeException('Demo seeding requires the empty OOP Compose database.');
        }
    }

    $password = file_get_contents('/run/rin/demo-password');
    if ($password === false || strlen($password) < 24) {
        throw new RuntimeException('The OOP demo needs its generated account password.');
    }
    $counts = (new DemoDataset($db, '/app/storage', new DateTimeImmutable('today', new DateTimeZone('Europe/Berlin'))))->replace(rtrim($password));
    printf("Seeded %d synthetic OOP staff accounts and %d customers.\n", $counts['users'], $counts['customers']);
} catch (Throwable $error) {
    fwrite(STDERR, 'OOP demo initialization failed: '.$error->getMessage().PHP_EOL);
    exit(1);
}
