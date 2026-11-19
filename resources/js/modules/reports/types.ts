export type ReportSortKey =
    | 'id'
    | 'name'
    | 'birth'
    | 'level'
    | 'equip'
    | 'location'
    | 'due'
    | 'parent';

export type ReportSortDirection = 'asc' | 'desc';

export interface ReportSkill {
    id: number;
    title: string;
}

export interface ReportEquip {
    id: number | null;
    title: string;
}

export interface ReportFilters {
    service_id: number | null;
    from: string | null;
    to: string | null;
    q: string;
    sort: ReportSortKey;
    dir: ReportSortDirection;
}

export interface ReportRow {
    booking_id: number;
    customer_id: number;
    customer_name: string;
    birth: string | null;
    parent_name: string | null;
    parent_phone: string | null;
    email: string | null;
    location: string;
    dates: string[];
    sum_paid: number;
    paid: number;
    due: number;
    note: string | null;
    skill: ReportSkill | null;
    equip: ReportEquip | null;
}

export interface ReportSummary {
    sum_total: number;
    paid_total: number;
    due_total: number;
    due_count: number;
    total_count: number;
}

export interface ReportOverview {
    is_driver: boolean;
    filters: ReportFilters;
    summary: ReportSummary;
    rows: ReportRow[];
}

export interface ReportServiceOption {
    id: number;
    title: string;
}

export interface ReportListParams {
    service_id?: number;
    from?: string;
    to?: string;
    q?: string;
    page?: number;
    per_page?: number;
    sort?: ReportSortKey;
    dir?: ReportSortDirection;
}

export interface PaginationMeta {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}
