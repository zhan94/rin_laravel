<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Issues;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIssueRequest extends FormRequest
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
            'module_key' => ['required', 'string', 'max:64'],
            'issue_key' => ['required', 'string', 'max:64'],
            'priority' => ['required', 'string', Rule::in(['normal', 'medium', 'urgent'])],
            'title' => ['nullable', 'string', 'max:190'],
            'description' => ['required', 'string', 'max:4000'],
            'page_url' => ['nullable', 'string', 'max:255'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'screenshots' => ['nullable', 'array', 'max:3'],
            'screenshots.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $assignedTo = $this->input('assigned_to');

        $this->merge([
            'module_key' => trim((string) $this->input('module_key', '')),
            'issue_key' => trim((string) $this->input('issue_key', '')),
            'priority' => strtolower(trim((string) $this->input('priority', 'normal'))),
            'title' => trim((string) $this->input('title', '')),
            'description' => trim((string) $this->input('description', '')),
            'page_url' => trim((string) $this->input('page_url', '')),
            'assigned_to' => $assignedTo !== null && $assignedTo !== '' ? (int) $assignedTo : null,
        ]);
    }
}
