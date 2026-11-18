<?php

declare(strict_types=1);

namespace App\DTOs\Catalog\Concerns;

use App\DTOs\Catalog\CatalogPriceData;
use App\Support\Localization\LocalizedText;

trait BuildsCatalogItemData
{
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

        return new self(
            title: LocalizedText::resolve($translations, 'en', $title),
            translations: $translations,
            serviceId: max(0, (int) ($payload['service'] ?? $payload['service_id'] ?? 0)),
            prices: self::normalizeCatalogPrices($payload['prices'] ?? []),
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
            'service' => $this->serviceId,
        ];
    }

    /**
     * @return list<CatalogPriceData>
     */
    private static function normalizeCatalogPrices(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $prices = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $priceData = CatalogPriceData::fromArray($row);
            if ($priceData->days <= 0) {
                continue;
            }

            $prices[] = $priceData;
        }

        return $prices;
    }
}
