<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

final class SeedCatalog
{
    /**
     * @return array<int,string>
     */
    public static function serviceTitles(): array
    {
        return [
            1 => 'Ski School',
            2 => 'Mountain School',
            3 => 'Summer Club',
            4 => 'Summer Camp',
            11 => 'Snowboard',
            14 => 'Swim Camp',
            15 => 'Kindergarten Swimming',
            16 => 'Student Swimming',
            17 => 'Winter Camp',
        ];
    }

    /**
     * @return list<array<string,mixed>>
     */
    public static function services(): array
    {
        return array_map(
            static fn (int $id, string $title): array => [
                'id' => $id,
                ...self::localizedTitlePayload($title),
                'title_key' => null,
                'max' => match ($id) {
                    1 => 24,
                    2, 3, 4 => 25,
                    11, 17 => 20,
                    14 => 18,
                    15 => 20,
                    16 => 80,
                    default => 0,
                },
                'active' => true,
            ],
            array_keys(self::serviceTitles()),
            self::serviceTitles(),
        );
    }

    /**
     * @return list<array{id:int,services:int,price:float,days:int,per_day:float}>
     */
    public static function servicePrices(): array
    {
        return [
            ['id' => 295, 'services' => 3, 'price' => 250.00, 'days' => 5, 'per_day' => 50.00],
            ['id' => 296, 'services' => 3, 'price' => 450.00, 'days' => 10, 'per_day' => 45.00],
            ['id' => 297, 'services' => 4, 'price' => 180.00, 'days' => 3, 'per_day' => 60.00],
            ['id' => 300, 'services' => 2, 'price' => 350.00, 'days' => 10, 'per_day' => 35.00],
            ['id' => 301, 'services' => 2, 'price' => 900.00, 'days' => 30, 'per_day' => 30.00],
        ];
    }

    /**
     * @return array<int,string>
     */
    public static function bonusTitles(): array
    {
        return [
            1 => 'Swim Camp',
            2 => 'Winter Camp',
            3 => 'Winter Camp',
        ];
    }

    /**
     * @return list<array<string,mixed>>
     */
    public static function bonuses(): array
    {
        return [
            [
                'id' => 1,
                ...self::localizedTitlePayload(self::bonusTitles()[1]),
                'title_key' => null,
                'location_from' => 'National History Museum Boyana',
                'location_to' => 'National History Museum Boyana',
                'date_from' => '2020-08-24',
                'date_to' => '2020-08-28',
                'price' => null,
                'description' => '5.1. Swim camp for advanced and beginner participants.',
            ],
            [
                'id' => 2,
                ...self::localizedTitlePayload(self::bonusTitles()[2]),
                'title_key' => null,
                'location_from' => 'National History Museum Boyana',
                'location_to' => 'National History Museum Boyana',
                'date_from' => '2020-12-26',
                'date_to' => '2020-11-30',
                'price' => null,
                'description' => null,
            ],
            [
                'id' => 3,
                ...self::localizedTitlePayload(self::bonusTitles()[3]),
                'title_key' => null,
                'location_from' => 'National History Museum Boyana',
                'location_to' => 'National History Museum Boyana',
                'date_from' => '2020-12-26',
                'date_to' => '2020-12-30',
                'price' => null,
                'description' => null,
            ],
        ];
    }

    /**
     * @return list<array{id:int,bonus_id:int,price:float,days:int,per_day:float}>
     */
    public static function bonusPrices(): array
    {
        return [
            ['id' => 4, 'bonus_id' => 3, 'price' => 100.00, 'days' => 10, 'per_day' => 10.00],
            ['id' => 5, 'bonus_id' => 3, 'price' => 180.00, 'days' => 20, 'per_day' => 9.00],
            ['id' => 6, 'bonus_id' => 3, 'price' => 260.00, 'days' => 30, 'per_day' => 8.67],
            ['id' => 7, 'bonus_id' => 1, 'price' => 360.00, 'days' => 5, 'per_day' => 72.00],
            ['id' => 8, 'bonus_id' => 2, 'price' => 440.00, 'days' => 5, 'per_day' => 88.00],
            ['id' => 9, 'bonus_id' => 3, 'price' => 440.00, 'days' => 5, 'per_day' => 88.00],
        ];
    }

