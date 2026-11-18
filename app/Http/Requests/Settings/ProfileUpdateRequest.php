<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use App\Support\Auth\AuthenticatedUserResolver;
use App\Support\Profiles\ProfileInputNormalizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    protected function prepareForValidation(): void
    {
        $user = AuthenticatedUserResolver::fromRequest($this);
        $normalizedInput = ProfileInputNormalizer::forProfileUpdate(
            $this->all(),
            (string) $user->username,
        );

        $this->merge($normalizedInput);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->profileRules((int) AuthenticatedUserResolver::fromRequest($this)->id);
    }
}
