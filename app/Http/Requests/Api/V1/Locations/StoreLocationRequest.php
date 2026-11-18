<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Locations;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocationRequest extends FormRequest
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
            'location_from' => ['required', 'string', 'max:222'],
            'location_to' => ['required', 'string', 'max:255'],
            'from_time' => ['required', 'regex:/^(?:[01]?\d|2[0-3]):[0-5]\d$/'],
            'to_time' => ['required', 'regex:/^(?:[01]?\d|2[0-3]):[0-5]\d$/'],
            'service_id' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'service_id' => max(0, (int) $this->input('service_id', $this->input('service', 0))),
            'from_time' => $this->normalizeTime((string) $this->input('from_time', '')),
            'to_time' => $this->normalizeTime((string) $this->input('to_time', '')),
        ]);
    }

    private function normalizeTime(string $value): string
    {
        $raw = trim($value);
        if ($raw === '') {
            return '';
        }

        $chunks = explode(':', $raw);
        if (count($chunks) !== 2) {
            return $raw;
        }

        $hour = (int) $chunks[0];
        $minute = (int) $chunks[1];
        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return $raw;
        }

        return sprintf('%02d:%02d', $hour, $minute);
    }
}
