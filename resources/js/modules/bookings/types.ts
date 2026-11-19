import type { LocalizedText } from '@/modules/localization';

export interface BookingDate {
    id: number;
    service_date_id: number;
    slot_id: number;
    slot_date: string | null;
    slot_name: string | null;
}

export interface BookingPayment {
    booking_id: number;
    sum_paid: number | null;
    paid: number | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface BookingWinterDetail {
    booking_id: number;
    skill_id: number | null;
    equip_id: number | null;
    equip_enabled: boolean | null;
    card_id: number | null;
    shoe_size: number | null;
    height: number | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface BookingRecord {
    id: number;
    customer_id: number | null;
    service_id: number | null;
    service_type: number | null;
    location_id: number | null;
    sum_paid: number | null;
    paid: number | null;
    due: number | null;
    approved: boolean;
    active: boolean;
    lunch: boolean;
    note: string | null;
    customer: {
        id: number;
        full_name: string;
        email: string | null;
    } | null;
    booking_dates: BookingDate[];
    payment: BookingPayment | null;
    winter_detail: BookingWinterDetail | null;
}

export interface BookingListParams {
    q?: string;
    page?: number;
    per_page?: number;
    service_id?: number;
    service_type?: number;
    customer_id?: number;
    active?: boolean;
    date?: string;
}

export interface BookingCreatePayload {
    customer_id?: number | null;
    firstname?: string;
    surname?: string | null;
    lastname?: string;
    date_birth?: string | null;
    parent_name?: string | null;
    parent_phone?: string | null;
    email?: string | null;
    service_type: 1 | 2;
    service_id: number;
    slot_id?: number | null;
    location_id?: number | null;
    dates: string[];
    sum_paid?: number | null;
    paid?: number | null;
    approved?: boolean;
    lunch?: boolean;
    note?: string | null;
    skill_id?: number | null;
    equip_id?: number | null;
    card_id?: number | null;
    ski_shoe_size?: number | null;
    ski_height?: number | null;
    equip_enabled?: boolean;
}

export interface PaginationMeta {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}

export interface BookingSupportSuggestion {
    value: string;
    label: string;
    customer_id: number;
}

export interface BookingSupportCustomer {
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
}

export interface BookingSupportOption {
    id: number;
    title: string;
    translations: LocalizedText;
}

export interface BookingSupportSlot {
    id: number;
    service_id: number;
    name: string;
    date_start: string | null;
    date_end: string | null;
    max: number;
}

export interface BookingSupportServiceDate {
    id: number;
    slot_id: number;
    slot_date: string;
}

export interface BookingSupportLocation {
    id: number;
    location_from: string;
    location_to: string;
    from_time: string;
    to_time: string;
    service_id: number;
    is_global: boolean;
    label: string;
}

export interface BookingSupportAvailability {
    disabled_dates: string[];
    locations: BookingSupportLocation[];
    location_groups: {
        main: BookingSupportLocation[];
        custom: BookingSupportLocation[];
    };
}

export interface BookingSupportBonusContext {
    found: boolean;
    id?: number;
    location: string;
    date_from: string | null;
    date_to: string | null;
}

export interface BookingSupportPrefillBooking {
    id: number;
    location: number;
    skill_id: number | null;
    card_id: number | null;
    equip_id: number | null;
    equip_enabled: boolean;
    approved: boolean;
    lunch: boolean;
    sum_paid: number | null;
    paid: number | null;
    note: string | null;
    ski_form: {
        shoe_size: string;
        height: string;
        skill_id: string;
        equip_enabled: string;
    };
    last_date: string | null;
    season_start: string;
    season_end: string;
}

export interface BookingSupportCustomerPrefill {
    found: boolean;
    booking: BookingSupportPrefillBooking | null;
    season_start?: string;
    season_end?: string;
}

export interface BookingPaymentValidationResult {
    booking_id: number;
    valid: boolean;
    amount_eur: number;
    amount_bgn: number;
    remaining_before_eur: number | null;
    remaining_before_bgn: number | null;
    remaining_after_eur: number | null;
    remaining_after_bgn: number | null;
    message: string;
}

export interface BookingPaymentApplyResult {
    booking: BookingRecord;
    payment: {
        mode: 'increment' | 'set';
        amount_eur: number;
        amount_bgn: number;
        paid_eur: number | null;
        paid_bgn: number;
        sum_paid_eur: number | null;
        sum_paid_bgn: number | null;
        remaining_eur: number | null;
        remaining_bgn: number | null;
    };
}
