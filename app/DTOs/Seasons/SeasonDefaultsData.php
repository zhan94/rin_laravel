<?php

declare(strict_types=1);

namespace App\DTOs\Seasons;

final readonly class SeasonDefaultsData
{
    public function __construct(
        public int $startMonth,
        public int $startDay,
        public int $endMonth,
        public int $endDay,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $startPair = self::parseDayMonth(
            (string) ($payload['default_start_md'] ?? ''),
            (int) ($payload['default_start_month'] ?? 0),
            (int) ($payload['default_start_day'] ?? 0),
        );

        $endPair = self::parseDayMonth(
            (string) ($payload['default_end_md'] ?? ''),
            (int) ($payload['default_end_month'] ?? 0),
            (int) ($payload['default_end_day'] ?? 0),
        );

        return new self(
            startMonth: $startPair['month'],
            startDay: $startPair['day'],
            endMonth: $endPair['month'],
            endDay: $endPair['day'],
        );
    }

    /**
     * @return array{month:int,day:int}
     */
    private static function parseDayMonth(string $dayMonthInput, int $fallbackMonth, int $fallbackDay): array
    {
        $dayMonthInput = trim($dayMonthInput);

        if ($dayMonthInput !== '' && preg_match('/^\s*(\d{1,2})\s*-\s*(\d{1,2})\s*$/', $dayMonthInput, $matches) === 1) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];

            return [
                'month' => $month,
                'day' => $day,
            ];
        }

        return [
            'month' => $fallbackMonth,
            'day' => $fallbackDay,
        ];
    }
}
