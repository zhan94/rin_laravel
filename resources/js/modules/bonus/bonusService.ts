import { httpRequest, type ApiEnvelope } from '@/services/http';
import { withQuery } from '@/services/queryString';
import type { BonusListParams, BonusPayload, BonusRecord } from './types';

const bonusBasePath = '/api/v1/bonus';

export function listBonus(
    params: BonusListParams = {},
): Promise<ApiEnvelope<BonusRecord[]>> {
    return httpRequest<BonusRecord[]>(
        withQuery(bonusBasePath, {
            q: params.q,
            page: params.page,
            per_page: params.per_page,
        }),
    );
}

export function createBonus(
    payload: BonusPayload,
): Promise<ApiEnvelope<BonusRecord>> {
    return httpRequest<BonusRecord>(bonusBasePath, {
        method: 'POST',
        body: payload,
    });
}

export function updateBonus(
    bonusId: number,
    payload: BonusPayload,
): Promise<ApiEnvelope<BonusRecord>> {
    return httpRequest<BonusRecord>(`${bonusBasePath}/${bonusId}`, {
        method: 'PUT',
        body: payload,
    });
}

export function deleteBonus(
    bonusId: number,
): Promise<ApiEnvelope<{ deleted: boolean }>> {
    return httpRequest<{ deleted: boolean }>(`${bonusBasePath}/${bonusId}`, {
        method: 'DELETE',
    });
}
