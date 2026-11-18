<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Catalog;

use App\Support\Pricing\BgnCurrency;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Illuminate\Database\Eloquent\Model */
class CatalogPriceResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        $priceBgn = (float) ($this->price ?? 0);
        $perDayBgn = (float) ($this->per_day ?? 0);

        return [
            'id' => (int) data_get($this->resource, 'id', 0),
            'days' => (int) ($this->days ?? 0),
            'price_bgn' => round($priceBgn, 2),
            'per_day_bgn' => round($perDayBgn, 2),
            'price_eur' => BgnCurrency::toEur($priceBgn),
            'per_day_eur' => BgnCurrency::toEur($perDayBgn),
        ];
    }
}
