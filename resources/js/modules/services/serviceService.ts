import { httpRequest, type ApiEnvelope } from '@/services/http';
import { withQuery } from '@/services/queryString';
import type {
    ServiceCreatePayload,
    ServiceDefinition,
    ServiceListParams,
    ServiceSlot,
    ServiceSlotCreatePayload,
} from './types';

const servicesBasePath = '/api/v1/services';

export function listServices(
    params: ServiceListParams = {},
): Promise<ApiEnvelope<ServiceDefinition[]>> {
    return httpRequest<ServiceDefinition[]>(
        withQuery(servicesBasePath, {
            q: params.q,
            page: params.page,
            per_page: params.per_page,
            active: params.active,
        }),
    );
}

export function createService(
    payload: ServiceCreatePayload,
): Promise<ApiEnvelope<ServiceDefinition>> {
    return httpRequest<ServiceDefinition>(servicesBasePath, {
        method: 'POST',
        body: payload,
    });
}

export function updateService(
    serviceId: number,
    payload: ServiceCreatePayload,
): Promise<ApiEnvelope<ServiceDefinition>> {
    return httpRequest<ServiceDefinition>(`${servicesBasePath}/${serviceId}`, {
        method: 'PUT',
        body: payload,
    });
}

export function listServiceSlots(
    serviceId: number,
    params: { page?: number; per_page?: number } = {},
): Promise<ApiEnvelope<ServiceSlot[]>> {
    return httpRequest<ServiceSlot[]>(
        withQuery(`${servicesBasePath}/${serviceId}/slots`, {
            page: params.page,
            per_page: params.per_page,
        }),
    );
}

export function createServiceSlot(
    serviceId: number,
    payload: ServiceSlotCreatePayload,
): Promise<ApiEnvelope<ServiceSlot>> {
    return httpRequest<ServiceSlot>(`${servicesBasePath}/${serviceId}/slots`, {
        method: 'POST',
        body: payload,
    });
}

export function updateServiceSlot(
    slotId: number,
    payload: ServiceSlotCreatePayload,
): Promise<ApiEnvelope<ServiceSlot>> {
    return httpRequest<ServiceSlot>(`/api/v1/slots/${slotId}`, {
        method: 'PUT',
        body: payload,
    });
}

export function deleteServiceSlot(
    slotId: number,
): Promise<ApiEnvelope<{ deleted: boolean }>> {
    return httpRequest<{ deleted: boolean }>(`/api/v1/slots/${slotId}`, {
        method: 'DELETE',
    });
}
