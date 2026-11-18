<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Seasons;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeasonBookingResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'booking_id' => (int) data_get($this->resource, 'booking_id', 0),
            'customer_id' => (int) data_get($this->resource, 'customer_id', 0),
            'name' => (string) data_get($this->resource, 'name', ''),
            'phone' => data_get($this->resource, 'phone'),
            'location' => data_get($this->resource, 'location'),
            'sum_paid' => (float) data_get($this->resource, 'sum_paid', 0),
            'paid' => (float) data_get($this->resource, 'paid', 0),
            'due' => (float) data_get($this->resource, 'due', 0),
            'dates' => array_values(array_map('strval', (array) data_get($this->resource, 'dates', []))),
        ];
    }
}
