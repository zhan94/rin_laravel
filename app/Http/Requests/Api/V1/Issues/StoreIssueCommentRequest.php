<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Issues;

use Illuminate\Foundation\Http\FormRequest;

class StoreIssueCommentRequest extends FormRequest
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
            'comment' => ['required', 'string', 'max:4000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'comment' => trim((string) $this->input('comment', '')),
        ]);
    }
}
