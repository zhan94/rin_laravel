<?php

declare(strict_types=1);

namespace App\DTOs\Services;

use App\Enums\SeasonType;
use DateTimeImmutable;

final readonly class ServiceSlotData
{
    /**
     * @param  list<string>  $dates
     */
    public function __construct(
        public int $serviceId,
        public string $name,
        public int $max,
        public SeasonType $seasonType,
        public array $dates,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload, int $serviceId): self
    {
        return new self(
            serviceId: $serviceId,
            name: trim((string) ($payload['name'] ?? '')),
            max: max(0, (int) ($payload['max'] ?? 0)),
            seasonType: SeasonType::tryFrom((string) ($payload['season_type'] ?? SeasonType::OTHER->value)) ?? SeasonType::OTHER,
            dates: self::normalizeDates($payload['dates'] ?? []),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function toSlotPersistence(): array
    {
        return [
            'service_id' => $this->serviceId,
            'name' => $this->name,
            'max' => $this->max,
            'date_start' => $this->dateStart(),
            'date_end' => $this->dateEnd(),
            'season_type' => $this->seasonType->value,
        ];
    }

    public function dateStart(): ?string
    {
        return $this->dates[0] ?? null;
    }

    public function dateEnd(): ?string
    {
        if ($this->dates === []) {
            return null;
        }

        return $this->dates[count($this->dates) - 1];
    }

    /**
     * @return list<string>
     */
    private static function normalizeDates(mixed $input): array
    {
        $values = [];

        if (is_string($input)) {
            $values = array_map('trim', explode(',', $input));
        } elseif (is_array($input)) {
            $values = $input;
        }

        $normalized = [];
        foreach ($values as $value) {
            $candidate = trim((string) $value);
            if ($candidate === '') {
                continue;
            }

            $date = DateTimeImmutable::createFromFormat('Y-m-d', $candidate);
            if (! $date instanceof DateTimeImmutable) {
                continue;
            }

            $normalized[$date->format('Y-m-d')] = true;
        }

        $dates = array_keys($normalized);
        sort($dates);

        return $dates;
    }
}
