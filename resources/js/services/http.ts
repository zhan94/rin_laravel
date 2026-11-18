export interface ApiError {
    code: string;
    message: string;
    details?: Record<string, unknown>;
}

export interface ApiEnvelope<TData> {
    data: TData;
    meta: Record<string, unknown>;
    errors: ApiError[];
}

export type JsonRequestBody = object;

export interface RequestOptions {
    method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
    body?: JsonRequestBody | FormData;
    signal?: AbortSignal;
}

const CSRF_COOKIE_NAME = 'XSRF-TOKEN';
const MUTATING_METHODS = new Set<RequestOptions['method']>([
    'POST',
    'PUT',
    'PATCH',
    'DELETE',
]);

let csrfCookieRequest: Promise<void> | null = null;

export class HttpError extends Error {
    public readonly status: number;
    public readonly errors: ApiError[];

    constructor(status: number, message: string, errors: ApiError[] = []) {
        super(message);
        this.name = 'HttpError';
        this.status = status;
        this.errors = errors;
    }
}

function readMetaCsrfToken(): string | null {
    return (
        document
            .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.getAttribute('content')
            ?.trim() ?? null
    );
}

function readCookie(name: string): string | null {
    const escapedName = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const match = document.cookie.match(
        new RegExp(`(?:^|; )${escapedName}=([^;]*)`),
    );

    if (match === null) {
        return null;
    }

    return decodeURIComponent(match[1]);
}

function readRequestCsrfToken(): string | null {
    return readCookie(CSRF_COOKIE_NAME) ?? readMetaCsrfToken();
}

async function ensureCsrfCookie(forceRefresh = false): Promise<void> {
    if (typeof window === 'undefined') {
        return;
    }

    if (!forceRefresh && readCookie(CSRF_COOKIE_NAME) !== null) {
        return;
    }

    if (csrfCookieRequest === null || forceRefresh) {
        csrfCookieRequest = fetch('/sanctum/csrf-cookie', {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => {
                if (!response.ok) {
                    throw new HttpError(
                        response.status,
                        `Failed to initialize CSRF cookie (${response.status}).`,
                    );
                }
            })
            .finally(() => {
                csrfCookieRequest = null;
            });
    }

    await csrfCookieRequest;
}

async function parseEnvelope<TData>(
    response: Response,
): Promise<ApiEnvelope<TData> | null> {
    try {
        return (await response.json()) as ApiEnvelope<TData>;
    } catch {
        return null;
    }
}

function serializeRequestBody(
    body: RequestOptions['body'],
): BodyInit | undefined {
    if (body === undefined) {
        return undefined;
    }

    if (body instanceof FormData) {
        return body;
    }

    return JSON.stringify(body);
}

async function performRequest<TData>(
    url: string,
    options: RequestOptions,
    allowRetry: boolean,
    csrfReady: boolean,
): Promise<ApiEnvelope<TData>> {
    const method = options.method ?? 'GET';

    if (!csrfReady && MUTATING_METHODS.has(method)) {
        await ensureCsrfCookie();
    }

    const isFormData = options.body instanceof FormData;
    const csrfToken = readRequestCsrfToken();
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(csrfToken ? { 'X-XSRF-TOKEN': csrfToken } : {}),
    };

    if (!isFormData) {
        headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers,
        body: serializeRequestBody(options.body),
        signal: options.signal,
    });

    if (allowRetry && response.status === 419 && MUTATING_METHODS.has(method)) {
        await ensureCsrfCookie(true);

        return performRequest<TData>(
            url,
            {
                ...options,
                method,
            },
            false,
            true,
        );
    }

    const payload = await parseEnvelope<TData>(response);

    if (!response.ok) {
        const message =
            payload?.errors?.[0]?.message ??
            `Request failed with status ${response.status}`;

        throw new HttpError(response.status, message, payload?.errors ?? []);
    }

    return (
        payload ?? {
            data: {} as TData,
            meta: {},
            errors: [],
        }
    );
}

export async function httpRequest<TData>(
    url: string,
    options: RequestOptions = {},
): Promise<ApiEnvelope<TData>> {
    return performRequest<TData>(url, options, true, false);
}
