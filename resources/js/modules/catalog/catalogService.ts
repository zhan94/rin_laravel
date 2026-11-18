import { httpRequest, type ApiEnvelope } from '@/services/http';
import { withQuery } from '@/services/queryString';
import type {
    CardCatalogPayload,
    CardCatalogRecord,
    CatalogListParams,
    EquipCatalogPayload,
    EquipCatalogRecord,
} from './types';

const cardsBasePath = '/api/v1/cards';
const equipsBasePath = '/api/v1/equips';

function listQuery(params: CatalogListParams) {
    return {
        q: params.q,
        page: params.page,
        per_page: params.per_page,
        service_id: params.service_id || undefined,
    };
}

export function listCards(
    params: CatalogListParams = {},
): Promise<ApiEnvelope<CardCatalogRecord[]>> {
    return httpRequest<CardCatalogRecord[]>(
        withQuery(cardsBasePath, listQuery(params)),
    );
}

export function createCard(
    payload: CardCatalogPayload,
): Promise<ApiEnvelope<CardCatalogRecord>> {
    return httpRequest<CardCatalogRecord>(cardsBasePath, {
        method: 'POST',
        body: payload,
    });
}

export function updateCard(
    cardId: number,
    payload: CardCatalogPayload,
): Promise<ApiEnvelope<CardCatalogRecord>> {
    return httpRequest<CardCatalogRecord>(`${cardsBasePath}/${cardId}`, {
        method: 'PUT',
        body: payload,
    });
}

export function deleteCard(
    cardId: number,
): Promise<ApiEnvelope<{ deleted: boolean }>> {
    return httpRequest<{ deleted: boolean }>(`${cardsBasePath}/${cardId}`, {
        method: 'DELETE',
    });
}

export function listEquips(
    params: CatalogListParams = {},
): Promise<ApiEnvelope<EquipCatalogRecord[]>> {
    return httpRequest<EquipCatalogRecord[]>(
        withQuery(equipsBasePath, listQuery(params)),
    );
}

export function createEquip(
    payload: EquipCatalogPayload,
): Promise<ApiEnvelope<EquipCatalogRecord>> {
    return httpRequest<EquipCatalogRecord>(equipsBasePath, {
        method: 'POST',
        body: payload,
    });
}

export function updateEquip(
    equipId: number,
    payload: EquipCatalogPayload,
): Promise<ApiEnvelope<EquipCatalogRecord>> {
    return httpRequest<EquipCatalogRecord>(`${equipsBasePath}/${equipId}`, {
        method: 'PUT',
        body: payload,
    });
}

export function deleteEquip(
    equipId: number,
): Promise<ApiEnvelope<{ deleted: boolean }>> {
    return httpRequest<{ deleted: boolean }>(`${equipsBasePath}/${equipId}`, {
        method: 'DELETE',
    });
}
