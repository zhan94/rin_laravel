<?php

declare(strict_types=1);

namespace App\DTOs\Locations;

final readonly class LocationData
{
    public function __construct(
        public string $locationFrom,
        public string $locationTo,
        public string $fromTime,
        public string $toTime,
        public int $serviceId,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            locationFrom: trim((string) ($payload['location_from'] ?? '')),
            locationTo: trim((string) ($payload['location_to'] ?? '')),
            fromTime: self::normalizeTime((string) ($payload['from_time'] ?? '')),
            toTime: self::normalizeTime((string) ($payload['to_time'] ?? '')),
            serviceId: max(0, (int) ($payload['service_id'] ?? $payload['service'] ?? 0)),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function toPersistence(): array
    {
        return [
            'location_from' => $this->locationFrom,
            'location_to' => $this->locationTo,
            'from_time' => $this->fromTime,
            'to_time' => $this->toTime,
            'service_id' => $this->serviceId,
        ];
    }

    private static function normalizeTime(string $value): string
    {
        $raw = trim($value);
        if ($raw === '') {
            return '';
        }

        $chunks = explode(':', $raw);
        if (count($chunks) !== 2) {
            return $raw;
        }

        $hour = (int) $chunks[0];
        $minute = (int) $chunks[1];

        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return $raw;
        }

        return sprintf('%02d:%02d', $hour, $minute);
    }
}
