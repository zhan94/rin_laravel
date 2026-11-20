<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Booking;
use Carbon\CarbonImmutable;
use Database\Seeders\Support\SeedCatalog;
use Database\Seeders\Support\SyntheticDatabase;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class BenchmarkDatabaseSeeder extends Seeder
{
    private const CUSTOMERS = 100_000;

    private const SERVICES = 100;

    private const BOOKINGS = 150_000;

    private const BATCH_SIZE = 500;

    public function run(): void
    {
        if (config('database.synthetic_dataset') !== 'benchmark') {
            throw new RuntimeException('The benchmark requires RIN_DATASET=benchmark on its designated development database.');
        }
        SyntheticDatabase::seed('rin.compose.benchmark.v1', ['users', 'customers', 'bookings', 'issue_reports', 'logs'], function (): void {
            $this->call(DemoDatabaseSeeder::class);
            $today = CarbonImmutable::today();
            $this->seedCustomers($today);
            [$totals, $firstEquipment] = $this->seedCatalogs();
            $this->seedSchedule($today, $totals, $firstEquipment);
        });
    }

    private function seedCustomers(CarbonImmutable $today): void
    {
        $firstNames = ['Avery', 'Robin', 'Alex', 'Sam', 'Charlie', 'Jordan', 'Morgan', 'Taylor'];
        $birthDates = array_map(
            static fn (int $months): string => $today->subMonths($months)->toDateString(),
            range(90, 220),
        );
        $firstId = (int) DB::table('customers')->max('id') + 1;

        for ($first = $firstId; $first <= self::CUSTOMERS; $first += self::BATCH_SIZE) {
            $rows = [];
            for ($id = $first; $id < min($first + self::BATCH_SIZE, self::CUSTOMERS + 1); $id++) {
                $rows[] = [
                    'id' => $id,
                    'firstname' => $firstNames[$id % count($firstNames)],
                    'lastname' => sprintf('Benchmark Family %06d', $id),
                    'date_birth' => $birthDates[$id % count($birthDates)],
                    'parent_name' => 'Benchmark Guardian '.$id,
                    'email' => 'benchmark.'.$id.'@example.test',
                    'active' => true,
                ];
            }
            DB::table('customers')->insert($rows);
        }
    }

    /** @return array{array<int,float>, int} */
    private function seedCatalogs(): array
    {
        $totals = DB::table('services_price')->where('days', 3)->pluck('price', 'services')
            ->map(static fn (mixed $price): float => (float) $price)->all();
        $services = [];
        $prices = [];
        $activities = ['Snowshoe trek', 'Mountain hike', 'Orienteering', 'Valley nature walk', 'Trail skills workshop'];
        $routes = ['Pine Pass', 'Cedar Valley', 'Silver Ridge', 'Alpine Meadow', 'North Basin'];

        for ($serviceId = 1; $serviceId <= self::SERVICES; $serviceId++) {
            if (isset($totals[$serviceId])) {
                continue;
            }

            $title = sprintf('%s · %s %02d', $activities[$serviceId % 5], $routes[intdiv($serviceId, 5) % 5], $serviceId);
            $rate = 26 + $serviceId % 19;
            $totals[$serviceId] = 3 * $rate;
            $services[] = ['id' => $serviceId, 'title' => $title, 'max' => 32 + $serviceId % 9, 'active' => true];
            $prices[] = $this->price('services', $serviceId, 1, $rate);
            $prices[] = $this->price('services', $serviceId, 3, $rate);
        }
        $this->insert('services', $services);
        $this->insert('services_price', $prices);

        $firstEquipment = (int) DB::table('equips')->max('id') + 1;
        $equipmentCount = 3_000 - DB::table('equips')->count();
        $equipment = [];
        $prices = [];
        for ($offset = 0; $offset < $equipmentCount; $offset++) {
            $equipmentId = $firstEquipment + $offset;
            $equipment[] = ['id' => $equipmentId, 'title' => sprintf('Junior ski set %04d', $offset + 1), 'service' => 1];
            $prices[] = $this->price('equip_id', $equipmentId, 1, 12);
            $prices[] = $this->price('equip_id', $equipmentId, 3, 12);
        }
        $this->insert('equips', $equipment);
        $this->insert('equips_price', $prices);

        return [$totals, $firstEquipment];
    }

    /** @param array<int,float> $totals */
    private function seedSchedule(CarbonImmutable $today, array $totals, int $nextEquipment): void
    {
        $periods = $this->calendar($today);
        $existingStart = (string) DB::table('service_slots')->min('date_start');
        $existingEnd = (string) DB::table('service_slots')->max('date_end');
        $corePeriods = array_filter($periods, static fn (array $dates): bool => $dates[0] > $existingEnd || end($dates) < $existingStart);
        $capacities = DB::table('services')->pluck('max', 'id')->all();
        $coreCapacities = array_intersect_key($capacities, SeedCatalog::serviceTitles());
        $periodCount = (count($capacities) - count($coreCapacities)) * count($periods);
        $bookingCount = self::BOOKINGS - DB::table('bookings')->count() - array_sum($coreCapacities) * count($corePeriods);
        $pickups = DB::table('locations')->orderBy('id')->get(['id', 'service_id'])->groupBy('service_id')
            ->map(static fn ($rows): array => $rows->pluck('id')->all());
        $perPeriod = intdiv($bookingCount, $periodCount);
        $extra = $bookingCount % $periodCount;
        $nextSlot = (int) DB::table('service_slots')->max('id') + 1;
        $nextDate = (int) DB::table('service_dates')->max('id') + 1;
        $nextBooking = (int) DB::table('bookings')->max('id') + 1;
        $cardId = (int) DB::table('cards')->where('service', 1)->value('id');
        $cardTotal = (float) DB::table('cards_price')->where('card_id', $cardId)->where('days', 3)->value('price');
        $equipmentTotal = 3 * 12;
        $todayDate = $today->toDateString();
        $periodIndex = 0;
        $reservation = 0;

        foreach ($totals as $serviceId => $total) {
            $isCore = isset($coreCapacities[$serviceId]);
            $capacity = (int) $capacities[$serviceId];
            $routes = $pickups->get($serviceId) ?? $pickups->get(0, []);
            if ($routes === []) {
                throw new RuntimeException('The benchmark service has no compatible pickup route.');
            }
            $rows = ['service_slots' => [], 'service_dates' => [], 'bookings' => [],
                'booking_dates' => [], 'booking_payments' => [], 'booking_winter_details' => []];

            foreach ($isCore ? $corePeriods : $periods as $dates) {
                $firstDate = $nextDate;
                $slotId = $nextSlot++;
                $rows['service_slots'][] = [
                    'id' => $slotId,
                    'service_id' => $serviceId,
                    'name' => 'Operating period '.$dates[0],
                    'date_start' => $dates[0],
                    'date_end' => end($dates),
                    'max' => $capacity,
                    'season_type' => SeedCatalog::seasonType($serviceId)->value,
                ];
                foreach ($dates as $date) {
                    $rows['service_dates'][] = ['id' => $nextDate++, 'slot_id' => $slotId, 'slot_date' => $date, 'is_active' => true];
                }

                // Core periods use at most the configured capacity across the whole block.
                $size = $isCore ? $capacity : $perPeriod + (int) ($periodIndex++ < $extra);
                for ($offset = 0; $offset < $size; $offset++) {
                    $bookingId = $nextBooking++;
                    $number = $reservation++;
                    $start = $offset % (count($dates) - 2);
                    $equipId = $serviceId === 1 ? $nextEquipment++ : null;
                    $includeCard = $serviceId === 1 && $number % 3 === 0;
                    $amount = round($total + ($equipId !== null ? $equipmentTotal : 0) + ($includeCard ? $cardTotal : 0), 2);
                    $paid = $number % 5 === 0 ? 0 : ($number % 2 === 0 ? round($amount / 2, 2) : $amount);
                    $cancelled = $paid === 0 && $number % 17 === 0 && $dates[$start + 2] < $todayDate;
                    $recordedAt = min($todayDate, $dates[$start]).' 09:00:00';

                    $rows['bookings'][] = [
                        'id' => $bookingId,
                        'customer_id' => 1 + $number % self::CUSTOMERS,
                        'service' => $serviceId,
                        'service_type' => Booking::SERVICE_TYPE_SERVICE,
                        'location' => $routes[$number % count($routes)],
                        'sum_paid' => $amount,
                        'paid' => $paid,
                        'active' => ! $cancelled,
                        'approved' => $number % 7 !== 0,
                    ];
                    $rows['booking_payments'][] = ['booking_id' => $bookingId, 'sum_paid' => $amount, 'paid' => $paid,
                        'created_at' => $recordedAt, 'updated_at' => $recordedAt];
                    for ($day = 0; $day < 3; $day++) {
                        $rows['booking_dates'][] = ['booking_id' => $bookingId, 'service_date_id' => $firstDate + $start + $day];
                    }
                    if (in_array($serviceId, Booking::SKI_SERVICE_IDS, true)) {
                        $rows['booking_winter_details'][] = [
                            'booking_id' => $bookingId, 'skill_id' => 1 + $number % 3, 'equip_id' => $equipId,
                            'equip_enabled' => $equipId !== null, 'card_id' => $includeCard ? $cardId : null,
                            'shoe_size' => $equipId !== null ? 33 + $number % 9 : null,
                            'height' => $equipId !== null ? 130 + $number % 41 : null,
                            'created_at' => $recordedAt, 'updated_at' => $recordedAt,
                        ];
                    }
                }
            }

            foreach ($rows as $table => $batch) {
                $this->insert($table, $batch);
            }
        }
    }

    /** @return list<list<string>> */
    private function calendar(CarbonImmutable $today): array
    {
        $start = $today->subDays(160);
        $periods = [];
        for ($period = 0; $period < 40; $period++) {
            $length = $period < 25 ? 8 : 7;
            $dates = [];
            for ($day = 0; $day < $length; $day++) {
                $dates[] = $start->toDateString();
                $start = $start->addDay();
            }
            $periods[] = $dates;
        }

        return $periods;
    }

    /** @return array<string,int|float> */
    private function price(string $relation, int $id, int $days, int $rate): array
    {
        return [$relation => $id, 'days' => $days, 'price' => $rate * $days, 'per_day' => $rate];
    }

    /** @param list<array<string,scalar|null>> $rows */
    private function insert(string $table, array $rows): void
    {
        foreach (array_chunk($rows, self::BATCH_SIZE) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }
}
