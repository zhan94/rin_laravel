<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Services;

use App\Http\Resources\Concerns\FormatsResourceValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ServiceSlot */
class ServiceSlotResource extends JsonResource
{
    use FormatsResourceValues;

    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'service_id' => (int) $this->service_id,
            'name' => (string) $this->name,
            'date_start' => $this->formatDate($this->date_start),
            'date_end' => $this->formatDate($this->date_end),
            'max' => (int) $this->max,
            'season_type' => $this->season_type !== null ? (string) $this->season_type : null,
            'dates' => ServiceDateResource::collection($this->whenLoaded('dates'))->resolve(),
            'created_at' => $this->formatAtom(data_get($this->resource, 'created_at')),
        ];
    }
}
