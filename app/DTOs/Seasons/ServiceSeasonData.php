<?php

declare(strict_types=1);

namespace App\DTOs\Seasons;

use DateTimeImmutable;

final readonly class ServiceSeasonData
{
    public function __construct(
        public int $serviceId,
        public string $name,
        public string $startDate,
        public string $endDate,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload, int $fallbackServiceId = 0): self
    {
        $serviceId = (int) ($payload['service_id'] ?? $fallbackServiceId);

        return new self(
            serviceId: $serviceId > 0 ? $serviceId : $fallbackServiceId,
            name: trim((string) ($payload['season_name'] ?? $payload['name'] ?? '')),
            startDate: self::normalizeDate((string) ($payload['season_start'] ?? $payload['date_start'] ?? '')),
            endDate: self::normalizeDate((string) ($payload['season_end'] ?? $payload['date_end'] ?? '')),
        );
    }

    private static function normalizeDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $formats = ['Y-m-d', 'd-m-Y', 'd.m.Y', 'd/m/Y'];
        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $value);
            if ($date instanceof DateTimeImmutable && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return '';
    }
}
