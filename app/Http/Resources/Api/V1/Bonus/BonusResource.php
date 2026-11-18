<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Bonus;

use App\Http\Resources\Concerns\FormatsResourceValues;
use App\Support\Pricing\BgnCurrency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Bonus */
class BonusResource extends JsonResource
{
    use FormatsResourceValues;

    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        $priceBgn = $this->price !== null ? (float) $this->price : null;
        $translations = $this->displayTranslations();

        return [
            'id' => (int) $this->id,
            'title' => $this->displayTitle($request->getLocale()),
            'translations' => $translations,
            'title_key' => $this->title_key !== null ? (int) $this->title_key : null,
            'location_from' => (string) ($this->location_from ?? ''),
            'location_to' => (string) ($this->location_to ?? ''),
            'date_from' => $this->formatDate($this->date_from),
            'date_to' => $this->formatDate($this->date_to),
            'description' => $this->description !== null ? (string) $this->description : null,
            'price_bgn' => $priceBgn !== null ? round($priceBgn, 2) : null,
            'price_eur' => BgnCurrency::toEur($priceBgn),
            'prices' => BonusPriceResource::collection($this->whenLoaded('prices'))->resolve(),
            'prices_count' => $this->relationLoaded('prices') ? $this->prices->count() : null,
        ];
    }
}
