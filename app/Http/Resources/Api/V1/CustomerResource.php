<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Concerns\FormatsResourceValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Customer */
class CustomerResource extends JsonResource
{
    use FormatsResourceValues;

    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'firstname' => (string) $this->firstname,
            'surname' => $this->surname,
            'lastname' => (string) $this->lastname,
            'full_name' => $this->fullName(),
            'date_birth' => $this->formatDate($this->date_birth),
            'age' => $this->age !== null ? (int) $this->age : null,
            'parent_name' => $this->parent_name,
            'parent_phone' => $this->parent_phone,
            'email' => $this->email,
            'active' => (bool) $this->active,
            'created_at' => $this->formatAtom(data_get($this->resource, 'created_at')),
            'updated_at' => $this->formatAtom(data_get($this->resource, 'updated_at')),
        ];
    }
}
