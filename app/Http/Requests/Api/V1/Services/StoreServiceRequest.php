<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Services;

use App\Support\Localization\LocalizedText;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreServiceRequest extends FormRequest
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
            'title' => ['nullable', 'string', 'max:255'],
            'translations' => ['nullable', 'array'],
            'translations.*' => ['nullable', 'string', 'max:255'],
            'title_key' => ['nullable', 'integer', 'min:1'],
            'max' => ['required', 'integer', 'min:0'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $title = trim((string) $this->input('title', ''));
        $translations = LocalizedText::normalize(
            $this->input('translations'),
            $title,
        );

        $this->merge([
            'title' => LocalizedText::resolve($translations, 'en', $title),
            'translations' => $translations,
            'active' => $this->has('active') ? $this->boolean('active') : true,
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var array<string,string> $translations */
            $translations = $this->input('translations', []);

            if ($translations === []) {
                $validator->errors()->add('translations', 'At least one localized title is required.');
            }
        });
    }
}
