import type { LocalizedText } from '@/modules/localization';

export interface BonusPrice {
    id: number;
    days: number;
    price_bgn: number;
    per_day_bgn: number;
    price_eur: number;
    per_day_eur: number;
}

export interface BonusRecord {
    id: number;
    title: string;
    translations: LocalizedText;
    title_key: number | null;
    location_from: string;
    location_to: string;
    date_from: string | null;
    date_to: string | null;
    description: string | null;
    price_bgn: number | null;
    price_eur: number | null;
    prices: BonusPrice[];
    prices_count: number | null;
}

export interface BonusListParams {
    q?: string;
    page?: number;
    per_page?: number;
}

export interface BonusPricePayload {
    days: number;
    price_eur: number;
}

export interface BonusPayload {
    title?: string;
    translations?: LocalizedText;
    title_key?: number | null;
    location_from: string;
    location_to: string;
    date_from?: string | null;
    date_to?: string | null;
    description?: string | null;
    price_eur?: number | null;
    prices: BonusPricePayload[];
}

export interface PaginationMeta {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}
