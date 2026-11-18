import { useToast } from '@/composables/useToast';
import { resolveNotificationLink } from '@/modules/notifications/linkResolver';
import {
    listNotifications,
    markAllNotificationsRead,
    markNotificationRead,
} from '@/modules/notifications/notificationService';
import type {
    NotificationItem,
    PaginationMeta,
} from '@/modules/notifications/types';
import { HttpError } from '@/services/http';
import { router } from '@inertiajs/vue3';
import { reactive } from 'vue';

type RefreshOptions = {
    silent?: boolean;
};

type NotificationState = {
    loading: boolean;
    actionInProgress: boolean;
    unreadCount: number;
    notifications: NotificationItem[];
    pagination: PaginationMeta;
    initialized: boolean;
};

const POLL_INTERVAL_MS = 20_000;

const state = reactive<NotificationState>({
    loading: false,
    actionInProgress: false,
    unreadCount: 0,
    notifications: [],
    pagination: {
        total: 0,
        per_page: 8,
        current_page: 1,
        last_page: 1,
    },
    initialized: false,
});

const { error, info, success } = useToast();

let pollTimer: ReturnType<typeof setInterval> | null = null;
let consumers = 0;
let currentRequest: Promise<void> | null = null;
let pollErrorShown = false;

function setNotificationReadLocal(notificationId: number): void {
    state.notifications = state.notifications.map((row) => {
        if (row.id !== notificationId) {
            return row;
        }

        return {
            ...row,
            status: 'read',
            read_at: row.read_at ?? new Date().toISOString(),
        };
    });
}

async function refresh(options: RefreshOptions = {}): Promise<void> {
    if (currentRequest !== null) {
        return currentRequest;
    }

    currentRequest = (async () => {
        if (!options.silent) {
            state.loading = true;
        }

        try {
            const previousUnread = state.unreadCount;
            const response = await listNotifications({
                page: 1,
                per_page: 8,
            });

            state.notifications = response.data;
            state.unreadCount = Number(response.meta.unread_count ?? 0);
            state.pagination = (response.meta.pagination as
                | PaginationMeta
                | undefined) ?? {
                total: state.notifications.length,
                per_page: 8,
                current_page: 1,
                last_page: 1,
            };

            if (
                state.initialized &&
                state.unreadCount > previousUnread &&
                options.silent
            ) {
                const diff = state.unreadCount - previousUnread;
                const label = diff === 1 ? 'notification' : 'notifications';
                info('New Notifications', `${diff} new ${label} received.`);
            }

            state.initialized = true;
            pollErrorShown = false;
        } catch (err) {
            if (err instanceof HttpError && err.status === 401) {
                stopPolling();
                state.loading = false;
                if (!pollErrorShown) {
                    error('Session Expired', 'Please sign in again.');
                    pollErrorShown = true;
                }
                router.visit('/login');

                return;
            }

            const message =
                err instanceof HttpError
                    ? (err.errors[0]?.message ?? err.message)
                    : 'Could not refresh notifications.';

            if (!options.silent || !pollErrorShown) {
                error('Notifications Error', message);
                pollErrorShown = true;
            }
        } finally {
            if (!options.silent) {
                state.loading = false;
            }
        }
    })();

    try {
        await currentRequest;
    } finally {
        currentRequest = null;
    }
}

async function markRead(notification: NotificationItem): Promise<void> {
    if (notification.status === 'read') {
        return;
    }

    const previousUnread = state.unreadCount;
    setNotificationReadLocal(notification.id);
    state.unreadCount = Math.max(0, state.unreadCount - 1);

    try {
        const response = await markNotificationRead(notification.id);
        setNotificationReadLocal(response.data.id);
    } catch (err) {
        state.unreadCount = previousUnread;

        state.notifications = state.notifications.map((row) => {
            if (row.id !== notification.id) {
                return row;
            }

            return {
                ...row,
                status: notification.status,
                read_at: notification.read_at,
            };
        });

        const message =
            err instanceof HttpError
                ? (err.errors[0]?.message ?? err.message)
                : 'Could not mark notification as read.';
        error('Notifications Error', message);
    }
}

async function markAllRead(): Promise<void> {
    if (state.unreadCount <= 0 || state.actionInProgress) {
        return;
    }

    state.actionInProgress = true;

    const previousRows = [...state.notifications];
    const previousUnread = state.unreadCount;

    state.notifications = state.notifications.map((row) => ({
        ...row,
        status: 'read',
        read_at: row.read_at ?? new Date().toISOString(),
    }));
    state.unreadCount = 0;

    try {
        const response = await markAllNotificationsRead();
        const updated = Number(response.data.updated ?? 0);
        success(
            'Notifications Updated',
            `Marked ${updated} notifications as read.`,
        );
    } catch (err) {
        state.notifications = previousRows;
        state.unreadCount = previousUnread;

        const message =
            err instanceof HttpError
                ? (err.errors[0]?.message ?? err.message)
                : 'Could not mark all notifications as read.';
        error('Notifications Error', message);
    } finally {
        state.actionInProgress = false;
    }
}

async function openNotification(notification: NotificationItem): Promise<void> {
    await markRead(notification);

    const destination = resolveNotificationLink({
        linkPath: notification.link_path,
        entityType: notification.entity_type,
        entityId: notification.entity_id,
    });

    router.visit(destination);
}

function pollTick(): void {
    if (document.hidden) {
        return;
    }

    void refresh({ silent: true });
}

function startPolling(): void {
    consumers += 1;

    if (consumers > 1) {
        return;
    }

    void refresh({ silent: true });

    pollTimer = setInterval(pollTick, POLL_INTERVAL_MS);
}

function stopPolling(): void {
    consumers = Math.max(0, consumers - 1);

    if (consumers > 0) {
        return;
    }

    if (pollTimer !== null) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
}

export function useNotifications() {
    return {
        state,
        refresh,
        startPolling,
        stopPolling,
        markRead,
        markAllRead,
        openNotification,
    };
}
