import { httpRequest, type ApiEnvelope } from '@/services/http';
import { withQuery } from '@/services/queryString';
import type { LogListParams, LogRow } from './types';

const basePath = '/api/v1/logs';

export function listLogs(
    params: LogListParams = {},
): Promise<ApiEnvelope<LogRow[]>> {
    return httpRequest<LogRow[]>(
        withQuery(basePath, {
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

export function deleteLog(
    logId: number,
): Promise<ApiEnvelope<{ deleted: boolean }>> {
    return httpRequest<{ deleted: boolean }>(`${basePath}/${logId}`, {
        method: 'DELETE',
    });
}

export function deleteSelectedLogs(
    ids: number[],
): Promise<ApiEnvelope<{ deleted: number }>> {
    return httpRequest<{ deleted: number }>(`${basePath}/delete-selected`, {
        method: 'POST',
        body: {
            ids,
        },
    });
}

export function clearLogs(): Promise<ApiEnvelope<{ deleted: number }>> {
    return httpRequest<{ deleted: number }>(basePath, {
        method: 'DELETE',
    });
}
