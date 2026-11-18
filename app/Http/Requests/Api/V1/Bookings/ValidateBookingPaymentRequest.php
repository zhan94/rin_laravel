<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Bookings;

use Illuminate\Foundation\Http\FormRequest;

class ValidateBookingPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => $this->input('amount') !== null
                ? (float) $this->input('amount')
                : null,
        ]);
    }
}
