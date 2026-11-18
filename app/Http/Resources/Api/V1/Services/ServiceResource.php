<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Services;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Service */
class ServiceResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        $translations = $this->displayTranslations();
        $slotsCount = data_get($this->resource, 'slots_count');

        return [
            'id' => (int) $this->id,
            'title' => $this->displayTitle($request->getLocale()),
            'translations' => $translations,
            'title_key' => (int) $this->title_key,
            'max' => (int) $this->max,
            'active' => (bool) $this->active,
            'slots_count' => is_numeric($slotsCount) ? (int) $slotsCount : null,
        ];
    }
}
