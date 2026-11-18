<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Issues;

use App\DTOs\Issues\IssueCreateData;
use App\DTOs\Issues\IssueFilterData;
use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Enums\RoleKey;
use App\Models\IssueReport;
use App\Models\IssueReportAttachment;
use App\Models\IssueReportComment;
use App\Models\User;
use App\Repositories\Contracts\Issues\IssueRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class IssueRepository implements IssueRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, IssueReport>
     */
    public function paginate(IssueFilterData $filters, ?int $scopeUserId): LengthAwarePaginator
    {
        return IssueReport::query()
            ->with([
                'reporter:id,username,firstname,surname,lastname',
                'resolver:id,username,firstname,surname,lastname',
                'assignee:id,username,firstname,surname,lastname',
            ])
            ->when($filters->scope === 'assigned' && ($scopeUserId ?? 0) > 0, fn ($query) => $query->where('assigned_to', $scopeUserId))
            ->when($filters->scope === 'created' && ($scopeUserId ?? 0) > 0, fn ($query) => $query->where('created_by', $scopeUserId))
            ->when($filters->status !== 'all', fn ($query) => $query->where('status', $filters->status))
            ->when($filters->module !== 'all', fn ($query) => $query->where('module_key', $filters->module))
            ->orderByRaw('COALESCE(updated_at, created_at) DESC')
            ->orderByDesc('id')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    public function summary(?int $scopeUserId, string $scope): array
    {
        $row = DB::table('issue_reports')
            ->when($scope === 'assigned' && ($scopeUserId ?? 0) > 0, fn ($query) => $query->where('assigned_to', $scopeUserId))
            ->when($scope === 'created' && ($scopeUserId ?? 0) > 0, fn ($query) => $query->where('created_by', $scopeUserId))
            ->selectRaw('COUNT(*) AS total_count')
            ->selectRaw("SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) AS open_count")
            ->selectRaw("SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS in_progress_count")
            ->selectRaw("SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved_count")
            ->selectRaw("SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) AS urgent_count")
            ->first();

        return [
            'total_count' => (int) data_get($row, 'total_count', 0),
            'open_count' => (int) data_get($row, 'open_count', 0),
            'in_progress_count' => (int) data_get($row, 'in_progress_count', 0),
            'resolved_count' => (int) data_get($row, 'resolved_count', 0),
            'urgent_count' => (int) data_get($row, 'urgent_count', 0),
        ];
    }

    public function create(int $reporterId, IssueCreateData $data): IssueReport
    {
        $priority = IssuePriority::tryFrom($data->priority) ?? IssuePriority::NORMAL;

        $title = $data->title !== ''
            ? $data->title
            : trim($data->moduleKey.' - '.$data->issueKey);

        return IssueReport::query()->create([
            'created_by' => $reporterId,
            'module_key' => $data->moduleKey,
            'issue_key' => $data->issueKey,
            'priority' => $priority->value,
            'status' => IssueStatus::OPEN->value,
            'title' => $title,
            'description' => $data->description,
            'page_url' => $data->pageUrl,
            'assigned_to' => $data->assignedTo,
            'assigned_at' => $data->assignedTo !== null ? now() : null,
        ])->loadMissing([
            'reporter:id,username,firstname,surname,lastname',
            'resolver:id,username,firstname,surname,lastname',
            'assignee:id,username,firstname,surname,lastname',
            'comments.user:id,username,firstname,surname,lastname',
            'attachments',
        ]);
    }

    public function findWithRelations(int $issueId): ?IssueReport
    {
        return IssueReport::query()
            ->with([
                'reporter:id,username,firstname,surname,lastname',
                'resolver:id,username,firstname,surname,lastname',
                'assignee:id,username,firstname,surname,lastname',
                'comments.user:id,username,firstname,surname,lastname',
                'attachments',
            ])
            ->find($issueId);
    }

    public function touch(IssueReport $issue): void
    {
        $issue->forceFill([
            'updated_at' => now(),
        ]);
        $issue->save();
    }

    public function updateStatus(IssueReport $issue, IssueStatus $status, ?int $resolvedBy): IssueReport
    {
        $issue->forceFill([
            'status' => $status->value,
            'resolved_by' => $status === IssueStatus::RESOLVED ? $resolvedBy : null,
            'resolved_at' => $status === IssueStatus::RESOLVED ? now() : null,
        ]);
        $issue->save();

        return $this->reload($issue);
    }

    public function updateAssignee(IssueReport $issue, ?int $assigneeId): IssueReport
    {
        $issue->forceFill([
            'assigned_to' => $assigneeId,
            'assigned_at' => $assigneeId !== null ? now() : null,
        ]);
        $issue->save();

        return $this->reload($issue);
    }

    public function addComment(IssueReport $issue, int $userId, string $comment): IssueReportComment
    {
        $created = IssueReportComment::query()->create([
            'issue_id' => (int) $issue->id,
            'user_id' => $userId,
            'comment_text' => $comment,
        ]);

        $this->touch($issue);

        return $created->loadMissing('user:id,username,firstname,surname,lastname');
    }

    public function addAttachment(
        IssueReport $issue,
        string $storedName,
        string $originalName,
        string $mimeType,
        int $fileSize,
    ): IssueReportAttachment {
        $created = IssueReportAttachment::query()->create([
            'issue_id' => (int) $issue->id,
            'stored_name' => $storedName,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'file_size' => max(0, $fileSize),
        ]);

        return $created->refresh();
    }

    public function findAttachmentById(int $attachmentId): ?IssueReportAttachment
    {
        return IssueReportAttachment::query()
            ->with('issue')
            ->find($attachmentId);
    }

    public function involvedUserIds(int $issueId): array
    {
        $reporterAndAssignee = IssueReport::query()
            ->whereKey($issueId)
            ->get(['created_by', 'assigned_to'])
            ->flatMap(static function (IssueReport $issue): array {
                return [
                    (int) $issue->created_by,
                    (int) ($issue->assigned_to ?? 0),
                ];
            })
            ->filter(static fn (int $id): bool => $id > 0)
            ->values()
            ->all();

        $commentUsers = IssueReportComment::query()
            ->where('issue_id', $issueId)
            ->pluck('user_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values()
            ->all();

        return array_values(array_unique([
            ...$reporterAndAssignee,
            ...$commentUsers,
        ]));
    }

    public function assignableAdmins(): array
    {
        $admins = User::query()
            ->select(['users.id', 'users.username', 'users.firstname', 'users.surname', 'users.lastname'])
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->whereIn('roles.role_key', [
                RoleKey::SUPER_ADMIN->value,
                RoleKey::ADMIN->value,
                RoleKey::MANAGER->value,
            ])
            ->orderBy('users.id')
            ->get()
            ->map(static function (User $user): array {
                return [
                    'id' => (int) $user->id,
                    'username' => (string) $user->username,
                    'firstname' => $user->firstname !== null ? (string) $user->firstname : null,
                    'surname' => $user->surname !== null ? (string) $user->surname : null,
                    'lastname' => $user->lastname !== null ? (string) $user->lastname : null,
                ];
            })
            ->values()
            ->all();

        /** @var list<array{id:int,username:string,firstname:?string,surname:?string,lastname:?string}> $admins */
        return $admins;
    }

    public function isAssignableAdmin(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        return User::query()
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('users.id', $userId)
            ->whereIn('roles.role_key', [
                RoleKey::SUPER_ADMIN->value,
                RoleKey::ADMIN->value,
                RoleKey::MANAGER->value,
            ])
            ->exists();
    }

    private function reload(IssueReport $issue): IssueReport
    {
        return $issue->refresh()->loadMissing([
            'reporter:id,username,firstname,surname,lastname',
            'resolver:id,username,firstname,surname,lastname',
            'assignee:id,username,firstname,surname,lastname',
            'comments.user:id,username,firstname,surname,lastname',
            'attachments',
        ]);
    }
}
