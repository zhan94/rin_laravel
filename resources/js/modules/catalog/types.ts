import type { LocalizedText } from '@/modules/localization';

export interface CatalogPrice {
    id: number;
    days: number;
    price_bgn: number;
    per_day_bgn: number;
    price_eur: number;
    per_day_eur: number;
}

export interface CatalogServiceSummary {
    id: number;
    title: string;
    translations: LocalizedText;
}

export interface CardCatalogRecord {
    id: number;
    title: string;
    translations: LocalizedText;
    service_id: number;
    service: CatalogServiceSummary | null;
    prices: CatalogPrice[];
    prices_count: number | null;
}

export interface EquipCatalogRecord {
    id: number;
    title: string;
    translations: LocalizedText;
    service_id: number;
    service: CatalogServiceSummary | null;
    prices: CatalogPrice[];
    prices_count: number | null;
}

export interface CatalogListParams {
    q?: string;
    page?: number;
    per_page?: number;
    service_id?: number;
}

export interface CatalogPricePayload {
    days: number;
    price_eur: number;
}

export interface CardCatalogPayload {
    title?: string;
    translations?: LocalizedText;
    service: number;
    prices: CatalogPricePayload[];
}

export interface EquipCatalogPayload {
    title?: string;
    translations?: LocalizedText;
    service: number;
    prices: CatalogPricePayload[];
}

export interface PaginationMeta {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}
