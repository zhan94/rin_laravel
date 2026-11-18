export interface PaginationMeta {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}

interface PaginationFallback {
    total?: number;
    perPage: number;
    currentPage?: number;
    lastPage?: number;
}

function finiteNumber(value: unknown, fallback: number): number {
    return typeof value === 'number' && Number.isFinite(value)
        ? value
        : fallback;
}

function isRecord(value: unknown): value is Record<string, unknown> {
    return typeof value === 'object' && value !== null;
}

export function defaultPaginationMeta({
    total = 0,
    perPage,
    currentPage = 1,
    lastPage = 1,
}: PaginationFallback): PaginationMeta {
    return {
        total,
        per_page: perPage,
        current_page: currentPage,
        last_page: lastPage,
    };
}

export function paginationFromMeta(
    metaPagination: unknown,
    fallback: PaginationFallback,
): PaginationMeta {
    const defaults = defaultPaginationMeta(fallback);

    if (!isRecord(metaPagination)) {
        return defaults;
    }

    return {
        total: finiteNumber(metaPagination.total, defaults.total),
        per_page: finiteNumber(metaPagination.per_page, defaults.per_page),
        current_page: finiteNumber(
            metaPagination.current_page,
            defaults.current_page,
        ),
        last_page: finiteNumber(metaPagination.last_page, defaults.last_page),
    };
}
