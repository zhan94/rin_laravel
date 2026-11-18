type ResolutionInput = {
    linkPath: string;
    entityType?: string;
    entityId?: number | null;
};

type EntityRoute = {
    path: string;
    param: string;
};

const knownCrmPaths = new Set([
    '/crm/bookings',
    '/crm/bonus',
    '/crm/cards',
    '/crm/customers',
    '/crm/equips',
    '/crm/issues',
    '/crm/locations',
    '/crm/logs',
    '/crm/reports',
    '/crm/seasons',
    '/crm/services',
    '/crm/users',
]);

const entityRouteMap: Record<string, EntityRoute> = {
    issue: {
        path: '/crm/issues',
        param: 'issue',
    },
    customer: {
        path: '/crm/customers',
        param: 'customer',
    },
    booking: {
        path: '/crm/bookings',
        param: 'booking',
    },
    service: {
        path: '/crm/services',
        param: 'service',
    },
    service_slot: {
        path: '/crm/services',
        param: 'service',
    },
    bonus: {
        path: '/crm/bonus',
        param: 'bonus',
    },
    location: {
        path: '/crm/locations',
        param: 'location',
    },
    card: {
        path: '/crm/cards',
        param: 'card',
    },
    equip: {
        path: '/crm/equips',
        param: 'equip',
    },
    user: {
        path: '/crm/users',
        param: 'user',
    },
};

function normalizeLegacyPath(path: string): string {
    const clean = path.trim();

    if (clean === '') {
        return '/dashboard';
    }

    const issueMatch = clean.match(/^\/issues\/view\/(\d+)$/);
    if (issueMatch) {
        return `/crm/issues?issue=${issueMatch[1]}`;
    }

    if (clean.startsWith('/issues')) {
        return '/crm/issues';
    }
    if (clean.startsWith('/customers')) {
        return '/crm/customers';
    }
    if (clean.startsWith('/users')) {
        return '/crm/users';
    }
    if (clean.startsWith('/services')) {
        return '/crm/services';
    }
    if (clean.startsWith('/bonus')) {
        return '/crm/bonus';
    }
    if (clean.startsWith('/booking')) {
        return '/crm/bookings';
    }
    if (clean.startsWith('/locations')) {
        return '/crm/locations';
    }
    if (clean.startsWith('/cards')) {
        return '/crm/cards';
    }
    if (clean.startsWith('/equips')) {
        return '/crm/equips';
    }
    if (clean.startsWith('/reports')) {
        return '/crm/reports';
    }
    if (clean.startsWith('/logs')) {
        return '/crm/logs';
    }
    if (clean.startsWith('/seasons')) {
        return '/crm/seasons';
    }

    if (clean.startsWith('/crm/')) {
        const noQuery = clean.split('?')[0];
        if (knownCrmPaths.has(noQuery)) {
            return clean;
        }

        return '/dashboard';
    }

    return clean.startsWith('/') ? clean : '/dashboard';
}

function withEntityQuery(
    path: string,
    entityType: string,
    entityId: number | null,
): string {
    const route = entityRouteMap[entityType];
    if (route === undefined || (entityId ?? 0) <= 0) {
        return path;
    }

    const [basePath, rawQuery = ''] = path.split('?');
    if (basePath !== route.path) {
        return path;
    }

    const query = new URLSearchParams(rawQuery);
    if (!query.has(route.param)) {
        query.set(route.param, String(entityId));
    }

    const normalizedQuery = query.toString();

    return normalizedQuery === '' ? basePath : `${basePath}?${normalizedQuery}`;
}

function fallbackFromEntity(
    entityType: string,
    entityId: number | null,
): string {
    const route = entityRouteMap[entityType];
    if (route !== undefined) {
        return withEntityQuery(route.path, entityType, entityId);
    }

    return '/dashboard';
}

export function resolveNotificationLink(input: ResolutionInput): string {
    const normalized = normalizeLegacyPath(input.linkPath);
    if (normalized !== '/dashboard' || input.linkPath.trim() !== '') {
        return withEntityQuery(
            normalized,
            input.entityType ?? '',
            input.entityId ?? null,
        );
    }

    return fallbackFromEntity(input.entityType ?? '', input.entityId ?? null);
}
