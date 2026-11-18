<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Seasons;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeasonDefaultsResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'start_month' => (int) data_get($this->resource, 'start_month', 0),
            'start_day' => (int) data_get($this->resource, 'start_day', 0),
            'end_month' => (int) data_get($this->resource, 'end_month', 0),
            'end_day' => (int) data_get($this->resource, 'end_day', 0),
            'updated_at' => data_get($this->resource, 'updated_at'),
        ];
    }
}
