export type NotificationStatus = 'all' | 'unread' | 'read';

export interface NotificationActor {
    id: number;
    username: string;
    firstname: string | null;
    surname: string | null;
    lastname: string | null;
}

export interface NotificationItem {
    id: number;
    user_id: number;
    actor_user_id: number | null;
    notification_type: string;
    module_key: string;
    action_key: string;
    entity_type: string;
    entity_id: number | null;
    title: string;
    message: string;
    metadata: Record<string, unknown> | null;
    link_path: string;
    status: 'unread' | 'read' | 'archived';
    priority: 'low' | 'normal' | 'high' | 'critical' | string;
    delivery_channel: string;
    is_sent: boolean;
    sent_at: string | null;
    created_at: string | null;
    read_at: string | null;
    actor: NotificationActor | null;
}

export interface NotificationListParams {
    status?: NotificationStatus;
    page?: number;
    per_page?: number;
}

export interface PaginationMeta {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}
