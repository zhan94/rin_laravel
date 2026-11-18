import { httpRequest, type ApiEnvelope } from '@/services/http';
import { withQuery } from '@/services/queryString';
import type { NotificationItem, NotificationListParams } from './types';

const basePath = '/api/v1/notifications';

export function listNotifications(
    params: NotificationListParams = {},
): Promise<ApiEnvelope<NotificationItem[]>> {
    return httpRequest<NotificationItem[]>(
        withQuery(basePath, {
            status: params.status !== 'all' ? params.status : undefined,
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

export function markNotificationRead(
    notificationId: number,
): Promise<ApiEnvelope<NotificationItem>> {
    return httpRequest<NotificationItem>(`${basePath}/${notificationId}/read`, {
        method: 'POST',
    });
}

export function markAllNotificationsRead(): Promise<
    ApiEnvelope<{ updated: number }>
> {
    return httpRequest<{ updated: number }>(`${basePath}/read-all`, {
        method: 'POST',
    });
}
