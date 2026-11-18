<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Bookings;

final class UpdateBookingRequest extends StoreBookingRequest
{
    /** @return array<string,mixed> */
    public function rules(): array
    {
        return ['expected_paid_bgn' => ['present_with:paid', 'required_with:paid', 'integer', 'min:0']] + parent::rules();
    }
}
