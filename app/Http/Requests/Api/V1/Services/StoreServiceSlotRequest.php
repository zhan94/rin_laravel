<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Services;

use App\Enums\SeasonType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceSlotRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'max' => ['sometimes', 'integer', 'min:0'],
            'season_type' => ['sometimes', Rule::in(array_map(static fn (SeasonType $type): string => $type->value, SeasonType::cases()))],
            'dates' => ['required', 'array', 'min:1'],
            'dates.*' => ['required', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $dates = $this->input('dates');
        if (is_string($dates)) {
            $dates = array_values(array_filter(array_map('trim', explode(',', $dates))));
        }

        $this->merge([
            'dates' => is_array($dates) ? $dates : [],
            'season_type' => $this->input('season_type', SeasonType::OTHER->value),
            'max' => (int) $this->input('max', 0),
        ]);
    }
}
