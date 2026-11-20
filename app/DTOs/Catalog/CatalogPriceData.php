<?php

declare(strict_types=1);

namespace App\DTOs\Catalog;

use App\Support\Pricing\EurCurrency;

final readonly class CatalogPriceData
{
    public function __construct(
        public int $days,
        public float $priceEur,
        public float $perDayEur,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $days = max(0, (int) ($payload['days'] ?? 0));
        $priceEur = EurCurrency::normalizeAmount($payload['price_eur'] ?? null) ?? 0.0;

        $perDayEur = $days > 0 ? round($priceEur / $days, 2) : 0.0;

        return new self(
            days: $days,
            priceEur: $priceEur,
            perDayEur: $perDayEur,
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function toPersistence(string $foreignColumn, int $foreignId): array
    {
        return [
            $foreignColumn => $foreignId,
            'price' => $this->priceEur,
            'days' => $this->days,
            'per_day' => $this->perDayEur,
        ];
    }
}