    /**
     * @return array<int,string>
     */
    public static function cardTitles(): array
    {
        return [
            1 => 'Aleko',
            2 => 'Vetrovala',
            3 => 'Machirski',
            4 => 'Ofeliite Ski Zone Machirski',
        ];
    }

    /**
     * @return list<array<string,mixed>>
     */
    public static function cards(): array
    {
        return [
            ['id' => 1, ...self::localizedTitlePayload(self::cardTitles()[1]), 'service' => 1],
            ['id' => 2, ...self::localizedTitlePayload(self::cardTitles()[2]), 'service' => 1],
            ['id' => 3, ...self::localizedTitlePayload(self::cardTitles()[3]), 'service' => 2],
            ['id' => 4, ...self::localizedTitlePayload(self::cardTitles()[4]), 'service' => 1],
        ];
    }

    /**
     * @return list<array{id:int,card_id:int,price:float,days:int,per_day:float}>
     */
    public static function cardPrices(): array
    {
        return [
            ['id' => 22, 'card_id' => 2, 'price' => 4.00, 'days' => 16, 'per_day' => 0.25],
            ['id' => 23, 'card_id' => 2, 'price' => 12.00, 'days' => 55, 'per_day' => 0.22],
            ['id' => 24, 'card_id' => 2, 'price' => 55.00, 'days' => 111, 'per_day' => 0.50],
            ['id' => 28, 'card_id' => 1, 'price' => 80.00, 'days' => 20, 'per_day' => 4.00],
            ['id' => 29, 'card_id' => 1, 'price' => 120.00, 'days' => 30, 'per_day' => 4.00],
            ['id' => 30, 'card_id' => 1, 'price' => 150.00, 'days' => 40, 'per_day' => 3.75],
            ['id' => 34, 'card_id' => 4, 'price' => 35.00, 'days' => 1, 'per_day' => 35.00],
            ['id' => 35, 'card_id' => 4, 'price' => 25.00, 'days' => 1, 'per_day' => 25.00],
        ];
    }

    /**
     * @return array<int,string>
     */
    public static function equipTitles(): array
    {
        return [
            17 => 'Partial Package',
            18 => 'Training',
            19 => 'Full Package',
        ];
    }

    /**
     * @return list<array<string,mixed>>
     */
    public static function equips(): array
    {
        return [
            ['id' => 17, ...self::localizedTitlePayload(self::equipTitles()[17]), 'service' => 1],
            ['id' => 18, ...self::localizedTitlePayload(self::equipTitles()[18]), 'service' => 1],
            ['id' => 19, ...self::localizedTitlePayload(self::equipTitles()[19]), 'service' => 1],
        ];
    }

    /**
     * @return list<array{id:int,equip_id:int,price:float,days:int,per_day:float}>
     */
    public static function equipPrices(): array
    {
        return [
            ['id' => 36, 'equip_id' => 17, 'price' => 60.00, 'days' => 1, 'per_day' => 60.00],
            ['id' => 37, 'equip_id' => 17, 'price' => 110.00, 'days' => 2, 'per_day' => 55.00],
            ['id' => 38, 'equip_id' => 17, 'price' => 155.00, 'days' => 3, 'per_day' => 51.67],
            ['id' => 39, 'equip_id' => 17, 'price' => 190.00, 'days' => 4, 'per_day' => 47.50],
            ['id' => 40, 'equip_id' => 17, 'price' => 230.00, 'days' => 5, 'per_day' => 46.00],
            ['id' => 41, 'equip_id' => 17, 'price' => 260.00, 'days' => 6, 'per_day' => 43.33],
            ['id' => 77, 'equip_id' => 19, 'price' => 70.00, 'days' => 1, 'per_day' => 70.00],
            ['id' => 78, 'equip_id' => 19, 'price' => 120.00, 'days' => 2, 'per_day' => 60.00],
            ['id' => 79, 'equip_id' => 19, 'price' => 165.00, 'days' => 3, 'per_day' => 55.00],
            ['id' => 80, 'equip_id' => 19, 'price' => 200.00, 'days' => 4, 'per_day' => 50.00],
            ['id' => 81, 'equip_id' => 19, 'price' => 240.00, 'days' => 5, 'per_day' => 48.00],
            ['id' => 82, 'equip_id' => 19, 'price' => 270.00, 'days' => 6, 'per_day' => 45.00],
        ];
    }

