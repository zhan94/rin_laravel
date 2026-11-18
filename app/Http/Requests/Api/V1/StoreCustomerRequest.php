<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'firstname' => ['required', 'string', 'max:120'],
            'surname' => ['nullable', 'string', 'max:120'],
            'lastname' => ['required', 'string', 'max:120'],
            'date_birth' => ['nullable', 'date'],
            'age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'parent_name' => ['nullable', 'string', 'max:191'],
            'parent_phone' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('active')) {
            $this->merge(['active' => true]);
        }
    }
}
