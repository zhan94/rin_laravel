import { HttpError, httpRequest, type ApiEnvelope } from '@/services/http';
import { withQuery } from '@/services/queryString';
import type {
    BookingCreatePayload,
    BookingListParams,
    BookingPaymentApplyResult,
    BookingRecord,
} from './types';

const bookingsBasePath = '/api/v1/bookings';

const pendingKeys = new Map<string, { payload: string; key: string }>();

async function submitMutation<T>(
    path: string,
    payload: object,
): Promise<ApiEnvelope<T>> {
    const serialized = JSON.stringify(payload);
    const pending = pendingKeys.get(path);
    const key =
        pending?.payload === serialized ? pending.key : crypto.randomUUID();
    pendingKeys.set(path, { payload: serialized, key });

    try {
        const result = await httpRequest<T>(path, {
            method: 'POST',
            body: { ...payload, request_key: key },
        });
        if (pendingKeys.get(path)?.key === key) pendingKeys.delete(path);

        return result;
    } catch (error) {
        if (
            error instanceof HttpError &&
            error.status >= 400 &&
            error.status < 500 &&
            pendingKeys.get(path)?.key === key
        ) {
            pendingKeys.delete(path);
        }

        throw error;
    }
}

export function listBookings(
    params: BookingListParams = {},
): Promise<ApiEnvelope<BookingRecord[]>> {
    return httpRequest<BookingRecord[]>(
        withQuery(bookingsBasePath, {
            q: params.q,
            page: params.page,
            per_page: params.per_page,
            service_id: params.service_id || undefined,
            service_type: params.service_type || undefined,
            customer_id: params.customer_id || undefined,
            active: params.active,
            date: params.date,
        }),
    );
}

export function createBooking(
    payload: BookingCreatePayload,
): Promise<ApiEnvelope<BookingRecord>> {
    return submitMutation<BookingRecord>(bookingsBasePath, payload);
}

export function updateBooking(
    bookingId: number,
    payload: BookingCreatePayload,
): Promise<ApiEnvelope<BookingRecord>> {
    return httpRequest<BookingRecord>(`${bookingsBasePath}/${bookingId}`, {
        method: 'PUT',
        body: payload,
    });
}

export function deactivateBooking(
    bookingId: number,
): Promise<ApiEnvelope<BookingRecord>> {
    return httpRequest<BookingRecord>(`${bookingsBasePath}/${bookingId}`, {
        method: 'DELETE',
    });
}

export function applyBookingPayment(
    bookingId: number,
    amount: number,
    mode: 'increment' | 'set' = 'increment',
    expectedPaidEur?: number,
): Promise<ApiEnvelope<BookingPaymentApplyResult>> {
    return submitMutation<BookingPaymentApplyResult>(
        `${bookingsBasePath}/${bookingId}/payments/apply`,
        { amount, mode, expected_paid_eur: expectedPaidEur },
    );
}
