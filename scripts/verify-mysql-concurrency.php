<?php

declare(strict_types=1);

use App\DTOs\Bookings\BookingData;
use App\Exceptions\ApiException;
use App\Models\Booking;
use App\Models\Location;
use App\Models\Service;
use App\Models\User;
use App\Services\BookingPaymentService;
use App\Services\BookingService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$db = DB::connection();
if (! $app->environment('testing') || $db->getDriverName() !== 'mysql'
    || $db->getDatabaseName() !== 'rin_laravel_test' || $db->scalar('SELECT DATABASE()') !== 'rin_laravel_test') {
    fwrite(STDERR, "Use the isolated rin_laravel_test MySQL database with APP_ENV=testing.\n");
    exit(2);
}

if (($argv[1] ?? '') === 'worker') {
    [$operation, $id, $relatedId] = array_slice($argv, 2);
    echo "ready\n";
    try {
        if ($operation === 'service') {
            $app->make(BookingService::class)->create(BookingData::fromArray([
                'firstname' => 'Synthetic', 'lastname' => 'Race '.getmypid(),
                'service_id' => (int) $id, 'location_id' => (int) $relatedId,
                'dates' => ['2090-01-03'], 'skill_id' => 1, 'sum_paid' => 100, 'paid' => 0,
            ]));
        } else {
            $app->make(BookingPaymentService::class)->apply((int) $id, 60, 'increment', User::findOrFail($relatedId), null);
        }
        echo "ok\n";
    } catch (ApiException $exception) {
        echo $exception->errorCode()."\n";
    }
    exit;
}

if ($db->getSchemaBuilder()->hasTable('migrations')) {
    fwrite(STDERR, "Use an empty disposable database for this verification.\n");
    exit(2);
}

if ($kernel->call('migrate', ['--force' => true]) !== 0) {
    fwrite(STDERR, $kernel->output());
    exit(3);
}

$service = Service::factory()->create(['max' => 1]);
$location = Location::factory()->create(['service_id' => 0]);
$user = User::factory()->create();

function race(string $resource, int $id, int $relatedId, string $expectedError): void
{
    $table = $resource === 'service' ? 'services' : 'bookings';
    DB::beginTransaction();
    $workers = [];
    try {
        DB::table($table)->where('id', $id)->lockForUpdate()->first();
        for ($index = 0; $index < 2; $index++) {
            $process = proc_open([PHP_BINARY, __FILE__, 'worker', $resource, (string) $id, (string) $relatedId],
                [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => STDERR], $pipes);
            if (! is_resource($process)) {
                throw new RuntimeException('Could not start concurrency worker.');
            }
            $output = $pipes[1];
            $workers[] = [$process, $output];
            stream_set_timeout($output, 10);
            if (trim((string) fgets($output)) !== 'ready') {
                throw new RuntimeException('Worker failed to reach the lock barrier.');
            }
        }

        usleep(150000);
        foreach ($workers as [$process]) {
            if (! proc_get_status($process)['running']) {
                throw new RuntimeException('Worker completed before the database lock was released.');
            }
        }
    } finally {
        DB::rollBack();
    }

    $results = [];
    foreach ($workers as [$process, $output]) {
        $results[] = trim(stream_get_contents($output) ?: '');
        fclose($output);
        proc_close($process);
    }
    sort($results);
    $expected = ['ok', $expectedError];
    sort($expected);
    if ($results !== $expected) {
        throw new RuntimeException('Unexpected race results: '.implode(', ', $results));
    }

    printf("%s race passed: one write, one %s.\n", $resource, $expectedError);
}

race('service', $service->id, $location->id, 'booking_capacity_exceeded');
if (Booking::count() !== 1 || DB::table('booking_dates')->count() !== 1) {
    throw new RuntimeException('Seat verification found an unexpected booking or date count.');
}

$booking = Booking::firstOrFail();
race('payment', $booking->id, $user->id, 'booking_payment_exceeds_remaining');
if ($booking->refresh()->paid !== 117 || $booking->payment?->paid !== 117) {
    throw new RuntimeException('Payment verification found a balance or payment mirror mismatch.');
}
