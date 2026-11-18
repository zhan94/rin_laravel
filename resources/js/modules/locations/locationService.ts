import { httpRequest, type ApiEnvelope } from '@/services/http';
import { withQuery } from '@/services/queryString';
import type {
    LocationListParams,
    LocationPayload,
    LocationRecord,
} from './types';

const locationsBasePath = '/api/v1/locations';

export function listLocations(
    params: LocationListParams = {},
): Promise<ApiEnvelope<LocationRecord[]>> {
    return httpRequest<LocationRecord[]>(
        withQuery(locationsBasePath, {
            q: params.q,
            page: params.page,
            per_page: params.per_page,
            service_id: params.service_id,
        }),
    );
}

export function createLocation(
    payload: LocationPayload,
): Promise<ApiEnvelope<LocationRecord>> {
    return httpRequest<LocationRecord>(locationsBasePath, {
        method: 'POST',
        body: payload,
    });
}

export function updateLocation(
    locationId: number,
    payload: LocationPayload,
): Promise<ApiEnvelope<LocationRecord>> {
    return httpRequest<LocationRecord>(`${locationsBasePath}/${locationId}`, {
        method: 'PUT',
        body: payload,
    });
}

export function deleteLocation(
    locationId: number,
): Promise<ApiEnvelope<{ deleted: boolean }>> {
    return httpRequest<{ deleted: boolean }>(
        `${locationsBasePath}/${locationId}`,
        {
            method: 'DELETE',
        },
    );
}
