<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Notifications;

use App\Http\Resources\Concerns\FormatsResourceValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\UserNotification */
class UserNotificationResource extends JsonResource
{
    use FormatsResourceValues;

    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'user_id' => (int) $this->user_id,
            'actor_user_id' => $this->actor_user_id !== null ? (int) $this->actor_user_id : null,
            'notification_type' => (string) $this->notification_type,
            'module_key' => (string) $this->module_key,
            'action_key' => (string) $this->action_key,
            'entity_type' => (string) $this->entity_type,
            'entity_id' => $this->entity_id !== null ? (int) $this->entity_id : null,
            'title' => (string) $this->title,
            'message' => (string) $this->message,
            'metadata' => $this->metadata,
            'link_path' => (string) $this->link_path,
            'status' => (string) $this->status,
            'priority' => (string) $this->priority,
            'delivery_channel' => (string) $this->delivery_channel,
            'is_sent' => (bool) $this->is_sent,
            'sent_at' => $this->formatAtom(data_get($this->resource, 'sent_at')),
            'created_at' => $this->formatAtom(data_get($this->resource, 'created_at')),
            'read_at' => $this->formatAtom(data_get($this->resource, 'read_at')),
            'actor' => $this->whenLoaded('actor', function (): ?array {
                if ($this->actor === null) {
                    return null;
                }

                return [
                    'id' => (int) $this->actor->id,
                    'username' => (string) $this->actor->username,
                    'firstname' => $this->actor->firstname !== null ? (string) $this->actor->firstname : null,
                    'surname' => $this->actor->surname !== null ? (string) $this->actor->surname : null,
                    'lastname' => $this->actor->lastname !== null ? (string) $this->actor->lastname : null,
                ];
            }),
        ];
    }
}
