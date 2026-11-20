<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

use App\Enums\SeasonType;
use Carbon\CarbonImmutable;

final class SeedCatalog
{
    // Snowboard uses Laravel's existing winter service identity.
    private const SERVICES = [
        1 => ['Ski school', 'Skischule', 'Escuela de esquí', 'ski_school', 12, [1 => 30.68, 3 => 84.36, 5 => 138.05], SeasonType::WINTER],
        11 => ['Snowboard school', 'Snowboardschule', 'Escuela de snowboard', 'snowboard_school', 10, [1 => 33.23, 3 => 92.03, 5 => 150.83], SeasonType::WINTER],
        3 => ['Swimming lessons', 'Schwimmkurse', 'Clases de natación', 'swimming_lessons', 8, [1 => 15.34, 3 => 38.35, 5 => 61.36], SeasonType::SUMMER],
        4 => ['Mountain walks', 'Bergwanderungen', 'Caminatas de montaña', 'mountain_walks', 15, [1 => 23.01, 3 => 61.36, 5 => 99.70], SeasonType::SUMMER],
        5 => ['Private coaching', 'Einzeltraining', 'Entrenamiento individual', 'private_coaching', 1, [1 => 46.02, 3 => 130.38, 5 => 214.74], SeasonType::OTHER],
    ];

    private const CARDS = [
        1 => ['Ski day pass', 'Ski-Tagespass', 'Pase diario de esquí', 1, 12.78],
        2 => ['Ski week pass', 'Ski-Wochenpass', 'Pase semanal de esquí', 1, 10.23],
        3 => ['Snowboard day pass', 'Snowboard-Tagespass', 'Pase diario de snowboard', 11, 12.78],
        4 => ['Snowboard week pass', 'Snowboard-Wochenpass', 'Pase semanal de snowboard', 11, 10.23],
    ];

    private const EQUIPMENT = [
        1 => ['Ski equipment set', 'Skiausrüstungsset', 'Equipo de esquí', 1, 7.67],
        2 => ['Snowboard equipment set', 'Snowboardausrüstungsset', 'Equipo de snowboard', 11, 9.20],
        3 => ['Swimming equipment set', 'Schwimmausrüstungsset', 'Equipo de natación', 3, 2.56],
        4 => ['Walking poles', 'Wanderstöcke', 'Bastones de senderismo', 4, 2.05],
    ];

    private const ACTIVITIES = [
        1 => ['Forest adventure day', 'Abenteuertag im Wald', 'Día de aventura en el bosque', 'forest_adventure_day', 'Market Square', 'Oakwood Activity Centre', -6, 1, 40.90],
        2 => ['Winter activity camp', 'Wintercamp', 'Campamento de invierno', 'winter_activity_camp', 'Central Station', 'Pine Ridge Lodge', 14, 5, 153.39],
        3 => ['Swimming weekend', 'Schwimmwochenende', 'Fin de semana de natación', 'swimming_weekend', 'Town Library', 'Bluewater Pool', 5, 2, 61.36],
    ];

    /** @return array<int,string> */
    public static function serviceTitles(): array
    {
        return array_map(static fn (array $service): string => $service[0], self::SERVICES);
    }

    /** @return list<array<string,mixed>> */
    public static function services(): array
    {
        $rows = [];
        foreach (self::SERVICES as $id => [$en, $de, $es, $key, $capacity]) {
            $rows[] = [
                'id' => $id,
                ...self::localizedTitlePayload($en, $de, $es),
                'legacy_title_key' => $key,
                'max' => $capacity,
                'active' => true,
            ];
        }

        return $rows;
    }

    public static function seasonType(int $serviceId): SeasonType
    {
        return self::SERVICES[$serviceId][6] ?? SeasonType::OTHER;
    }

    /** @return list<array<string,int|float>> */
    public static function servicePrices(): array
    {
        $rows = [];
        foreach (self::SERVICES as $id => $service) {
            foreach ($service[5] as $days => $price) {
                $rows[] = self::priceRow(count($rows) + 1, 'services', $id, $days, $price);
            }
        }

        return $rows;
    }

    /** @return array<int,string> */
    public static function cardTitles(): array
    {
        return array_map(static fn (array $card): string => $card[0], self::CARDS);
    }

    /** @return list<array<string,mixed>> */
    public static function cards(): array
    {
        return self::products(self::CARDS);
    }

    /** @return list<array<string,int|float>> */
    public static function cardPrices(): array
    {
        return self::productPrices(self::CARDS, 'card_id');
    }

    /** @return array<int,string> */
    public static function equipTitles(): array
    {
        return array_map(static fn (array $equipment): string => $equipment[0], self::EQUIPMENT);
    }

    /** @return list<array<string,mixed>> */
    public static function equips(): array
    {
        return self::products(self::EQUIPMENT);
    }

    /** @return list<array<string,int|float>> */
    public static function equipPrices(): array
    {
        return self::productPrices(self::EQUIPMENT, 'equip_id');
    }

    /** @return array<int,string> */
    public static function bonusTitles(): array
    {
        return array_map(static fn (array $activity): string => $activity[0], self::ACTIVITIES);
    }

