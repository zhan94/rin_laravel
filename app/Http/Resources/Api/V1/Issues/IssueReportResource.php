<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Issues;

use App\Http\Resources\Concerns\FormatsResourceValues;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\IssueReport */
class IssueReportResource extends JsonResource
{
    use FormatsResourceValues;

    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'created_by' => (int) $this->created_by,
            'module_key' => (string) $this->module_key,
            'issue_key' => (string) $this->issue_key,
            'priority' => $this->enumValue($this->priority),
            'status' => $this->enumValue($this->status),
            'title' => (string) $this->title,
            'description' => $this->description !== null ? (string) $this->description : null,
            'page_url' => $this->page_url !== null ? (string) $this->page_url : null,
            'resolved_by' => $this->resolved_by !== null ? (int) $this->resolved_by : null,
            'assigned_to' => $this->assigned_to !== null ? (int) $this->assigned_to : null,
            'assigned_at' => $this->formatAtom(data_get($this->resource, 'assigned_at')),
            'resolved_at' => $this->formatAtom(data_get($this->resource, 'resolved_at')),
            'created_at' => $this->formatAtom(data_get($this->resource, 'created_at')),
            'updated_at' => $this->formatAtom(data_get($this->resource, 'updated_at')),
            'reporter' => ($this->relationLoaded('reporter') && $this->reporter !== null)
                ? $this->mapUser($this->reporter)
                : null,
            'resolver' => ($this->relationLoaded('resolver') && $this->resolver !== null)
                ? $this->mapUser($this->resolver)
                : null,
            'assignee' => ($this->relationLoaded('assignee') && $this->assignee !== null)
                ? $this->mapUser($this->assignee)
                : null,
            'comments' => $this->relationLoaded('comments')
                ? IssueCommentResource::collection($this->comments)->resolve()
                : [],
            'attachments' => $this->relationLoaded('attachments')
                ? IssueAttachmentResource::collection($this->attachments)->resolve()
                : [],
        ];
    }

    /**
     * @return array{id:int,username:string,firstname:string|null,surname:string|null,lastname:string|null}
     */
    private function mapUser(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'username' => (string) $user->username,
            'firstname' => $user->firstname !== null ? (string) $user->firstname : null,
            'surname' => $user->surname !== null ? (string) $user->surname : null,
            'lastname' => $user->lastname !== null ? (string) $user->lastname : null,
        ];
    }
}
