export interface LogUserSummary {
    id: number;
    username: string;
    firstname: string | null;
    surname: string | null;
    lastname: string | null;
}

export interface LogRow {
    id: number;
    user_id: number;
    page: string;
    ip: string;
    location: string | null;
    date: string | null;
    user: LogUserSummary | null;
}

export interface LogListParams {
    page?: number;
    per_page?: number;
}

export interface PaginationMeta {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}
