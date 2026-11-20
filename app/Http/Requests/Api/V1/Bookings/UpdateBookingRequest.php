<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Bookings;

use App\Support\Pricing\EurCurrency;

final class UpdateBookingRequest extends StoreBookingRequest
{
    /** @return array<string,mixed> */
    public function rules(): array
    {
        return ['expected_paid_eur' => ['present_with:paid', 'required_with:paid', 'numeric', 'min:0', 'max:'.EurCurrency::MAX_BOOKING_AMOUNT]] + parent::rules();
    }
}
