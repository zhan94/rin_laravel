<?php

declare(strict_types=1);

namespace App\DTOs\Bonus;

use App\Support\Localization\LocalizedText;
use App\Support\Pricing\EurCurrency;
use DateTimeImmutable;

final readonly class BonusData
{
    /**
     * @param  list<BonusPriceData>  $prices
     */
    public function __construct(
        public string $title,
        /** @var array<string,string> */
        public array $translations,
        public ?int $titleKey,
        public string $locationFrom,
        public string $locationTo,
        public ?string $dateFrom,
        public ?string $dateTo,
        public ?string $description,
        public ?float $priceEur,
        public array $prices,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $title = trim((string) ($payload['title'] ?? ''));
        $translations = LocalizedText::normalize(
            $payload['translations'] ?? null,
            $title,
        );

        $rows = $payload['prices'] ?? [];
        $prices = [];

        if (is_array($rows)) {
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $priceData = BonusPriceData::fromArray($row);
                if ($priceData->days <= 0) {
                    continue;
                }

                $prices[] = $priceData;
            }
        }

        $basePrice = EurCurrency::normalizeAmount($payload['price_eur'] ?? $payload['price'] ?? null);
        if ($basePrice === null && $prices !== []) {
            $basePrice = $prices[0]->priceEur;
        }

        return new self(
            title: LocalizedText::resolve($translations, 'en', $title),
            translations: $translations,
            titleKey: isset($payload['title_key']) ? (int) $payload['title_key'] : null,
            locationFrom: trim((string) ($payload['location_from'] ?? '')),
            locationTo: trim((string) ($payload['location_to'] ?? '')),
            dateFrom: self::normalizeDate($payload['date_from'] ?? null),
            dateTo: self::normalizeDate($payload['date_to'] ?? null),
            description: self::nullableString($payload['description'] ?? null),
            priceEur: $basePrice,
            prices: $prices,
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function toPersistence(): array
    {
        $fallbackTitle = LocalizedText::resolve($this->translations, 'en', $this->title);

        return [
            'title' => $fallbackTitle,
            'title_translations' => $this->translations,
            'title_key' => $this->titleKey,
            'location_from' => $this->locationFrom,
            'location_to' => $this->locationTo,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'description' => $this->description,
            'price' => $this->priceEur,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private static function normalizeDate(mixed $value): ?string
    {
        $raw = trim((string) ($value ?? ''));
        if ($raw === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $raw);
        if (! $date instanceof DateTimeImmutable) {
            return null;
        }

        return $date->format('Y-m-d');
    }
}
