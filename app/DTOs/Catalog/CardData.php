<?php

declare(strict_types=1);

namespace App\DTOs\Catalog;

use App\DTOs\Catalog\Concerns\BuildsCatalogItemData;

final readonly class CardData
{
    use BuildsCatalogItemData;

    /**
     * @param  list<CatalogPriceData>  $prices
     */
    public function __construct(
        public string $title,
        /** @var array<string,string> */
        public array $translations,
        public int $serviceId,
        public array $prices,
    ) {}
}
