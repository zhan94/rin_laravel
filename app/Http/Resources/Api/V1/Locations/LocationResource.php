<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Locations;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Location */
class LocationResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        $service = $this->relationLoaded('serviceModel') ? $this->serviceModel : null;
        $serviceResource = null;

        if ($service !== null) {
            $serviceTranslations = $service->displayTranslations();

            $serviceResource = [
                'id' => (int) $service->id,
                'title' => $service->displayTitle($request->getLocale()),
                'translations' => $serviceTranslations,
            ];
        }

        return [
            'id' => (int) $this->id,
            'location_from' => (string) ($this->location_from ?? ''),
            'location_to' => (string) ($this->location_to ?? ''),
            'from_time' => (string) ($this->from_time ?? ''),
            'to_time' => (string) ($this->to_time ?? ''),
            'service_id' => (int) ($this->service_id ?? 0),
            'service' => $serviceResource,
        ];
    }
}
