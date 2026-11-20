<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Bookings;

use App\Support\Pricing\EurCurrency;
use Illuminate\Validation\Rule;

final class ApplyBookingPaymentRequest extends ValidateBookingPaymentRequest
{
    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0', 'max:1000000000'],
            'request_key' => ['sometimes', 'required', 'uuid'],
            'expected_paid_eur' => ['required_if:mode,set', 'numeric', 'min:0', 'max:'.EurCurrency::MAX_BOOKING_AMOUNT],
            'mode' => ['sometimes', 'string', Rule::in(['increment', 'set'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'mode' => is_string($this->input('mode', 'increment')) ? strtolower($this->input('mode', 'increment')) : $this->input('mode'),
        ]);
    }
}
