import type { LocalizedText } from '@/modules/localization';

export interface ServiceDefinition {
    id: number;
    title: string;
    translations: LocalizedText;
    title_key: number | null;
    max: number;
    active: boolean;
    slots_count: number | null;
}

export interface ServiceDate {
    id: number;
    slot_id: number;
    slot_date: string | null;
    is_active: boolean;
    bookings_count?: number;
    has_bookings?: boolean;
}

export interface ServiceSlot {
    id: number;
    service_id: number;
    name: string;
    date_start: string | null;
    date_end: string | null;
    max: number;
    season_type: string | null;
    dates: ServiceDate[];
    created_at: string | null;
}

export interface ServiceListParams {
    q?: string;
    page?: number;
    per_page?: number;
    active?: boolean;
}

export interface ServiceCreatePayload {
    title?: string;
    translations?: LocalizedText;
    max: number;
    active?: boolean;
}

export interface ServiceSlotCreatePayload {
    name: string;
    max: number;
    season_type?: 'winter' | 'summer' | 'other';
    dates: string[];
}

export interface PaginationMeta {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}

export type ServiceSeasonStatus =
    | 'active'
    | 'upcoming'
    | 'expired'
    | 'unscheduled';
export type ServiceSeasonSort = 'service' | 'season' | 'date' | 'status';

export interface ServiceSeason {
    id: number;
    service_id: number;
    service_name: string;
    service_active: boolean;
    season_name: string;
    date_start: string | null;
    date_end: string | null;
    capacity: number;
    status: ServiceSeasonStatus;
    dates_count: number;
    prices: { id: number; days: number; price: string }[];
}

export interface ServiceSeasonDate {
    id: number;
    date: string | null;
    active: boolean;
    booked: number;
    remaining: number;
    bookings_count: number;
}

export interface ServiceSeasonBooking {
    id: number;
    customer: string | null;
    status: 'active' | 'cancelled';
}

export interface ServiceSeasonFilters {
    q: string;
    service_id: number;
    date: string;
    status: ServiceSeasonStatus | '';
    sort: ServiceSeasonSort;
    direction: 'asc' | 'desc';
}
