<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Bookings;

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
            'expected_paid_bgn' => ['required_if:mode,set', 'integer', 'min:0'],
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
