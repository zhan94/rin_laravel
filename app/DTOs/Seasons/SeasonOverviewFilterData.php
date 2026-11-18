<?php

declare(strict_types=1);

namespace App\DTOs\Seasons;

use App\Enums\SeasonViewType;

final readonly class SeasonOverviewFilterData
{
    public function __construct(
        public SeasonViewType $type,
        public ?int $serviceId,
        public ?string $bonusTitle,
        public ?int $seasonId,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $type = SeasonViewType::tryFrom((string) ($payload['type'] ?? SeasonViewType::SERVICE->value))
            ?? SeasonViewType::SERVICE;

        $serviceId = null;
        $bonusTitle = null;

        $serviceRaw = $payload['service'] ?? null;
        if ($type === SeasonViewType::SERVICE) {
            $parsedService = (int) $serviceRaw;
            $serviceId = $parsedService > 0 ? $parsedService : null;
        } else {
            $title = trim((string) $serviceRaw);
            $bonusTitle = $title !== '' ? $title : null;
        }

        $seasonIdRaw = (int) ($payload['season'] ?? 0);

        return new self(
            type: $type,
            serviceId: $serviceId,
            bonusTitle: $bonusTitle,
            seasonId: $seasonIdRaw > 0 ? $seasonIdRaw : null,
        );
    }
}
