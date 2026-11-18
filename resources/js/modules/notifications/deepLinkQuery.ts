export function readFirstDeepLinkParam(keys: string[]): string | null {
    if (typeof window === 'undefined') {
        return null;
    }

    const params = new URLSearchParams(window.location.search);

    for (const key of keys) {
        const value = params.get(key);
        if (value === null) {
            continue;
        }

        const normalized = value.trim();
        if (normalized !== '') {
            return normalized;
        }
    }

    return null;
}

export function readPositiveIntDeepLinkParam(keys: string[]): number | null {
    const value = readFirstDeepLinkParam(keys);

    if (value === null) {
        return null;
    }

    const parsed = Number.parseInt(value, 10);

    return Number.isNaN(parsed) || parsed <= 0 ? null : parsed;
}
