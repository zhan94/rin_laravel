type Translate = (key: string, params?: Record<string, unknown>) => string;

export interface PasskeyPostResponse {
    ok: boolean;
    status: number;
    json: Record<string, unknown>;
}

const CSRF_COOKIE_NAME = 'XSRF-TOKEN';
let csrfCookieRequest: Promise<void> | null = null;

export function detectPasskeySupport(
    t: Translate,
    capability: 'create' | 'get',
    supportedMessage: string,
): { supported: boolean; message: string } {
    if (typeof window === 'undefined') {
        return {
            supported: false,
            message: t('auth.passkey.unsupported_browser'),
        };
    }

    if (isIpv4Host(window.location.hostname)) {
        return {
            supported: false,
            message: t('auth.passkey.unsupported_ip'),
        };
    }

    if (!window.isSecureContext) {
        return {
            supported: false,
            message: secureContextMessage(t),
        };
    }

    const credentialMethod = window.navigator?.credentials?.[capability];
    if (!window.PublicKeyCredential || typeof credentialMethod !== 'function') {
        return {
            supported: false,
            message: t('auth.passkey.unsupported_browser'),
        };
    }

    return {
        supported: true,
        message: supportedMessage,
    };
}

export function resolvePasskeyErrorMessage(
    t: Translate,
    error: unknown,
    fallback: string,
    cancelledMessage: string,
): string {
    if (
        error instanceof DOMException &&
        (error.name === 'NotAllowedError' || error.name === 'AbortError')
    ) {
        return cancelledMessage;
    }

    if (error instanceof DOMException && error.name === 'SecurityError') {
        return typeof window !== 'undefined' && window.isSecureContext
            ? domainMismatchMessage(t)
            : secureContextMessage(t);
    }

    if (error instanceof Error && error.message.trim() !== '') {
        if (/invalid domain|rp id|relying party/i.test(error.message)) {
            return domainMismatchMessage(t);
        }

        if (/secure context|insecure/i.test(error.message)) {
            return secureContextMessage(t);
        }

        if (/security/i.test(error.message)) {
            return typeof window !== 'undefined' && window.isSecureContext
                ? domainMismatchMessage(t)
                : secureContextMessage(t);
        }

        return error.message;
    }

    return fallback;
}

export async function postPasskeyForm(
    t: Translate,
    url: string,
    payload: Record<string, string>,
    allowRetry = true,
): Promise<PasskeyPostResponse> {
    const body = new URLSearchParams();

    for (const [key, value] of Object.entries(payload)) {
        body.append(key, value);
    }

    await ensureCsrfCookie();

    const token = csrfToken();
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            ...(token !== '' ? { 'X-XSRF-TOKEN': token } : {}),
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: body.toString(),
    });

    if (allowRetry && response.status === 419) {
        await ensureCsrfCookie(true);

        return postPasskeyForm(t, url, payload, false);
    }

    const redirectedToLogin =
        response.redirected && /\/login(?:\?|$)/.test(response.url);
    let json: Record<string, unknown> = {};

    try {
        json = (await response.json()) as Record<string, unknown>;
    } catch {
        json = redirectedToLogin ? { error: t('auth.session_expired') } : {};
    }

    return {
        ok: response.ok && !redirectedToLogin && json.ok !== false,
        status: response.status,
        json,
    };
}

export function normalizeRegistrationOptions(
    options: Record<string, unknown>,
): PublicKeyCredentialCreationOptions {
    const normalized: Record<string, unknown> = {
        ...options,
    };

    normalized.challenge = fromBase64Url(String(options.challenge ?? ''));

    const userOptions = (options.user ?? {}) as Record<string, unknown>;
    normalized.user = {
        ...userOptions,
        id: fromBase64Url(String(userOptions.id ?? '')),
    };

    if (Array.isArray(options.excludeCredentials)) {
        normalized.excludeCredentials = options.excludeCredentials.map(
            (item) => {
                const row = item as Record<string, unknown>;

                return {
                    ...row,
                    id: fromBase64Url(String(row.id ?? '')),
                };
            },
        );
    }

    return normalized as unknown as PublicKeyCredentialCreationOptions;
}

