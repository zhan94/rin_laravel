<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportOverviewResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'is_driver' => (bool) data_get($this->resource, 'is_driver', false),
            'filters' => [
                'service_id' => data_get($this->resource, 'filters.service_id'),
                'from' => data_get($this->resource, 'filters.from'),
                'to' => data_get($this->resource, 'filters.to'),
                'q' => (string) data_get($this->resource, 'filters.q', ''),
                'sort' => (string) data_get($this->resource, 'filters.sort', 'id'),
                'dir' => (string) data_get($this->resource, 'filters.dir', 'desc'),
            ],
            'summary' => [
                'sum_total' => (int) data_get($this->resource, 'summary.sum_total', 0),
                'paid_total' => (int) data_get($this->resource, 'summary.paid_total', 0),
                'due_total' => (int) data_get($this->resource, 'summary.due_total', 0),
                'due_count' => (int) data_get($this->resource, 'summary.due_count', 0),
                'total_count' => (int) data_get($this->resource, 'summary.total_count', 0),
            ],
            'rows' => ReportRowResource::collection((array) data_get($this->resource, 'rows', []))->resolve(),
        ];
    }
}
