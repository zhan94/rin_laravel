import { httpRequest, type ApiEnvelope } from '@/services/http';
import { withQuery } from '@/services/queryString';
import type {
    Customer,
    CustomerCreatePayload,
    CustomerListParams,
    CustomerUnpaidRow,
} from './types';

const basePath = '/api/v1/customers';

export function listCustomers(
    params: CustomerListParams = {},
): Promise<ApiEnvelope<Customer[]>> {
    return httpRequest<Customer[]>(
        withQuery(basePath, {
            q: params.q,
            page: params.page,
            per_page: params.per_page,
        }),
    );
}

export function createCustomer(
    payload: CustomerCreatePayload,
): Promise<ApiEnvelope<Customer>> {
    return httpRequest<Customer>(basePath, {
        method: 'POST',
        body: payload,
    });
}

export function getCustomer(
    customerId: number,
): Promise<ApiEnvelope<Customer>> {
    return httpRequest<Customer>(`${basePath}/${customerId}`);
}

export function updateCustomer(
    customerId: number,
    payload: CustomerCreatePayload,
): Promise<ApiEnvelope<Customer>> {
    return httpRequest<Customer>(`${basePath}/${customerId}`, {
        method: 'PUT',
        body: payload,
    });
}

export function deleteCustomer(
    customerId: number,
): Promise<ApiEnvelope<{ deleted: boolean }>> {
    return httpRequest<{ deleted: boolean }>(`${basePath}/${customerId}`, {
        method: 'DELETE',
    });
}

export function listUnpaidCustomers(
    params: Pick<CustomerListParams, 'q'> = {},
): Promise<ApiEnvelope<CustomerUnpaidRow[]>> {
    return httpRequest<CustomerUnpaidRow[]>(
        withQuery(`${basePath}/unpaid`, { q: params.q }),
    );
}
