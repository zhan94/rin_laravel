<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Issues;

use App\Http\Resources\Concerns\FormatsResourceValues;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\IssueReportComment */
class IssueCommentResource extends JsonResource
{
    use FormatsResourceValues;

    /**
     * @return array<string,mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'issue_id' => (int) $this->issue_id,
            'user_id' => (int) $this->user_id,
            'comment_text' => (string) $this->comment_text,
            'created_at' => $this->formatAtom(data_get($this->resource, 'created_at')),
            'user' => ($this->relationLoaded('user') && $this->user !== null)
                ? $this->mapUser($this->user)
                : null,
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
