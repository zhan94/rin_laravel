<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Catalog;

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
        return [
            'id' => (int) data_get($this->resource, 'id', 0),
            'days' => (int) ($this->days ?? 0),
            'price_eur' => (float) ($this->price ?? 0),
            'per_day_eur' => (float) ($this->per_day ?? 0),
        ];
    }
}
