import type { LocalizedText } from '@/modules/localization';

export interface LocationServiceSummary {
    id: number;
    title: string;
    translations: LocalizedText;
}

export interface LocationRecord {
    id: number;
    location_from: string;
    location_to: string;
    from_time: string;
    to_time: string;
    service_id: number;
    service: LocationServiceSummary | null;
}

export interface LocationListParams {
    q?: string;
    page?: number;
    per_page?: number;
    service_id?: number;
}

export interface LocationPayload {
    location_from: string;
    location_to: string;
    from_time: string;
    to_time: string;
    service_id: number;
}

export interface PaginationMeta {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}
