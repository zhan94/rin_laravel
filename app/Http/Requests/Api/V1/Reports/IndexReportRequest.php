<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Reports;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexReportRequest extends FormRequest
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
            'service_id' => ['nullable', 'integer', 'min:1', 'exists:services,id'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'q' => ['nullable', 'string', 'max:190'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in([10, 25, 50, 100])],
            'sort' => ['nullable', 'string', Rule::in(['id', 'name', 'birth', 'level', 'equip', 'location', 'due', 'parent'])],
            'dir' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $serviceId = $this->input('service_id');
        $page = $this->input('page');
        $perPage = $this->input('per_page');

        $this->merge([
            'service_id' => $serviceId !== null && $serviceId !== '' ? (int) $serviceId : null,
            'page' => $page !== null && $page !== '' ? (int) $page : 1,
            'per_page' => $perPage !== null && $perPage !== '' ? (int) $perPage : 25,
            'sort' => strtolower(trim((string) $this->input('sort', 'id'))),
            'dir' => strtolower(trim((string) $this->input('dir', 'desc'))),
            'q' => trim((string) $this->input('q', '')),
            'from' => trim((string) $this->input('from', '')),
            'to' => trim((string) $this->input('to', '')),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $from = trim((string) $this->input('from', ''));
            $to = trim((string) $this->input('to', ''));

            if (($from === '') !== ($to === '')) {
                $validator->errors()->add('from', 'Both from and to dates are required.');
                $validator->errors()->add('to', 'Both from and to dates are required.');

                return;
            }

            if ($from !== '' && $to !== '' && $from > $to) {
                $validator->errors()->add('to', 'The to date must be after or equal to from date.');
            }
        });
    }
}