export function normalizeRequestOptions(
    options: Record<string, unknown>,
): PublicKeyCredentialRequestOptions {
    const normalized: Record<string, unknown> = {
        ...options,
    };

    normalized.challenge = fromBase64Url(String(options.challenge ?? ''));

    if (Array.isArray(options.allowCredentials)) {
        normalized.allowCredentials = options.allowCredentials.map((item) => {
            const row = item as Record<string, unknown>;

            return {
                ...row,
                id: fromBase64Url(String(row.id ?? '')),
            };
        });
    }

    return normalized as unknown as PublicKeyCredentialRequestOptions;
}

export function serializeAssertion(
    credential: PublicKeyCredential,
): Record<string, string> {
    const response = credential.response as AuthenticatorAssertionResponse;

    return {
        credential_id: toBase64Url(credential.rawId),
        client_data_json: toBase64Url(response.clientDataJSON),
        authenticator_data: toBase64Url(response.authenticatorData),
        signature: toBase64Url(response.signature),
        user_handle: response.userHandle
            ? toBase64Url(response.userHandle)
            : '',
    };
}

export function serializeAttestation(
    credential: PublicKeyCredential,
    deviceName: string,
): Record<string, string> {
    const response = credential.response as AuthenticatorAttestationResponse;
    const payload: Record<string, string> = {
        credential_id: toBase64Url(credential.rawId),
        client_data_json: toBase64Url(response.clientDataJSON),
        attestation_object: toBase64Url(response.attestationObject),
        device_name: deviceName,
    };

    if (typeof response.getTransports === 'function') {
        const transports = response.getTransports();
        if (transports.length > 0) {
            payload.transports = transports.join(',');
        }
    }

    return payload;
}

function secureContextMessage(t: Translate): string {
    if (typeof window === 'undefined') {
        return t('auth.passkey.secure_general', { host: 'localhost' });
    }

    const hostname = window.location.hostname;
    const isLoopback =
        hostname === 'localhost' ||
        hostname === '127.0.0.1' ||
        hostname === '[::1]';

    if (isIpv4Host(hostname)) {
        return t('auth.passkey.unsupported_ip');
    }

    if (isLoopback) {
        return t('auth.passkey.secure_localhost');
    }

    return t('auth.passkey.secure_general', { host: window.location.host });
}

function domainMismatchMessage(t: Translate): string {
    if (typeof window === 'undefined') {
        return t('auth.passkey.mismatch_other_domain');
    }

    if (isIpv4Host(window.location.hostname)) {
        return t('auth.passkey.mismatch_ip');
    }

    return t('auth.passkey.mismatch_host', { host: window.location.host });
}

function isIpv4Host(hostname: string): boolean {
    return /^\d{1,3}(?:\.\d{1,3}){3}$/.test(hostname);
}

function csrfToken(): string {
    const cookieToken = readCookie(CSRF_COOKIE_NAME);
    if (cookieToken !== null && cookieToken !== '') {
        return cookieToken;
    }

    const metaToken =
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? '';

    return metaToken.trim();
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
                    throw new Error(
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

function toBase64Url(buffer: ArrayBuffer): string {
    const bytes = new Uint8Array(buffer);
    let binary = '';

    for (const byte of bytes) {
        binary += String.fromCharCode(byte);
    }

    return btoa(binary)
        .replace(/\+/g, '-')
        .replace(/\//g, '_')
        .replace(/=+$/g, '');
}

function fromBase64Url(value: string): ArrayBuffer {
    const padded = value + '==='.slice((value.length + 3) % 4);
    const base64 = padded.replace(/-/g, '+').replace(/_/g, '/');
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);

    for (let i = 0; i < binary.length; i += 1) {
        bytes[i] = binary.charCodeAt(i);
    }

    return bytes.buffer;
}
