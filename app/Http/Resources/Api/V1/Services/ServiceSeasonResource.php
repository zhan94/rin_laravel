<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Services;

use App\Http\Resources\Concerns\FormatsResourceValues;
use App\Models\ServicePrice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ServiceSlot */
final class ServiceSeasonResource extends JsonResource
{
    use FormatsResourceValues;

    public const array STATUSES = ['active', 'upcoming', 'expired', 'unscheduled'];

    /** @return array<string,mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_id' => $this->service_id,
            'service_name' => $this->service?->displayTitleOrId($request->getLocale()),
            'service_active' => (bool) $this->service?->active,
            'season_name' => $this->displayLabel(),
            'date_start' => $this->formatDate($this->date_start),
            'date_end' => $this->formatDate($this->date_end),
            'capacity' => $this->max,
            'status' => self::STATUSES[(int) data_get($this->resource, 'status_rank')] ?? 'unscheduled',
            'dates_count' => (int) $this->dates_count,
            'prices' => $this->service?->prices->map(static fn (ServicePrice $price): array => [
                'id' => $price->id,
                'days' => $price->days,
                'price' => (string) $price->price,
            ])->all() ?? [],
        ];
    }
}
