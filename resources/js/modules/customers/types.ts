export interface Customer {
    id: number;
    firstname: string;
    surname: string | null;
    lastname: string;
    full_name: string;
    date_birth: string | null;
    age: number | null;
    parent_name: string | null;
    parent_phone: string | null;
    email: string | null;
    active: boolean;
    created_at: string | null;
    updated_at: string | null;
}

export interface CustomerListParams {
    q?: string;
    page?: number;
    per_page?: number;
}

export interface CustomerUnpaidRow {
    customer_id: number;
    customer_name: string;
    date_birth: string | null;
    booking_id: number;
    service_id: number | null;
    service_type: number | null;
    service_title: string;
    sum_paid: number;
    paid: number;
    due: number;
    season_label: string;
}

export interface CustomerUnpaidTab {
    id: string;
    label: string;
    is_current: boolean;
    count: number;
    due_total: number;
}

export interface CustomerCreatePayload {
    firstname: string;
    surname?: string | null;
    lastname: string;
    date_birth?: string | null;
    age?: number | null;
    parent_name?: string | null;
    parent_phone?: string | null;
    email?: string | null;
    active?: boolean;
}

export interface PaginationMeta {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}
