<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Bookings;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\BookingWinterDetail */
class BookingWinterDetailResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'booking_id' => (int) $this->booking_id,
            'skill_id' => $this->skill_id !== null ? (int) $this->skill_id : null,
            'equip_id' => $this->equip_id !== null ? (int) $this->equip_id : null,
            'equip_enabled' => $this->equip_enabled !== null ? (bool) $this->equip_enabled : null,
            'card_id' => $this->card_id !== null ? (int) $this->card_id : null,
            'shoe_size' => $this->shoe_size !== null ? (int) $this->shoe_size : null,
            'height' => $this->height !== null ? (int) $this->height : null,
            'created_at' => $this->created_at?->toAtomString(),
            'updated_at' => $this->updated_at?->toAtomString(),
        ];
    }
}
