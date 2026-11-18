import type { LocalizedText } from '@/modules/localization';

export type SeasonViewType = 'service' | 'bonus';

export interface ServiceOption {
    id: number;
    title: string;
    translations: LocalizedText;
}

export interface SeasonDefaults {
    start_month: number;
    start_day: number;
    end_month: number;
    end_day: number;
    updated_at: string | null;
}

export interface SeasonSummary {
    id: number;
    label: string;
    title: string | null;
    date_start: string | null;
    date_end: string | null;
    date_start_label: string | null;
    date_end_label: string | null;
    date_range: string;
    dates_count: number | null;
    bookings_count: number;
    customers_count: number;
    sum_total: number;
    paid_total: number;
    due_total: number;
    location: string | null;
    is_selected: boolean;
}

export interface SeasonBooking {
    booking_id: number;
    customer_id: number;
    name: string;
    phone: string | null;
    location: string | null;
    sum_paid: number;
    paid: number;
    due: number;
    dates: string[];
}

export interface SeasonOverview {
    type: SeasonViewType;
    services: ServiceOption[];
    bonus_titles: string[];
    selected_service_id: number | null;
    selected_bonus_title: string | null;
    season_defaults: SeasonDefaults;
    seasons: SeasonSummary[];
    selected_season: SeasonSummary | null;
    season_bookings: SeasonBooking[];
}

export interface SeasonOverviewParams {
    type?: SeasonViewType;
    service?: string | number;
    season?: number;
}

export interface ServiceSeasonPayload {
    service_id?: number;
    season_name?: string;
    season_start: string;
    season_end: string;
}

export interface SeasonDefaultsPayload {
    default_start_month: number;
    default_start_day: number;
    default_end_month: number;
    default_end_day: number;
}
