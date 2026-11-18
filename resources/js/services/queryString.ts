export type QueryStringValue = string | number | boolean | null | undefined;
export type QueryStringParams = Record<string, QueryStringValue>;

export function buildQueryString(params: QueryStringParams = {}): string {
    const query = new URLSearchParams();

    for (const [key, value] of Object.entries(params)) {
        if (value === undefined || value === null || value === '') {
            continue;
        }

        query.set(key, String(value));
    }

    return query.toString();
}

export function withQuery(
    path: string,
    params: QueryStringParams = {},
): string {
    const query = buildQueryString(params);

    return query === '' ? path : `${path}?${query}`;
}
