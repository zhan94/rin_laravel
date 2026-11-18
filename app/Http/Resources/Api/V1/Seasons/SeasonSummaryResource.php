<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Seasons;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeasonSummaryResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) data_get($this->resource, 'id', 0),
            'label' => (string) data_get($this->resource, 'label', ''),
            'title' => data_get($this->resource, 'title'),
            'date_start' => data_get($this->resource, 'date_start'),
            'date_end' => data_get($this->resource, 'date_end'),
            'date_start_label' => data_get($this->resource, 'date_start_label'),
            'date_end_label' => data_get($this->resource, 'date_end_label'),
            'date_range' => (string) data_get($this->resource, 'date_range', ''),
            'dates_count' => data_get($this->resource, 'dates_count'),
            'bookings_count' => (int) data_get($this->resource, 'bookings_count', 0),
            'customers_count' => (int) data_get($this->resource, 'customers_count', 0),
            'sum_total' => (float) data_get($this->resource, 'sum_total', 0),
            'paid_total' => (float) data_get($this->resource, 'paid_total', 0),
            'due_total' => (float) data_get($this->resource, 'due_total', 0),
            'location' => data_get($this->resource, 'location'),
            'is_selected' => (bool) data_get($this->resource, 'is_selected', false),
        ];
    }
}
