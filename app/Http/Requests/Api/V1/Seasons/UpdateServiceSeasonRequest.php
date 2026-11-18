<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Seasons;

use DateTimeImmutable;
use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceSeasonRequest extends FormRequest
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
            'season_name' => ['nullable', 'string', 'max:255'],
            'season_start' => ['required', 'date_format:Y-m-d'],
            'season_end' => ['required', 'date_format:Y-m-d'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'season_name' => trim((string) $this->input('season_name', $this->input('name', ''))),
            'season_start' => $this->normalizeDate((string) $this->input('season_start', $this->input('date_start', ''))),
            'season_end' => $this->normalizeDate((string) $this->input('season_end', $this->input('date_end', ''))),
        ]);
    }

    private function normalizeDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $formats = ['Y-m-d', 'd-m-Y', 'd.m.Y', 'd/m/Y'];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $value);
            if ($date instanceof DateTimeImmutable && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return '';
    }
}
