<?php

declare(strict_types=1);

namespace App\Repositories\Contracts\Issues;

use App\DTOs\Issues\IssueCreateData;
use App\DTOs\Issues\IssueFilterData;
use App\Enums\IssueStatus;
use App\Models\IssueReport;
use App\Models\IssueReportAttachment;
use App\Models\IssueReportComment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IssueRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, \App\Models\IssueReport>
     */
    public function paginate(IssueFilterData $filters, ?int $scopeUserId): LengthAwarePaginator;

    /**
     * @return array{total_count:int,open_count:int,in_progress_count:int,resolved_count:int,urgent_count:int}
     */
    public function summary(?int $scopeUserId, string $scope): array;

    public function create(int $reporterId, IssueCreateData $data): IssueReport;

    public function findWithRelations(int $issueId): ?IssueReport;

    public function touch(IssueReport $issue): void;

    public function updateStatus(IssueReport $issue, IssueStatus $status, ?int $resolvedBy): IssueReport;

    public function updateAssignee(IssueReport $issue, ?int $assigneeId): IssueReport;

    public function addComment(IssueReport $issue, int $userId, string $comment): IssueReportComment;

    public function addAttachment(
        IssueReport $issue,
        string $storedName,
        string $originalName,
        string $mimeType,
        int $fileSize,
    ): IssueReportAttachment;

    public function findAttachmentById(int $attachmentId): ?IssueReportAttachment;

    /**
     * @return list<int>
     */
    public function involvedUserIds(int $issueId): array;

    /**
     * @return list<array{id:int,username:string,firstname:string|null,surname:string|null,lastname:string|null}>
     */
    public function assignableAdmins(): array;

    public function isAssignableAdmin(int $userId): bool;
}
