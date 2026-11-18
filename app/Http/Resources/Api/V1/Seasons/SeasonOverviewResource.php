<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Seasons;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeasonOverviewResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        $selectedSeason = data_get($this->resource, 'selected_season');

        return [
            'type' => (string) data_get($this->resource, 'type', 'service'),
            'services' => array_values((array) data_get($this->resource, 'services', [])),
            'bonus_titles' => array_values(array_map('strval', (array) data_get($this->resource, 'bonus_titles', []))),
            'selected_service_id' => data_get($this->resource, 'selected_service_id'),
            'selected_bonus_title' => data_get($this->resource, 'selected_bonus_title'),
            'season_defaults' => (new SeasonDefaultsResource((array) data_get($this->resource, 'season_defaults', [])))->resolve(),
            'seasons' => SeasonSummaryResource::collection((array) data_get($this->resource, 'seasons', []))->resolve(),
            'selected_season' => is_array($selectedSeason)
                ? (new SeasonSummaryResource($selectedSeason))->resolve()
                : null,
            'season_bookings' => SeasonBookingResource::collection((array) data_get($this->resource, 'season_bookings', []))->resolve(),
        ];
    }
}
