<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Catalog;

use App\Support\Localization\LocalizedText;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreCatalogItemRequest extends FormRequest
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
            'title' => ['nullable', 'string', 'max:100'],
            'translations' => ['nullable', 'array'],
            'translations.*' => ['nullable', 'string', 'max:255'],
            'service' => ['required', 'integer', 'exists:services,id'],
            'prices' => ['sometimes', 'array'],
            'prices.*.days' => ['required', 'integer', 'min:1'],
            'prices.*.price_eur' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $prices = $this->input('prices');

        if (! is_array($prices)) {
            $days = $this->input('days', []);
            $values = $this->input('price', []);

            $prices = [];
            if (is_array($days) && is_array($values)) {
                $count = max(count($days), count($values));
                for ($i = 0; $i < $count; $i++) {
                    $day = trim((string) ($days[$i] ?? ''));
                    $price = trim((string) ($values[$i] ?? ''));

                    if ($day === '' && $price === '') {
                        continue;
                    }

                    $prices[] = [
                        'days' => $day,
                        'price_eur' => $price,
                    ];
                }
            }
        }

        $this->merge([
            'service' => (int) $this->input('service', $this->input('service_id', 0)),
            'translations' => LocalizedText::normalize(
                $this->input('translations'),
                $this->input('title'),
            ),
            'prices' => $prices,
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
