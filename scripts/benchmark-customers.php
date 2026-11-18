<?php

declare(strict_types=1);

use App\DTOs\Customers\CustomerFilterData;
use App\Repositories\Eloquent\CustomerRepository;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$db = DB::connection();
if (! $app->environment('testing') || $db->getDriverName() !== 'sqlite' || $db->getDatabaseName() !== ':memory:') {
    fwrite(STDERR, "Set APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: to run the benchmark.\n");
    exit(2);
}

if ($kernel->call('migrate', ['--force' => true]) !== 0) {
    fwrite(STDERR, $kernel->output());
    exit(3);
}

$started = hrtime(true);
$db->transaction(function () use ($db): void {
    for ($first = 1; $first <= 100000; $first += 250) {
        $batch = [];
        for ($id = $first; $id < $first + 250; $id++) {
            $batch[] = [
                'id' => $id,
                'firstname' => 'Synthetic',
                'surname' => 'Sample',
                'lastname' => 'Fixture '.$id,
                'email' => 'sample.'.$id.'@example.test',
                'date_birth' => '2010-01-20',
                'active' => true,
            ];
        }
        $db->table('customers')->insert($batch);
    }
});
$seedSeconds = (hrtime(true) - $started) / 1e9;
printf("PHP %s / SQLite %s / %s\n", PHP_VERSION, $db->scalar('SELECT sqlite_version()'), php_uname('m'));
printf("%d synthetic customers seeded in %.3f s\n", $db->table('customers')->count(), $seedSeconds);

$repository = $app->make(CustomerRepository::class);
$queryCount = 0;
$sqlTime = 0.0;
DB::listen(static function (QueryExecuted $query) use (&$queryCount, &$sqlTime): void {
    $queryCount++;
    $sqlTime += $query->time;
});

foreach ([
    'First page' => [['page' => 1], 25, 100000],
    'Deep page' => [['page' => 3900], 25, 100000],
    'Substring match' => [['q' => 'Fixture 99999'], 1, 1],
    'Substring miss' => [['q' => 'Absent-marker'], 0, 0],
] as $label => [$params, $expectedPage, $expectedTotal]) {
    $input = CustomerFilterData::fromArray($params + ['per_page' => 25]);
    $samples = [];
    for ($run = 0; $run < 8; $run++) {
        $queryCount = 0;
        $sqlTime = 0.0;
        $started = hrtime(true);
        $page = $repository->paginate($input);
        $elapsed = (hrtime(true) - $started) / 1e6;

        if ($page->count() !== $expectedPage || $page->total() !== $expectedTotal) {
            throw new RuntimeException('Unexpected pagination result for '.$label);
        }

        if ($run > 0) {
            $samples[] = [$elapsed, $queryCount, $sqlTime];
        }
    }

    usort($samples, static fn (array $left, array $right): int => $left[0] <=> $right[0]);
    printf("%s: %.2f ms median, %d queries, %.2f ms SQL, %d row(s), %d total\n", $label, ...[...$samples[3], $expectedPage, $expectedTotal]);
}

printf("Peak allocated PHP memory: %.1f MiB\n", memory_get_peak_usage(true) / 1048576);