    /**
     * @return list<array{id:int,location_from:string,location_to:string,from_time:string,to_time:string,service_id:int}>
     */
    public static function locations(): array
    {
        return [
            ['id' => 1, 'location_from' => 'HIT', 'location_to' => 'HIT', 'from_time' => '8:00', 'to_time' => '16:45', 'service_id' => 0],
            ['id' => 2, 'location_from' => 'Russian Embassy', 'location_to' => 'Russian Embassy', 'from_time' => '8:20', 'to_time' => '16:30', 'service_id' => 0],
            ['id' => 3, 'location_from' => 'Evlogi Georgiev', 'location_to' => 'NDK', 'from_time' => '8:30', 'to_time' => '16:20', 'service_id' => 0],
            ['id' => 4, 'location_from' => 'NDK', 'location_to' => 'NDK', 'from_time' => '08:30', 'to_time' => '16:20', 'service_id' => 0],
            ['id' => 5, 'location_from' => 'T. Kableshkov', 'location_to' => 'BILLA Bulgaria Blvd.', 'from_time' => '8:50', 'to_time' => '16:10', 'service_id' => 0],
            ['id' => 7, 'location_from' => 'SHELL Bulgaria', 'location_to' => 'SHELL Bulgaria', 'from_time' => '8:40', 'to_time' => '16:15', 'service_id' => 0],
            ['id' => 8, 'location_from' => 'Skiorkata Stop - Boyana', 'location_to' => 'Skiorkata Stop - Boyana', 'from_time' => '9:00', 'to_time' => '16:00', 'service_id' => 0],
            ['id' => 33, 'location_from' => 'Camelini Montessori Kindergarten', 'location_to' => 'Camelini Montessori Kindergarten', 'from_time' => '13:30', 'to_time' => '15:40', 'service_id' => 15],
            ['id' => 35, 'location_from' => '51 SU Elisaveta Bagryana', 'location_to' => '51 SU Elisaveta Bagryana', 'from_time' => '8:00', 'to_time' => '10.30', 'service_id' => 16],
            ['id' => 39, 'location_from' => 'Kableshkov Blvd. - Bulgaria Blvd.', 'location_to' => 'Kableshkov Blvd. - Bulgaria Blvd.', 'from_time' => '8:50', 'to_time' => '16:10', 'service_id' => 11],
            ['id' => 41, 'location_from' => 'National History Museum Boyana', 'location_to' => 'National History Museum Boyana', 'from_time' => '9:00', 'to_time' => '17:00', 'service_id' => 17],
            ['id' => 42, 'location_from' => 'National History Museum Boyana', 'location_to' => 'National History Museum Boyana', 'from_time' => '9:00', 'to_time' => '17:00', 'service_id' => 14],
        ];
    }

    /**
     * @return array<string,string>
     */
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

    /**
     * @return array<string,mixed>
     */
    public static function localizedTitlePayload(string $title): array
    {
        return [
            'title' => $title,
            'title_bg' => $title,
            'title_en' => $title,
            'title_translations' => self::translations($title),
        ];
    }

    /**
     * @return array<string,string>
     */
    public static function translations(string $title): array
    {
        return [
            'en' => $title,
            'de' => $title,
            'es' => $title,
        ];
    }
}
