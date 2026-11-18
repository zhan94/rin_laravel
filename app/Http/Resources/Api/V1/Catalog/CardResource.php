<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Catalog;

use App\Http\Resources\Api\V1\Catalog\Concerns\FormatsCatalogItemResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Card */
class CardResource extends JsonResource
{
    use FormatsCatalogItemResource;

    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->catalogItemArray($request);
    }
}
