<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Issues;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIssueAssigneeRequest extends FormRequest
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
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $assignedTo = $this->input('assigned_to');

        $this->merge([
            'assigned_to' => $assignedTo !== null && $assignedTo !== '' ? (int) $assignedTo : null,
        ]);
    }
}
