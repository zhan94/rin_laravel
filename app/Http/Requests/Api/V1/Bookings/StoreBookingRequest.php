<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Bookings;

use App\Models\Booking;
use App\Models\Service;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
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
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'firstname' => ['nullable', 'string', 'max:120'],
            'surname' => ['nullable', 'string', 'max:120'],
            'lastname' => ['nullable', 'string', 'max:120'],
            'date_birth' => ['nullable', 'date_format:Y-m-d'],
            'parent_name' => ['nullable', 'string', 'max:191'],
            'parent_phone' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],

            'service_type' => ['required', 'integer', Rule::in([Booking::SERVICE_TYPE_SERVICE, Booking::SERVICE_TYPE_BONUS])],
            'service_id' => ['required', 'integer', 'min:1'],
            'slot_id' => ['nullable', 'integer', 'min:1'],
            'location_id' => ['exclude_unless:service_type,'.Booking::SERVICE_TYPE_SERVICE, 'required', 'integer', 'min:1'],
            'dates' => ['exclude_unless:service_type,'.Booking::SERVICE_TYPE_SERVICE, 'required', 'array', 'min:1'],
            'dates.*' => ['exclude_unless:service_type,'.Booking::SERVICE_TYPE_SERVICE, 'required', 'date_format:Y-m-d'],

            'request_key' => ['sometimes', 'required', 'uuid'],
            'sum_paid' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'paid' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'approved' => ['sometimes', 'boolean'],
            'lunch' => ['sometimes', 'boolean'],
            'note' => ['nullable', 'string'],

            'skill_id' => ['nullable', 'integer', 'min:1'],
            'equip_id' => ['nullable', 'integer', 'min:1'],
            'card_id' => ['nullable', 'integer', 'min:1'],
            'ski_shoe_size' => ['nullable', 'integer', 'min:1'],
            'ski_height' => ['nullable', 'integer', 'min:1'],
            'equip_enabled' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $rawServiceType = $this->input('service_type');
        $serviceType = $rawServiceType !== null
            ? (int) $rawServiceType
            : ($this->filled('bonus')
                ? Booking::SERVICE_TYPE_BONUS
                : Booking::SERVICE_TYPE_SERVICE);

        $dates = $this->input('dates');
        if (is_string($dates)) {
            $dates = array_values(array_filter(array_map('trim', explode(',', $dates))));
        }

        $serviceIdInput = $this->input(
            'service_id',
            $serviceType === Booking::SERVICE_TYPE_BONUS
                ? $this->input('bonus', 0)
                : $this->input('service', 0),
        );

        $slotInput = $this->input('slot_id', $this->input('slot'));
        $locationInput = $this->input('location_id', $this->input('location'));
        $skillInput = $this->input('skill_id', $this->input('skill'));
        $equipInput = $this->input('equip_id', $this->input('equip'));
        $cardInput = $this->input('card_id', $this->input('card'));

        $this->merge([
            'service_type' => $serviceType,
            'service_id' => max(0, (int) $serviceIdInput),
            'slot_id' => $slotInput !== null && $slotInput !== '' ? (int) $slotInput : null,
            'location_id' => $locationInput !== null && $locationInput !== '' ? (int) $locationInput : null,
            'dates' => is_array($dates) ? $dates : [],
            'skill_id' => $skillInput !== null && $skillInput !== '' ? (int) $skillInput : null,
            'equip_id' => $equipInput !== null && $equipInput !== '' ? (int) $equipInput : null,
            'card_id' => $cardInput !== null && $cardInput !== '' ? (int) $cardInput : null,
            'approved' => $this->boolean('approved'),
            'lunch' => $this->boolean('lunch'),
            'equip_enabled' => $this->boolean('equip_enabled')
                || ($equipInput !== null && $equipInput !== ''),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $customerId = (int) $this->input('customer_id', 0);
            $firstname = trim((string) $this->input('firstname', ''));
            $lastname = trim((string) $this->input('lastname', ''));

            if ($customerId <= 0 && ($firstname === '' || $lastname === '')) {
                if ($firstname === '') {
                    $validator->errors()->add('firstname', __('booking.first_name_required'));
                }
                if ($lastname === '') {
                    $validator->errors()->add('lastname', __('booking.last_name_required'));
                }
            }

            $serviceType = (int) $this->input('service_type', Booking::SERVICE_TYPE_SERVICE);
            if ($serviceType === Booking::SERVICE_TYPE_SERVICE) {
                $serviceId = (int) $this->input('service_id', 0);
                if ($serviceId <= 0 || ! Service::query()->whereKey($serviceId)->exists()) {
                    $validator->errors()->add('service_id', __('booking.service_not_found'));
                }
            }
        });
    }
}
