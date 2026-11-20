<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportRowResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'booking_id' => (int) data_get($this->resource, 'booking_id', 0),
            'customer_id' => (int) data_get($this->resource, 'customer_id', 0),
            'customer_name' => (string) data_get($this->resource, 'customer_name', '—'),
            'birth' => data_get($this->resource, 'birth'),
            'parent_name' => data_get($this->resource, 'parent_name'),
            'parent_phone' => data_get($this->resource, 'parent_phone'),
            'email' => data_get($this->resource, 'email'),
            'location' => (string) data_get($this->resource, 'location', 'Unknown'),
            'dates' => array_values(array_map('strval', (array) data_get($this->resource, 'dates', []))),
            'sum_paid' => (float) data_get($this->resource, 'sum_paid', 0),
            'paid' => (float) data_get($this->resource, 'paid', 0),
            'due' => (float) data_get($this->resource, 'due', 0),
            'note' => data_get($this->resource, 'note'),
            'skill' => data_get($this->resource, 'skill'),
            'equip' => data_get($this->resource, 'equip'),
        ];
    }
}
