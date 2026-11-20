<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\DTOs\Services\ServiceSlotData;
use App\Models\Service;
use App\Services\ServiceSlotService;
use Carbon\CarbonImmutable;
use Database\Seeders\Support\SeedCatalog;
use Database\Seeders\Support\SyntheticDatabase;
use Illuminate\Database\Seeder;

final class CoreBusinessSeeder extends Seeder
{
    public function run(ServiceSlotService $slots): void
    {
        SyntheticDatabase::seed('rin.core.catalog.v1', ['services', 'locations', 'cards', 'equips', 'service_slots', 'bonus'], function () use ($slots): void {
            $this->call([RoleSeeder::class, LegacyLookupSeeder::class, LegacyCatalogSeeder::class]);

            $today = CarbonImmutable::today();
            foreach (Service::query()->orderBy('id')->get() as $service) {
                foreach ([[-28, -15], [-7, 13], [22, 42]] as [$start, $end]) {
                    $slots->create($service, ServiceSlotData::fromArray([
                        'name' => 'Activity block '.$today->addDays($start)->format('d.m.Y'),
                        'max' => $service->max,
                        'season_type' => SeedCatalog::seasonType($service->id)->value,
                        'dates' => array_map(static fn (int $offset): string => $today->addDays($offset)->toDateString(), range($start, $end)),
                    ], $service->id));
                }
            }
        });
    }
}
