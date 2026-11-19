import type { LocalizedText } from '@/modules/localization';

export interface UserRoleOption {
    id: number;
    role_key: string;
    title: string;
    translations: LocalizedText;
    is_active: boolean;
    sort_order: number;
}

export interface UserRoleSummary {
    id: number;
    role_key: string;
    title: string;
    translations: LocalizedText;
}

export interface UserRecord {
    id: number;
    username: string;
    name: string;
    firstname: string | null;
    surname: string | null;
    lastname: string | null;
    email: string | null;
    role: UserRoleSummary | null;
    role_id: number | null;
    is_active: boolean;
    can_delete: boolean;
    created_at: string | null;
    updated_at: string | null;
}

export interface UserListParams {
    q?: string;
    page?: number;
    per_page?: number;
}

export interface UserCreatePayload {
    username: string;
    firstname: string;
    surname?: string | null;
    lastname: string;
    email?: string | null;
    password: string;
    role_id: number;
    is_active?: boolean;
}

export interface UserUpdatePayload {
    username: string;
    firstname: string;
    surname?: string | null;
    lastname: string;
    email?: string | null;
    password?: string | null;
    role_id?: number | null;
    is_active?: boolean;
}

export interface PaginationMeta {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}
