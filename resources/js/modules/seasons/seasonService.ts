import { httpRequest, type ApiEnvelope } from '@/services/http';
import { withQuery } from '@/services/queryString';
import type {
    SeasonDefaults,
    SeasonDefaultsPayload,
    SeasonOverview,
    SeasonOverviewParams,
    SeasonSummary,
    ServiceSeasonPayload,
} from './types';

const basePath = '/api/v1/seasons';

export function getSeasonOverview(
    params: SeasonOverviewParams = {},
): Promise<ApiEnvelope<SeasonOverview>> {
    return httpRequest<SeasonOverview>(
        withQuery(basePath, {
            type: params.type,
            service: params.service,
            season:
                params.season !== undefined && params.season > 0
                    ? params.season
                    : undefined,
        }),
    );
}

export function createServiceSeason(
    payload: ServiceSeasonPayload,
): Promise<ApiEnvelope<SeasonSummary>> {
    return httpRequest<SeasonSummary>(basePath, {
        method: 'POST',
        body: payload,
    });
}

export function updateServiceSeason(
    seasonId: number,
    payload: ServiceSeasonPayload,
): Promise<ApiEnvelope<SeasonSummary>> {
    return httpRequest<SeasonSummary>(`${basePath}/${seasonId}`, {
        method: 'PUT',
        body: payload,
    });
}

export function deleteServiceSeason(
    seasonId: number,
): Promise<ApiEnvelope<{ deleted: boolean }>> {
    return httpRequest<{ deleted: boolean }>(`${basePath}/${seasonId}`, {
        method: 'DELETE',
    });
}

export function updateSeasonDefaults(
    payload: SeasonDefaultsPayload,
): Promise<ApiEnvelope<SeasonDefaults>> {
    return httpRequest<SeasonDefaults>(`${basePath}/defaults`, {
        method: 'PUT',
        body: payload,
    });
}
