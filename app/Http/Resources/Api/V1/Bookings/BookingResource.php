<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Bookings;

use App\Http\Resources\Api\V1\CustomerResource;
use App\Http\Resources\Api\V1\Services\ServiceResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Booking */
class BookingResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        $sumPaid = $this->sum_paid !== null ? (int) $this->sum_paid : null;
        $paid = $this->paid !== null ? (int) $this->paid : null;

        $payment = null;
        if ($this->relationLoaded('payment') && $this->payment !== null) {
            $payment = (new BookingPaymentResource($this->payment))->resolve();
        }

        $winterDetail = null;
        if ($this->relationLoaded('winterDetail') && $this->winterDetail !== null) {
            $winterDetail = (new BookingWinterDetailResource($this->winterDetail))->resolve();
        }

        return [
            'id' => (int) $this->id,
            'customer_id' => $this->customer_id !== null ? (int) $this->customer_id : null,
            'service_id' => $this->service !== null ? (int) $this->service : null,
            'service_type' => $this->service_type !== null ? (int) $this->service_type : null,
            'location_id' => $this->location !== null ? (int) $this->location : null,
            'sum_paid' => $sumPaid,
            'paid' => $paid,
            'due' => ($sumPaid !== null && $paid !== null) ? max(0, $sumPaid - $paid) : null,
            'approved' => (bool) $this->approved,
            'active' => (bool) $this->active,
            'lunch' => (bool) $this->lunch,
            'note' => $this->note !== null ? (string) $this->note : null,
            'customer' => ($this->relationLoaded('customer') && $this->customer !== null)
                ? (new CustomerResource($this->customer))->resolve()
                : null,
            'service' => ($this->relationLoaded('serviceModel') && $this->serviceModel !== null)
                ? (new ServiceResource($this->serviceModel))->resolve()
                : null,
            'location' => ($this->relationLoaded('locationRecord') && $this->locationRecord !== null)
                ? [
                    'id' => (int) $this->locationRecord->id,
                    'location_from' => (string) $this->locationRecord->location_from,
                    'location_to' => (string) $this->locationRecord->location_to,
                    'from_time' => (string) $this->locationRecord->from_time,
                    'to_time' => (string) $this->locationRecord->to_time,
                    'service_id' => (int) $this->locationRecord->service_id,
                ]
                : null,
            'booking_dates' => $this->relationLoaded('bookingDates')
                ? BookingDateResource::collection($this->bookingDates)->resolve()
                : [],
            'payment' => $payment,
            'winter_detail' => $winterDetail,
        ];
    }
}
