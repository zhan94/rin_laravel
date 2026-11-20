<?php

declare(strict_types=1);

use App\DTOs\Bookings\BookingFilterData;
use App\DTOs\Customers\CustomerFilterData;
use App\DTOs\Reports\ReportFilterData;
use App\Http\Controllers\Api\V1\ServiceScheduleController;
use App\Http\Controllers\DashboardController;
use App\Models\ServiceSlot;
use App\Models\User;
use App\Services\BookingService;
use App\Services\CustomerService;
use App\Services\ReportService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/vendor/autoload.php';

if (getenv('APP_ENV') !== 'local' || getenv('DB_CONNECTION') !== 'mysql' || getenv('DB_HOST') !== 'mysql'
    || getenv('DB_URL') || ! is_readable('/run/rin/db-password') || ! is_readable('/run/rin/app-key')) {
    fwrite(STDERR, "Run inside one of the isolated synthetic Compose projects.\n");
    exit(2);
}

putenv('APP_KEY='.trim((string) file_get_contents('/run/rin/app-key')));
putenv('DB_PASSWORD='.trim((string) file_get_contents('/run/rin/db-password')));
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
set_exception_handler(static function (Throwable $error): void {
    fwrite(STDERR, $error->getMessage().PHP_EOL);
    exit(1);
});

$db = DB::connection();
DatabaseSeeder::assertDevelopmentDatabase($db);
$dataset = config('database.synthetic_dataset');
if (! in_array($dataset, ['demo', 'benchmark'], true) || $db->scalar('SELECT DATABASE()') !== $db->getDatabaseName()) {
    fwrite(STDERR, "Unexpected MySQL schema.\n");
    exit(2);
}

$checks = [
    'booking type, customer, service and pickup' => <<<'SQL'
        SELECT COUNT(*) FROM bookings b
        LEFT JOIN customers c ON c.id = b.customer_id
        LEFT JOIN services s ON s.id = b.service
        LEFT JOIN locations l ON l.id = b.location
        LEFT JOIN bonus a ON a.id = b.service
        WHERE c.id IS NULL OR b.service_type IS NULL OR b.service_type NOT IN (1, 2)
            OR (b.service_type = 1 AND (s.id IS NULL OR l.id IS NULL OR l.service_id NOT IN (0, s.id)))
            OR (b.service_type = 2 AND (a.id IS NULL OR b.location IS NOT NULL))
        SQL,
    'calendar date outside its period' => <<<'SQL'
        SELECT COUNT(*) FROM service_dates d JOIN service_slots s ON s.id = d.slot_id
        WHERE d.slot_date NOT BETWEEN s.date_start AND s.date_end
        SQL,
    'overlapping service periods' => <<<'SQL'
        SELECT COUNT(*) FROM service_slots a JOIN service_slots b ON a.service_id = b.service_id AND a.id < b.id
        WHERE a.date_start <= b.date_end AND b.date_start <= a.date_end
        SQL,
    'booking dates missing or assigned to the wrong service' => <<<'SQL'
        SELECT COUNT(*) FROM bookings b
        LEFT JOIN booking_dates l ON l.booking_id = b.id
        LEFT JOIN service_dates d ON d.id = l.service_date_id
        LEFT JOIN service_slots s ON s.id = d.slot_id
        WHERE (b.service_type = 1 AND (l.id IS NULL OR s.service_id <> b.service))
            OR (b.service_type = 2 AND l.id IS NOT NULL)
        SQL,
    'overbooked dates' => <<<'SQL'
        SELECT COUNT(*) FROM (
            SELECT d.id FROM service_dates d JOIN service_slots s ON s.id = d.slot_id
            JOIN booking_dates l ON l.service_date_id = d.id JOIN bookings b ON b.id = l.booking_id AND b.active = 1
            GROUP BY d.id, s.max HAVING COUNT(*) > s.max
        ) full_days
        SQL,
    'duplicate active customer, service and date' => <<<'SQL'
        SELECT COUNT(*) FROM (
            SELECT b.customer_id, b.service, d.slot_date
            FROM bookings b JOIN booking_dates l ON l.booking_id = b.id JOIN service_dates d ON d.id = l.service_date_id
            WHERE b.active = 1 GROUP BY b.customer_id, b.service, d.slot_date HAVING COUNT(*) > 1
        ) duplicates
        SQL,
    'missing or inconsistent payment balance' => <<<'SQL'
        SELECT COUNT(*) FROM bookings b LEFT JOIN booking_payments p ON p.booking_id = b.id
        WHERE p.booking_id IS NULL OR COALESCE(b.paid, 0) < 0 OR COALESCE(b.sum_paid, 0) < 0
            OR COALESCE(b.paid, 0) > COALESCE(b.sum_paid, 0)
            OR NOT (p.paid <=> b.paid) OR NOT (p.sum_paid <=> b.sum_paid)
        SQL,
    'invalid rental, card or skill assignment' => <<<'SQL'
        SELECT COUNT(*) FROM booking_winter_details w JOIN bookings b ON b.id = w.booking_id
        LEFT JOIN equips e ON e.id = w.equip_id LEFT JOIN cards c ON c.id = w.card_id
        LEFT JOIN skills sk ON sk.id = w.skill_id
        WHERE b.service_type <> 1 OR b.service NOT IN (1, 11) OR sk.id IS NULL
            OR (w.equip_id IS NOT NULL AND (e.id IS NULL OR e.service <> b.service OR b.service <> 1 OR w.equip_enabled <> 1))
            OR (w.card_id IS NOT NULL AND (c.id IS NULL OR c.service <> b.service OR b.service <> 1))
        SQL,
    'double-assigned rental on the same date' => <<<'SQL'
        SELECT COUNT(*) FROM (
            SELECT w.equip_id, d.slot_date
            FROM booking_winter_details w JOIN bookings b ON b.id = w.booking_id AND b.active = 1
            JOIN booking_dates l ON l.booking_id = b.id JOIN service_dates d ON d.id = l.service_date_id
            WHERE w.equip_id IS NOT NULL GROUP BY w.equip_id, d.slot_date HAVING COUNT(*) > 1
        ) double_assignments
        SQL,
];

