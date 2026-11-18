<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Issues;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexIssueRequest extends FormRequest
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
            'scope' => ['nullable', 'string', Rule::in(['all', 'assigned', 'created'])],
            'status' => ['nullable', 'string', Rule::in(['all', 'open', 'in_progress', 'resolved'])],
            'module' => ['nullable', 'string', 'max:64'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 25, 50, 100])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'scope' => strtolower(trim((string) $this->input('scope', 'all'))),
            'status' => strtolower(trim((string) $this->input('status', 'all'))),
            'module' => trim((string) $this->input('module', 'all')),
            'page' => (int) $this->input('page', 1),
            'per_page' => (int) $this->input('per_page', 25),
        ]);
    }
}
