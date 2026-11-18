<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Bookings;

use App\Http\Resources\Concerns\FormatsResourceValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\BookingDate */
class BookingDateResource extends JsonResource
{
    use FormatsResourceValues;

    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        $serviceDate = $this->serviceDate;

        return [
            'id' => (int) $this->id,
            'service_date_id' => (int) $this->service_date_id,
            'slot_id' => $serviceDate === null ? 0 : (int) $serviceDate->slot_id,
            'slot_date' => $this->formatDate($serviceDate?->slot_date),
            'slot_name' => $serviceDate?->slot?->name,
        ];
    }
}
