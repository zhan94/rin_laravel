<?php

declare(strict_types=1);

namespace App\DTOs\Bonus;

use App\Support\Pricing\BgnCurrency;

final readonly class BonusPriceData
{
    public function __construct(
        public int $days,
        public float $priceEur,
        public float $priceBgn,
        public float $perDayBgn,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $days = max(0, (int) ($payload['days'] ?? 0));
        $priceEur = BgnCurrency::normalizeEurAmount($payload['price_eur'] ?? null) ?? 0.0;

        $priceBgn = BgnCurrency::fromEur($priceEur);
        $perDayBgn = $days > 0 ? round($priceBgn / $days, 2) : 0.0;

        return new self(
            days: $days,
            priceEur: $priceEur,
            priceBgn: $priceBgn,
            perDayBgn: $perDayBgn,
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function toPersistence(string $foreignColumn, int $foreignId): array
    {
        return [
            $foreignColumn => $foreignId,
            'price' => BgnCurrency::formatDecimal($this->priceBgn),
            'days' => $this->days,
            'per_day' => BgnCurrency::formatDecimal($this->perDayBgn),
        ];
    }
}
