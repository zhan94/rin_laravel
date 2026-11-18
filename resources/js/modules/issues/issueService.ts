import { httpRequest, type ApiEnvelope } from '@/services/http';
import { withQuery } from '@/services/queryString';
import type {
    IssueAssigneePayload,
    IssueCommentPayload,
    IssueCreatePayload,
    IssueListParams,
    IssueOverview,
    IssueRecord,
    IssueStatusPayload,
} from './types';

const basePath = '/api/v1/issues';

export function listIssues(
    params: IssueListParams = {},
): Promise<ApiEnvelope<IssueOverview>> {
    return httpRequest<IssueOverview>(
        withQuery(basePath, {
            scope: params.scope !== 'all' ? params.scope : undefined,
            status: params.status !== 'all' ? params.status : undefined,
            module: params.module !== 'all' ? params.module : undefined,
            page:
                params.page !== undefined && params.page > 0
                    ? params.page
                    : undefined,
            per_page:
                params.per_page !== undefined && params.per_page > 0
                    ? params.per_page
                    : undefined,
        }),
    );
}

export function getIssue(issueId: number): Promise<ApiEnvelope<IssueRecord>> {
    return httpRequest<IssueRecord>(`${basePath}/${issueId}`);
}

export function createIssue(
    payload: IssueCreatePayload,
): Promise<ApiEnvelope<IssueRecord>> {
    const formData = new FormData();

    formData.set('module_key', payload.module_key);
    formData.set('issue_key', payload.issue_key);
    formData.set('priority', payload.priority);
    formData.set('description', payload.description);

    if (payload.title && payload.title.trim() !== '') {
        formData.set('title', payload.title.trim());
    }

    if (payload.page_url && payload.page_url.trim() !== '') {
        formData.set('page_url', payload.page_url.trim());
    }

    if (payload.assigned_to !== undefined && payload.assigned_to !== null) {
        formData.set('assigned_to', String(payload.assigned_to));
    }

    for (const file of payload.screenshots ?? []) {
        formData.append('screenshots[]', file);
    }

    return httpRequest<IssueRecord>(basePath, {
        method: 'POST',
        body: formData,
    });
}

export function updateIssueStatus(
    issueId: number,
    payload: IssueStatusPayload,
): Promise<ApiEnvelope<IssueRecord>> {
    return httpRequest<IssueRecord>(`${basePath}/${issueId}/status`, {
        method: 'PATCH',
        body: {
            status: payload.status,
            comment: payload.comment ?? '',
        },
    });
}

export function updateIssueAssignee(
    issueId: number,
    payload: IssueAssigneePayload,
): Promise<ApiEnvelope<IssueRecord>> {
    return httpRequest<IssueRecord>(`${basePath}/${issueId}/assignee`, {
        method: 'PATCH',
        body: {
            assigned_to: payload.assigned_to,
        },
    });
}

export function addIssueComment(
    issueId: number,
    payload: IssueCommentPayload,
): Promise<ApiEnvelope<IssueRecord>> {
    return httpRequest<IssueRecord>(`${basePath}/${issueId}/comments`, {
        method: 'POST',
        body: {
            comment: payload.comment,
        },
    });
}

export function issueAttachmentUrl(
    attachmentId: number,
    download = false,
): string {
    return download
        ? `${basePath}/attachments/${attachmentId}?download=1`
        : `${basePath}/attachments/${attachmentId}`;
}
