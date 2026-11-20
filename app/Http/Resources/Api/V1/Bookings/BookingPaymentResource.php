<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Bookings;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\BookingPayment */
class BookingPaymentResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'booking_id' => (int) $this->booking_id,
            'sum_paid' => $this->sum_paid !== null ? (float) $this->sum_paid : null,
            'paid' => $this->paid !== null ? (float) $this->paid : null,
            'created_at' => $this->created_at?->toAtomString(),
            'updated_at' => $this->updated_at?->toAtomString(),
        ];
    }
}
