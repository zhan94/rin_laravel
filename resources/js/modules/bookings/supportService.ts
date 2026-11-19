import { httpRequest, type ApiEnvelope } from '@/services/http';
import { withQuery } from '@/services/queryString';
import type {
    BookingSupportAvailability,
    BookingSupportBonusContext,
    BookingSupportCustomer,
    BookingSupportCustomerPrefill,
    BookingSupportOption,
    BookingSupportSlot,
    BookingSupportSuggestion,
} from './types';

const basePath = '/api/v1/booking-support';

export function autocompleteCustomers(
    query: string,
): Promise<ApiEnvelope<BookingSupportSuggestion[]>> {
    return httpRequest<BookingSupportSuggestion[]>(
        withQuery(`${basePath}/customers/autocomplete`, {
            q: query,
        }),
    );
}

export function getCustomerById(
    customerId: number,
): Promise<ApiEnvelope<BookingSupportCustomer>> {
    return httpRequest<BookingSupportCustomer>(
        `${basePath}/customers/${customerId}`,
    );
}

export function getServiceOptions(serviceId: number): Promise<
    ApiEnvelope<{
        cards: BookingSupportOption[];
        equips: BookingSupportOption[];
    }>
> {
    return httpRequest<{
        cards: BookingSupportOption[];
        equips: BookingSupportOption[];
    }>(`${basePath}/services/${serviceId}/options`);
}

export function getServiceSlots(
    serviceId: number,
): Promise<ApiEnvelope<BookingSupportSlot[]>> {
    return httpRequest<BookingSupportSlot[]>(
        `${basePath}/services/${serviceId}/slots`,
    );
}

export function getServiceAvailability(
    serviceId: number,
    params: {
        customerId?: number;
        slotId?: number;
    } = {},
): Promise<ApiEnvelope<BookingSupportAvailability>> {
    return httpRequest<BookingSupportAvailability>(
        withQuery(`${basePath}/services/${serviceId}/availability`, {
            customer_id:
                params.customerId !== undefined && params.customerId > 0
                    ? params.customerId
                    : undefined,
            slot_id:
                params.slotId !== undefined && params.slotId > 0
                    ? params.slotId
                    : undefined,
        }),
    );
}

export function getBonusContext(
    bonusId: number,
): Promise<ApiEnvelope<BookingSupportBonusContext>> {
    return httpRequest<BookingSupportBonusContext>(
        `${basePath}/bonus/${bonusId}/context`,
    );
}

export function getCustomerServicePrefill(
    customerId: number,
    serviceId: number,
    selectedDate?: string,
): Promise<ApiEnvelope<BookingSupportCustomerPrefill>> {
    return httpRequest<BookingSupportCustomerPrefill>(
        withQuery(`${basePath}/prefill`, {
            customer_id: customerId,
            service_id: serviceId,
            selected_date: selectedDate,
        }),
    );
}
