<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Notifications;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexNotificationRequest extends FormRequest
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
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 25, 50, 100])],
            'status' => ['nullable', 'string', Rule::in(['unread', 'read', 'archived'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $status = trim(strtolower((string) $this->input('status', '')));

        $this->merge([
            'page' => (int) $this->input('page', 1),
            'per_page' => (int) $this->input('per_page', 25),
            'status' => $status !== '' ? $status : null,
        ]);
    }
}
