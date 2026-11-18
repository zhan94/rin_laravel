<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Seasons;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSeasonDefaultsRequest extends FormRequest
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
            'default_start_month' => ['required', 'integer', 'between:1,12'],
            'default_start_day' => ['required', 'integer', 'between:1,31'],
            'default_end_month' => ['required', 'integer', 'between:1,12'],
            'default_end_day' => ['required', 'integer', 'between:1,31'],
            'default_start_md' => ['sometimes', 'nullable', 'string', 'regex:/^\s*\d{1,2}\s*-\s*\d{1,2}\s*$/'],
            'default_end_md' => ['sometimes', 'nullable', 'string', 'regex:/^\s*\d{1,2}\s*-\s*\d{1,2}\s*$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $start = $this->parseDayMonth(
            (string) $this->input('default_start_md', ''),
            (int) $this->input('default_start_month', 0),
            (int) $this->input('default_start_day', 0),
        );

        $end = $this->parseDayMonth(
            (string) $this->input('default_end_md', ''),
            (int) $this->input('default_end_month', 0),
            (int) $this->input('default_end_day', 0),
        );

        $this->merge([
            'default_start_month' => $start['month'],
            'default_start_day' => $start['day'],
            'default_end_month' => $end['month'],
            'default_end_day' => $end['day'],
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! checkdate((int) $this->input('default_start_month'), (int) $this->input('default_start_day'), 2024)) {
                $validator->errors()->add('default_start_md', 'Invalid default season start date.');
            }

            if (! checkdate((int) $this->input('default_end_month'), (int) $this->input('default_end_day'), 2024)) {
                $validator->errors()->add('default_end_md', 'Invalid default season end date.');
            }
        });
    }

    /**
     * @return array{month:int,day:int}
     */
    private function parseDayMonth(string $value, int $fallbackMonth, int $fallbackDay): array
    {
        $value = trim($value);

        if ($value !== '' && preg_match('/^\s*(\d{1,2})\s*-\s*(\d{1,2})\s*$/', $value, $matches) === 1) {
            return [
                'month' => (int) $matches[2],
                'day' => (int) $matches[1],
            ];
        }

        return [
            'month' => $fallbackMonth,
            'day' => $fallbackDay,
        ];
    }
}