if ((int) $db->scalar('SELECT @@foreign_key_checks') !== 1) {
    throw new RuntimeException('Foreign-key validation is off.');
}
foreach ($checks as $label => $sql) {
    $failures = (int) $db->scalar($sql);
    if ($failures !== 0) {
        throw new RuntimeException(sprintf('%s: %d integrity errors.', $label, $failures));
    }
}
echo "Integrity checks passed with foreign keys enabled.\n";

$counts = [];
foreach (['users', 'roles', 'customers', 'locations', 'services', 'service_slots', 'service_dates', 'bookings', 'booking_dates', 'booking_payments', 'equips', 'cards', 'bonus', 'issue_reports', 'issue_report_comments', 'user_notifications', 'logs'] as $table) {
    $counts[$table] = $db->table($table)->count();
}
$counts['positive_payments'] = $db->table('booking_payments')->where('paid', '>', 0)->count();
$counts['equipped_bookings'] = $db->table('booking_winter_details')->whereNotNull('equip_id')->count();
$counts['booked_customers'] = $db->table('bookings')->distinct()->count('customer_id');
echo json_encode($counts, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;

$email = $dataset === 'benchmark' ? 'benchmark.55555@example.test' : 'customer.1@example.test';
$customerService = app(CustomerService::class);
$bookingService = app(BookingService::class);
$dashboardController = app(DashboardController::class);
$schedule = app(ServiceScheduleController::class);
$reportService = app(ReportService::class);
$user = User::query()->where('username', 'demo.manager')->firstOrFail();
$today = today()->toDateString();
$slot = ServiceSlot::query()->where('service_id', $dataset === 'benchmark' ? 3 : 1)
    ->where('date_start', '<=', $today)->where('date_end', '>=', $today)->firstOrFail();
$dashboard = Request::create('/dashboard', server: ['HTTP_X_INERTIA' => 'true']);
$dashboard->setUserResolver(static fn () => $user);
$report = ReportFilterData::fromArray(['from' => today()->subDays(30)->toDateString(), 'to' => today()->addDays(14)->toDateString()]);
$deepPage = max(1, (int) floor($counts['customers'] / 25 * 0.975));
$operations = [
    'Customer first page' => [fn () => $customerService->paginate(CustomerFilterData::fromArray(['page' => 1]))->count(), 1],
    'Customer deep page ('.$deepPage.')' => [fn () => $customerService->paginate(CustomerFilterData::fromArray(['page' => $deepPage]))->count(), 1],
    'Customer email search' => [fn () => $customerService->paginate(CustomerFilterData::fromArray(['q' => $email]))->total(), 1],
    'Booking search by customer email' => [fn () => $bookingService->paginate(BookingFilterData::fromArray(['q' => $email]))->total(), 1],
    'Availability for selected date' => [fn () => (int) data_get($schedule->dates(Request::create('/', parameters: ['date' => $today]), $slot)->getData(true), 'data.0.remaining'), 1],
    'Dashboard controller and Inertia payload' => [fn () => (int) data_get($dashboardController($dashboard)->toResponse($dashboard)->getData(true), 'props.customers.total', 0), $counts['customers']],
    'Report generation (45 days, first page)' => [fn () => (int) data_get($reportService->overview($report), 'summary.total_count'), 1],
];

$queries = 0;
DB::listen(static function (QueryExecuted $query) use (&$queries): void {
    $queries++;
});
foreach ($operations as $label => [$operation, $minimum]) {
    $samples = [];
    for ($run = 0; $run < 8; $run++) {
        $queries = 0;
        $started = hrtime(true);
        $result = $operation();
        $elapsed = (hrtime(true) - $started) / 1_000_000;
        if ($result < $minimum) {
            throw new RuntimeException('Unexpected result for '.$label);
        }
        if ($run !== 0) {
            $samples[] = [$elapsed, $queries];
        }
    }
    sort($samples);
    printf("%s: %.2f ms median (%d queries, result %d)\n", $label, $samples[3][0], $samples[3][1], $result);
}
