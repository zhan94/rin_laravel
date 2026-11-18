import { HttpError, httpRequest } from '@/services/http';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

function createResponse<T>(payload: T, status = 200, ok = true): Response {
    return {
        ok,
        status,
        json: vi.fn().mockResolvedValue(payload),
    } as unknown as Response;
}

describe('httpRequest', () => {
    const fetchMock = vi.fn();

    beforeEach(() => {
        vi.stubGlobal('fetch', fetchMock);
        document.head.innerHTML =
            '<meta name="csrf-token" content="csrf-token-value">';
        document.cookie =
            'XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
    });

    afterEach(() => {
        fetchMock.mockReset();
        vi.unstubAllGlobals();
        document.cookie =
            'XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
    });

    it('returns a successful JSON envelope', async () => {
        fetchMock.mockResolvedValue(
            createResponse({
                data: { id: 42 },
                meta: {},
                errors: [],
            }),
        );

        const result = await httpRequest<{ id: number }>('/api/v1/test');

        expect(result.data.id).toBe(42);
        expect(fetchMock).toHaveBeenCalledWith(
            '/api/v1/test',
            expect.objectContaining({
                method: 'GET',
                credentials: 'same-origin',
                headers: expect.objectContaining({
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': 'csrf-token-value',
                }),
            }),
        );
    });

    it('initializes the csrf cookie before mutating requests', async () => {
        fetchMock
            .mockResolvedValueOnce(createResponse({}, 204, true))
            .mockResolvedValueOnce(
                createResponse({
                    data: { saved: true },
                    meta: {},
                    errors: [],
                }),
            );

        await httpRequest('/api/v1/test', {
            method: 'POST',
            body: { name: 'Kalina' },
        });

        expect(fetchMock).toHaveBeenNthCalledWith(
            1,
            '/sanctum/csrf-cookie',
            expect.objectContaining({
                method: 'GET',
                credentials: 'same-origin',
            }),
        );
        expect(fetchMock).toHaveBeenNthCalledWith(
            2,
            '/api/v1/test',
            expect.objectContaining({
                method: 'POST',
                body: JSON.stringify({ name: 'Kalina' }),
                headers: expect.objectContaining({
                    'X-XSRF-TOKEN': 'csrf-token-value',
                }),
            }),
        );
    });

    it('prefers the live xsrf cookie token when present', async () => {
        document.cookie = 'XSRF-TOKEN=cookie-token-value; path=/';
        fetchMock.mockResolvedValue(
            createResponse({
                data: { ok: true },
                meta: {},
                errors: [],
            }),
        );

        await httpRequest('/api/v1/test');

        expect(fetchMock).toHaveBeenCalledWith(
            '/api/v1/test',
            expect.objectContaining({
                headers: expect.objectContaining({
                    'X-XSRF-TOKEN': 'cookie-token-value',
                }),
            }),
        );
    });

    it('retries once after a 419 response', async () => {
        fetchMock
            .mockResolvedValueOnce(createResponse({}, 204, true))
            .mockResolvedValueOnce(createResponse({}, 419, false))
            .mockResolvedValueOnce(createResponse({}, 204, true))
            .mockResolvedValueOnce(
                createResponse({
                    data: { saved: true },
                    meta: {},
                    errors: [],
                }),
            );

        await httpRequest('/api/v1/test', {
            method: 'POST',
            body: { retry: true },
        });

        expect(fetchMock).toHaveBeenCalledTimes(4);
        expect(fetchMock).toHaveBeenNthCalledWith(
            4,
            '/api/v1/test',
            expect.objectContaining({
                method: 'POST',
            }),
        );
    });

    it('throws HttpError when response is not ok', async () => {
        fetchMock.mockResolvedValue(
            createResponse(
                {
                    data: {},
                    meta: {},
                    errors: [
                        {
                            code: 'unauthenticated',
                            message: 'Unauthenticated.',
                        },
                    ],
                },
                401,
                false,
            ),
        );

        try {
            await httpRequest('/api/v1/test');
            expect.unreachable('Expected request to throw HttpError');
        } catch (error: unknown) {
            expect(error).toBeInstanceOf(HttpError);
            expect(error).toMatchObject({
                status: 401,
                message: 'Unauthenticated.',
            });
        }
    });
});
