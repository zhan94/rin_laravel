<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Services;

use App\Http\Resources\Concerns\FormatsResourceValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ServiceDate */
class ServiceDateResource extends JsonResource
{
    use FormatsResourceValues;

    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        $bookingsCount = (int) ($this->booking_dates_count ?? 0);

        return [
            'id' => (int) $this->id,
            'slot_id' => (int) $this->slot_id,
            'slot_date' => $this->formatDate($this->slot_date),
            'is_active' => (bool) $this->is_active,
            'bookings_count' => $bookingsCount,
            'has_bookings' => $bookingsCount > 0,
        ];
    }
}
