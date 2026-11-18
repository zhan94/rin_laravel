<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Logs;

use App\Http\Resources\Concerns\FormatsResourceValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Log */
class LogResource extends JsonResource
{
    use FormatsResourceValues;

    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'user_id' => (int) $this->user,
            'page' => (string) $this->page,
            'ip' => (string) $this->ip,
            'location' => $this->location !== null ? (string) $this->location : null,
            'date' => $this->formatDateTime($this->date),
            'user' => $this->whenLoaded('userModel', function (): ?array {
                if ($this->userModel === null) {
                    return null;
                }

                return [
                    'id' => (int) $this->userModel->id,
                    'username' => (string) $this->userModel->username,
                    'firstname' => $this->userModel->firstname !== null ? (string) $this->userModel->firstname : null,
                    'surname' => $this->userModel->surname !== null ? (string) $this->userModel->surname : null,
                    'lastname' => $this->userModel->lastname !== null ? (string) $this->userModel->lastname : null,
                ];
            }),
        ];
    }
}
