<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Catalog\Concerns;

use App\Http\Resources\Api\V1\Catalog\CatalogPriceResource;
use Illuminate\Http\Request;

trait FormatsCatalogItemResource
{
    /**
     * @return array<string,mixed>
     */
    protected function catalogItemArray(Request $request): array
    {
        $translations = $this->displayTranslations();

        $serviceTranslations = $this->relationLoaded('serviceModel') && $this->serviceModel !== null
            ? $this->serviceModel->displayTranslations()
            : [];

        return [
            'id' => (int) $this->id,
            'title' => $this->displayTitle($request->getLocale()),
            'translations' => $translations,
            'service_id' => (int) $this->service,
            'service' => $this->relationLoaded('serviceModel') && $this->serviceModel !== null
                ? [
                    'id' => (int) $this->serviceModel->id,
                    'title' => $this->serviceModel->displayTitle($request->getLocale()),
                    'translations' => $serviceTranslations,
                ]
                : null,
            'prices' => CatalogPriceResource::collection($this->whenLoaded('prices'))->resolve(),
            'prices_count' => $this->relationLoaded('prices') ? $this->prices->count() : null,
        ];
    }
}
