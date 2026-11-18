<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Issues\IssueAssigneeUpdateData;
use App\DTOs\Issues\IssueCommentData;
use App\DTOs\Issues\IssueCreateData;
use App\DTOs\Issues\IssueFilterData;
use App\DTOs\Issues\IssueStatusUpdateData;
use App\Http\Controllers\Api\Concerns\InteractsWithApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Issues\IndexIssueRequest;
use App\Http\Requests\Api\V1\Issues\StoreIssueCommentRequest;
use App\Http\Requests\Api\V1\Issues\StoreIssueRequest;
use App\Http\Requests\Api\V1\Issues\UpdateIssueAssigneeRequest;
use App\Http\Requests\Api\V1\Issues\UpdateIssueStatusRequest;
use App\Http\Resources\Api\V1\Issues\IssueReportResource;
use App\Models\IssueReportAttachment;
use App\Models\User;
use App\Services\IssueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class IssueController extends Controller
{
    use InteractsWithApiResponses;

    public function __construct(
        private readonly IssueService $service,
    ) {}

    public function index(IndexIssueRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $overview = $this->service->overview(
            $user,
            IssueFilterData::fromArray($request->validated()),
        );

        return $this->success(
            data: [
                'rows' => IssueReportResource::collection((array) ($overview['rows'] ?? []))->resolve(),
                'summary' => (array) ($overview['summary'] ?? []),
                'filters' => (array) ($overview['filters'] ?? []),
                'allowed_statuses' => (array) ($overview['allowed_statuses'] ?? []),
                'allowed_priorities' => (array) ($overview['allowed_priorities'] ?? []),
                'module_issue_map' => (array) ($overview['module_issue_map'] ?? []),
                'assignable_admins' => (array) ($overview['assignable_admins'] ?? []),
            ],
            meta: [
                'pagination' => (array) ($overview['pagination'] ?? []),
            ],
        );
    }

    public function moduleIssueMap(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->service->overview(
            $user,
            IssueFilterData::fromArray([]),
        );

        return $this->success(
            data: [
                'module_issue_map' => $this->service->moduleIssueMap(),
            ],
        );
    }

    public function store(StoreIssueRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $issue = $this->service->create(
            $user,
            IssueCreateData::fromArray($request->validated()),
        );

        return $this->success(
            data: (new IssueReportResource($issue))->resolve(),
            status: JsonResponse::HTTP_CREATED,
        );
    }

    public function show(Request $request, int $issue): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $record = $this->service->findForView($user, $issue);

        return $this->success(
            data: (new IssueReportResource($record))->resolve(),
        );
    }

    public function updateStatus(UpdateIssueStatusRequest $request, int $issue): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $record = $this->service->updateStatus(
            $user,
            $issue,
            IssueStatusUpdateData::fromArray($request->validated()),
        );

        return $this->success(
            data: (new IssueReportResource($record))->resolve(),
        );
    }

    public function updateAssignee(UpdateIssueAssigneeRequest $request, int $issue): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $record = $this->service->updateAssignee(
            $user,
            $issue,
            IssueAssigneeUpdateData::fromArray($request->validated()),
        );

        return $this->success(
            data: (new IssueReportResource($record))->resolve(),
        );
    }

    public function storeComment(StoreIssueCommentRequest $request, int $issue): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $record = $this->service->addComment(
            $user,
            $issue,
            IssueCommentData::fromArray($request->validated()),
        );

        return $this->success(
            data: (new IssueReportResource($record))->resolve(),
        );
    }

    public function attachment(Request $request, int $attachment): BinaryFileResponse
    {
        /** @var User $user */
        $user = $request->user();

        $payload = $this->service->attachmentForDownload($user, $attachment);

        /** @var IssueReportAttachment $attachmentModel */
        $attachmentModel = $payload['attachment'];
        $path = (string) $payload['path'];
        $download = (string) $request->query('download', '0') === '1';

        $mimeType = trim((string) $attachmentModel->mime_type);
        $safeInline = in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true);
        $headers = ['Content-Type' => $safeInline ? $mimeType : 'application/octet-stream', 'Cache-Control' => 'private, no-store'];

        $originalName = trim((string) $attachmentModel->original_name);
        $safeName = str_replace(["\r", "\n", '"'], '_', $originalName);
        if ($safeName === '') {
            $safeName = 'issue_attachment_'.$attachmentModel->id;
        }

        return response()->download(
            $path,
            $safeName,
            $headers,
            $download || ! $safeInline ? 'attachment' : 'inline',
        );
    }
}
