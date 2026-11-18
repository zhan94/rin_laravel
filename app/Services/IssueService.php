<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Issues\IssueAssigneeUpdateData;
use App\DTOs\Issues\IssueCommentData;
use App\DTOs\Issues\IssueCreateData;
use App\DTOs\Issues\IssueFilterData;
use App\DTOs\Issues\IssueStatusUpdateData;
use App\Enums\IssueStatus;
use App\Enums\RoleKey;
use App\Exceptions\ApiException;
use App\Models\IssueReport;
use App\Models\IssueReportAttachment;
use App\Models\User;
use App\Models\UserNotification;
use App\Repositories\Contracts\Issues\IssueRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final readonly class IssueService
{
    /**
     * @var array<string,list<string>>
     */
    private const MODULE_ISSUES = [
        'booking' => ['page_not_loading', 'cannot_save', 'date_calendar_problem', 'payment_problem', 'location_mapping_problem', 'equipment_card_problem', 'calculation_problem', 'duplicate_records', 'slow_response', 'unexpected_error', 'other'],
        'services' => ['page_not_loading', 'cannot_save', 'date_calendar_problem', 'season_mapping_problem', 'wrong_data_displayed', 'missing_data', 'cannot_delete', 'duplicate_records', 'slow_response', 'unexpected_error', 'other'],
        'seasons' => ['page_not_loading', 'cannot_save', 'season_mapping_problem', 'date_calendar_problem', 'wrong_data_displayed', 'missing_data', 'cannot_delete', 'unexpected_error', 'other'],
        'bonus' => ['page_not_loading', 'cannot_save', 'date_calendar_problem', 'wrong_data_displayed', 'payment_problem', 'cannot_delete', 'slow_response', 'unexpected_error', 'other'],
        'reports' => ['page_not_loading', 'wrong_data_displayed', 'search_filter_problem', 'export_or_print_problem', 'calculation_problem', 'slow_response', 'unexpected_error', 'other'],
        'locations' => ['page_not_loading', 'cannot_save', 'location_mapping_problem', 'wrong_data_displayed', 'cannot_delete', 'duplicate_records', 'unexpected_error', 'other'],
        'cards' => ['page_not_loading', 'cannot_save', 'equipment_card_problem', 'wrong_data_displayed', 'cannot_delete', 'duplicate_records', 'calculation_problem', 'unexpected_error', 'other'],
        'equips' => ['page_not_loading', 'cannot_save', 'equipment_card_problem', 'wrong_data_displayed', 'cannot_delete', 'duplicate_records', 'unexpected_error', 'other'],
        'customers' => ['page_not_loading', 'cannot_save', 'wrong_data_displayed', 'search_filter_problem', 'duplicate_records', 'permission_problem', 'cannot_delete', 'unexpected_error', 'other'],
        'users' => ['page_not_loading', 'cannot_save', 'permission_problem', 'login_problem', 'password_reset_problem', 'passkey_problem', 'duplicate_records', 'cannot_delete', 'unexpected_error', 'other'],
        'logs' => ['page_not_loading', 'wrong_data_displayed', 'search_filter_problem', 'permission_problem', 'slow_response', 'unexpected_error', 'other'],
        'auth' => ['login_problem', 'password_reset_problem', 'passkey_problem', 'permission_problem', 'page_not_loading', 'unexpected_error', 'other'],
        'manual' => ['page_not_loading', 'missing_data', 'wrong_data_displayed', 'ui_layout_problem', 'mobile_view_problem', 'other'],
        'other_module' => ['page_not_loading', 'cannot_save', 'wrong_data_displayed', 'missing_data', 'search_filter_problem', 'slow_response', 'unexpected_error', 'other'],
    ];

    private const NOTIFY_ENTITY = 'issue';

    private const NOTIFY_MODULE_KEY = 'issues';

    public function __construct(
        private IssueRepositoryInterface $repository,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function overview(User $actor, IssueFilterData $filters): array
    {
        $this->assertModuleAccess($actor);

        $scopeUserId = (int) $actor->id;

        $paginator = $this->repository->paginate($filters, $scopeUserId);
        $summary = $this->repository->summary($scopeUserId, $filters->scope);

        return [
            'rows' => $paginator->items(),
            'summary' => $summary,
            'filters' => [
                'scope' => $filters->scope,
                'status' => $filters->status,
                'module' => $filters->module,
            ],
            'allowed_statuses' => ['open', 'in_progress', 'resolved'],
            'allowed_priorities' => ['normal', 'medium', 'urgent'],
            'module_issue_map' => self::MODULE_ISSUES,
            'assignable_admins' => $this->repository->assignableAdmins(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }

    public function create(User $actor, IssueCreateData $data): IssueReport
    {
        $this->assertModuleAccess($actor);

        $this->assertValidIssueKey($data->moduleKey, $data->issueKey);
        $this->assertAssignableOrNull($data->assignedTo);

        $reporterId = (int) $actor->id;

        $files = [];

        try {
            return DB::transaction(function () use ($reporterId, $data, &$files): IssueReport {
                $issue = $this->repository->create($reporterId, $data);

                foreach ($data->screenshots as $screenshot) {
                    $this->storeAttachment($issue, $screenshot, $files);
                }

                if ($data->assignedTo !== null) {
                    $this->createNotifications(
                        [$data->assignedTo],
                        $reporterId,
                        'assigned',
                        sprintf('Issue #%d assigned', (int) $issue->id),
                        sprintf('Issue #%d was assigned to you.', (int) $issue->id),
                        (int) $issue->id,
                    );
                }

                return $this->reloadIssue((int) $issue->id);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($files);

            throw $exception;
        }
    }

    public function findForView(User $actor, int $issueId): IssueReport
    {
        $this->assertModuleAccess($actor);

        $issue = $this->repository->findWithRelations($issueId);
        if ($issue === null) {
            throw new ApiException(
                errorCode: 'issue_not_found',
                message: 'Issue not found.',
                statusCode: 404,
            );
        }

        return $issue;
    }

    public function updateStatus(User $actor, int $issueId, IssueStatusUpdateData $data): IssueReport
    {
        $issue = $this->findForView($actor, $issueId);
        $this->assertCanEditIssue($actor, $issue);

        $newStatus = IssueStatus::tryFrom($data->status);
        if (! $newStatus instanceof IssueStatus) {
            throw new ApiException(
                errorCode: 'issue_status_invalid',
                message: 'Invalid issue status.',
                statusCode: 422,
            );
        }

        $actorId = (int) $actor->id;
        $previousStatus = (string) $issue->getRawOriginal('status');

        return DB::transaction(function () use ($issue, $newStatus, $actorId, $previousStatus, $data): IssueReport {
            $updated = $this->repository->updateStatus(
                $issue,
                $newStatus,
                $newStatus === IssueStatus::RESOLVED ? $actorId : null,
            );

            $statusChanged = $previousStatus !== $newStatus->value;

            if ($statusChanged || $data->comment !== null) {
                $parts = [];
                if ($statusChanged) {
                    $parts[] = sprintf('Status changed: %s -> %s', $previousStatus, $newStatus->value);
                }
                if ($data->comment !== null && $data->comment !== '') {
                    $parts[] = $data->comment;
                }

                $comment = trim(implode("\n", $parts));
                if ($comment !== '') {
                    $this->repository->addComment($updated, $actorId, $comment);
                }
            }

            if ($statusChanged) {
                $recipientIds = $this->issueNotificationRecipients((int) $updated->id, $actorId);
                $this->createNotifications(
                    $recipientIds,
                    $actorId,
                    'status_changed',
                    sprintf('Issue #%d status changed', (int) $updated->id),
                    sprintf('Issue #%d changed status from %s to %s.', (int) $updated->id, $previousStatus, $newStatus->value),
                    (int) $updated->id,
                );
            } elseif ($data->comment !== null && $data->comment !== '') {
                $recipientIds = $this->issueNotificationRecipients((int) $updated->id, $actorId);
                $this->createNotifications(
                    $recipientIds,
                    $actorId,
                    'comment_added',
                    sprintf('Issue #%d new comment', (int) $updated->id),
                    sprintf('A new comment was added on issue #%d.', (int) $updated->id),
                    (int) $updated->id,
                );
            }

            return $this->reloadIssue((int) $updated->id);
        });
    }

    public function updateAssignee(User $actor, int $issueId, IssueAssigneeUpdateData $data): IssueReport
    {
        $issue = $this->findForView($actor, $issueId);
        $this->assertCanEditIssue($actor, $issue);
        $this->assertAssignableOrNull($data->assignedTo);

        $actorId = (int) $actor->id;
        $previousAssigneeId = (int) ($issue->assigned_to ?? 0);

        return DB::transaction(function () use ($issue, $data, $actorId, $previousAssigneeId): IssueReport {
            $updated = $this->repository->updateAssignee($issue, $data->assignedTo);

            if ($previousAssigneeId !== (int) ($data->assignedTo ?? 0)) {
                $previousLabel = $previousAssigneeId > 0 ? ('#'.$previousAssigneeId) : 'unassigned';
                $newLabel = ($data->assignedTo ?? 0) > 0 ? ('#'.(int) $data->assignedTo) : 'unassigned';

                $this->repository->addComment(
                    $updated,
                    $actorId,
                    sprintf('Assignee changed: %s -> %s', $previousLabel, $newLabel),
                );

                if ($data->assignedTo !== null) {
                    $this->createNotifications(
                        [$data->assignedTo],
                        $actorId,
                        'assigned',
                        sprintf('Issue #%d assigned', (int) $updated->id),
                        sprintf('Issue #%d was assigned to you.', (int) $updated->id),
                        (int) $updated->id,
                    );
                }
            }

            return $this->reloadIssue((int) $updated->id);
        });
    }

    public function addComment(User $actor, int $issueId, IssueCommentData $data): IssueReport
    {
        $issue = $this->findForView($actor, $issueId);

        if ($data->comment === '') {
            throw new ApiException(
                errorCode: 'issue_comment_required',
                message: 'Comment is required.',
                statusCode: 422,
            );
        }

        $actorId = (int) $actor->id;

        return DB::transaction(function () use ($issue, $data, $actorId): IssueReport {
            $this->repository->addComment($issue, $actorId, $data->comment);

            $recipientIds = $this->issueNotificationRecipients((int) $issue->id, $actorId);
            $this->createNotifications(
                $recipientIds,
                $actorId,
                'comment_added',
                sprintf('Issue #%d new comment', (int) $issue->id),
                sprintf('A new comment was added on issue #%d.', (int) $issue->id),
                (int) $issue->id,
            );

            return $this->reloadIssue((int) $issue->id);
        });
    }

    /**
     * @return array{attachment:IssueReportAttachment,path:string}
     */
    public function attachmentForDownload(User $actor, int $attachmentId): array
    {
        $this->assertModuleAccess($actor);

        $attachment = $this->repository->findAttachmentById($attachmentId);
        if ($attachment === null) {
            throw new ApiException(
                errorCode: 'issue_attachment_not_found',
                message: 'Attachment not found.',
                statusCode: 404,
            );
        }

        $name = (string) $attachment->stored_name;
        abort_if($name === '' || $name === '.' || $name === '..' || strpbrk($name, '/\\') !== false, 404);
        $relativePath = 'issue_attachments/'.$name;

        if (! Storage::disk('local')->exists($relativePath)) {
            throw new ApiException(
                errorCode: 'issue_attachment_not_found',
                message: 'Attachment file not found.',
                statusCode: 404,
            );
        }

        $path = Storage::disk('local')->path($relativePath);

        return [
            'attachment' => $attachment,
            'path' => $path,
        ];
    }

    /**
     * @return array<string,list<string>>
     */
    public function moduleIssueMap(): array
    {
        return self::MODULE_ISSUES;
    }

    private function assertModuleAccess(User $actor): void
    {
        $allowed = [RoleKey::GROUP_LEADER, RoleKey::MANAGER, RoleKey::ADMIN, RoleKey::SUPER_ADMIN];
        $currentRoleKey = $actor->resolvedRoleKey();

        if (! $currentRoleKey instanceof RoleKey || ! in_array($currentRoleKey, $allowed, true)) {
            throw new ApiException(
                errorCode: 'issue_forbidden',
                message: 'You are not allowed to access issues.',
                statusCode: 403,
            );
        }
    }

    private function assertCanEditIssue(User $actor, IssueReport $issue): void
    {
        $actorId = (int) $actor->id;

        if ($this->isSuperAdmin($actor) || (int) $issue->created_by === $actorId) {
            return;
        }

        throw new ApiException(
            errorCode: 'issue_edit_forbidden',
            message: 'Only creator or super admin can edit this issue.',
            statusCode: 403,
        );
    }

    private function assertValidIssueKey(string $moduleKey, string $issueKey): void
    {
        $allowedIssueKeys = self::MODULE_ISSUES[$moduleKey] ?? null;

        if ($allowedIssueKeys === null) {
            throw new ApiException(
                errorCode: 'issue_module_invalid',
                message: 'Invalid issue module.',
                statusCode: 422,
            );
        }

        if (! in_array($issueKey, $allowedIssueKeys, true)) {
            throw new ApiException(
                errorCode: 'issue_key_invalid',
                message: 'Invalid issue key for selected module.',
                statusCode: 422,
            );
        }
    }

    private function assertAssignableOrNull(?int $assignedTo): void
    {
        if ($assignedTo === null) {
            return;
        }

        if (! $this->repository->isAssignableAdmin($assignedTo)) {
            throw new ApiException(
                errorCode: 'issue_assignee_invalid',
                message: 'Assignee must be admin, manager, or super admin.',
                statusCode: 422,
            );
        }
    }

    /** @param list<string> $files */
    private function storeAttachment(IssueReport $issue, UploadedFile $file, array &$files): void
    {
        $extension = strtolower($file->extension() ?: 'bin');
        $storedName = sprintf(
            'issue_%d_%s.%s',
            (int) $issue->id,
            bin2hex(random_bytes(10)),
            $extension,
        );

        $path = $file->storeAs('issue_attachments', $storedName, 'local');
        if ($path === false) {
            throw new RuntimeException('Issue attachment could not be stored.');
        }
        $files[] = $path;

        $this->repository->addAttachment(
            $issue,
            $storedName,
            $this->sanitizeAttachmentName($file->getClientOriginalName(), $extension),
            (string) ($file->getMimeType() ?? 'application/octet-stream'),
            (int) $file->getSize(),
        );
    }

    private function sanitizeAttachmentName(string $name, string $fallbackExtension): string
    {
        $normalized = trim(str_replace(["\r", "\n", '"', '\\', '/'], '_', $name));
        if ($normalized === '') {
            $normalized = 'screenshot.'.$fallbackExtension;
        }

        return mb_substr($normalized, 0, 180);
    }

    /**
     * @return list<int>
     */
    private function issueNotificationRecipients(int $issueId, int $actorId): array
    {
        $ids = [];

        foreach ($this->repository->involvedUserIds($issueId) as $candidateId) {
            if ($candidateId <= 0 || $candidateId === $actorId) {
                continue;
            }

            $ids[$candidateId] = $candidateId;
        }

        return array_values($ids);
    }

    /**
     * @param  list<int>  $recipientIds
     */
    private function createNotifications(
        array $recipientIds,
        int $actorId,
        string $actionKey,
        string $title,
        string $message,
        int $issueId,
    ): void {
        if ($recipientIds === []) {
            return;
        }

        foreach ($recipientIds as $recipientId) {
            UserNotification::query()->create([
                'user_id' => $recipientId,
                'actor_user_id' => $actorId > 0 ? $actorId : null,
                'notification_type' => self::NOTIFY_ENTITY.'.'.$actionKey,
                'module_key' => self::NOTIFY_MODULE_KEY,
                'action_key' => $actionKey,
                'entity_type' => self::NOTIFY_ENTITY,
                'entity_id' => $issueId,
                'title' => mb_substr($title, 0, 190),
                'message' => mb_substr($message, 0, 500),
                'link_path' => '/issues/view/'.$issueId,
                'status' => 'unread',
                'priority' => 'normal',
                'delivery_channel' => 'in_app',
                'is_sent' => true,
                'sent_at' => now(),
            ]);
        }
    }

    private function reloadIssue(int $issueId): IssueReport
    {
        $issue = $this->repository->findWithRelations($issueId);

        if ($issue === null) {
            throw new ApiException(
                errorCode: 'issue_not_found',
                message: 'Issue not found.',
                statusCode: 404,
            );
        }

        return $issue;
    }

    private function isSuperAdmin(User $actor): bool
    {
        return $actor->resolvedRoleKey() === RoleKey::SUPER_ADMIN;
    }
}