    /** @return list<array<string,mixed>> */
    public static function bonuses(CarbonImmutable $today): array
    {
        $rows = [];
        foreach (self::ACTIVITIES as $id => [$en, $de, $es, $key, $pickup, $destination, $offset, $days, $price]) {
            $start = $today->addDays($offset);
            $rows[] = [
                'id' => $id,
                ...self::localizedTitlePayload($en, $de, $es),
                'legacy_title_key' => $key,
                'location_from' => $pickup,
                'location_to' => $destination,
                'date_from' => $start->toDateString(),
                'date_to' => $start->addDays($days - 1)->toDateString(),
                'price' => $price,
                'description' => 'Example programme with supervised activities and transport.',
                'active' => true,
            ];
        }

        return $rows;
    }

    /** @return list<array<string,int|float>> */
    public static function bonusPrices(): array
    {
        $rows = [];
        foreach (self::ACTIVITIES as $id => $activity) {
            foreach (array_unique([1, $activity[7]]) as $days) {
                $amount = $activity[8] / $activity[7] * $days;
                $rows[] = self::priceRow(count($rows) + 1, 'bonus_id', $id, $days, $amount);
            }
        }

        return $rows;
    }

    /** @return list<array<string,int|string|bool>> */
    public static function locations(): array
    {
        $routes = [
            [1, 'Central Station', 'Pine Ridge Ski Centre', '07:45', '17:15'],
            [1, 'Riverside School', 'Pine Ridge Ski Centre', '08:00', '17:30'],
            [11, 'Central Station', 'Snow Valley Centre', '07:30', '17:45'],
            [3, 'Town Library', 'Bluewater Pool', '09:00', '12:30'],
            [4, 'Market Square', 'Oakwood Trail Centre', '08:30', '16:30'],
            [4, 'Westside School', 'Oakwood Trail Centre', '08:45', '16:45'],
            [5, 'Central Station', 'Private Training Centre', '09:00', '17:00'],
        ];
        $districts = ['Northgate', 'Eastfield', 'Westbrook', 'Southbank', 'Oakwood', 'Lakeside', 'Riverside', 'Hillcrest'];
        foreach ([
            [1, 'Pine Ridge Ski Centre', 8, 17],
            [11, 'Snow Valley Centre', 9, 16],
            [3, 'Bluewater Pool', 7, 17],
            [4, 'Oakwood Trail Centre', 8, 16],
        ] as [$serviceId, $destination, $pickupHour, $returnHour]) {
            foreach ($districts as $index => $district) {
                $routes[] = [
                    $serviceId, $district.' Community Centre', $destination,
                    sprintf('%02d:%02d', $pickupHour, $index * 5),
                    sprintf('%02d:%02d', $returnHour, $index * 5),
                ];
            }
        }
        $routes[] = [0, 'Example Valley Shuttle Stop', 'Example Valley Visitor Centre', '09:00', '15:30'];

        return array_map(static fn (array $route, int $index): array => [
            'id' => $index + 1,
            'service_id' => $route[0],
            'location_from' => $route[1],
            'location_to' => $route[2],
            'from_time' => $route[3],
            'to_time' => $route[4],
            'active' => true,
        ], $routes, array_keys($routes));
    }

    /** @return array<string,string> */
    public static function roleTitles(): array
    {
        return [
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'manager' => 'Manager',
            'group_leader' => 'Group Leader',
            'driver' => 'Driver',
        ];
    }

    /** @return array<string,mixed> */
    public static function localizedTitlePayload(string $title, ?string $german = null, ?string $spanish = null): array
    {
        return [
            'title' => $title,
            'title_bg' => $title,
            'title_en' => $title,
            'title_translations' => json_encode(['en' => $title, 'de' => $german ?? $title, 'es' => $spanish ?? $title], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ];
    }

    /** @return array<string,string> */
    public static function translations(string $title): array
    {
        return ['en' => $title, 'de' => $title, 'es' => $title];
    }

    /**
     * @param  array<int,array{string,string,string,int,float}>  $products
     * @return list<array<string,mixed>>
     */
    private static function products(array $products): array
    {
        $rows = [];
        foreach ($products as $id => [$en, $de, $es, $serviceId]) {
            $rows[] = ['id' => $id, ...self::localizedTitlePayload($en, $de, $es), 'service' => $serviceId, 'active' => true];
        }

        return $rows;
    }

    /**
     * @param  array<int,array{string,string,string,int,float}>  $products
     * @return list<array<string,int|float>>
     */
    private static function productPrices(array $products, string $foreignKey): array
    {
        $rows = [];
        foreach ($products as $id => $product) {
            foreach ([1, 3, 5] as $days) {
                $rows[] = self::priceRow(count($rows) + 1, $foreignKey, $id, $days, $product[4] * $days);
            }
        }

        return $rows;
    }

    /** @return array<string,int|float> */
    private static function priceRow(int $id, string $foreignKey, int $parentId, int $days, float $amount): array
    {
        return ['id' => $id, $foreignKey => $parentId, 'days' => $days, 'price' => round($amount, 2), 'per_day' => round($amount / $days, 2)];
    }
}
