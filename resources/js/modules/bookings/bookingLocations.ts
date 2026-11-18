import type { BookingSupportLocation } from '@/modules/bookings/types';

export interface BookingLocationGroup {
    key: string;
    label: string;
    options: BookingSupportLocation[];
}

export interface BookingLocationLabels {
    main: string;
    custom: string;
    placeholder: string;
}

export interface BookingLocationGroupsSource {
    locations: BookingSupportLocation[];
    locationGroups: {
        main: BookingSupportLocation[];
        custom: BookingSupportLocation[];
    };
    labels: BookingLocationLabels;
}

export function resolveBookingLocationGroups(
    locations: BookingSupportLocation[],
    apiGroups?: {
        main?: BookingSupportLocation[];
        custom?: BookingSupportLocation[];
    } | null,
): {
    main: BookingSupportLocation[];
    custom: BookingSupportLocation[];
} {
    return {
        main:
            apiGroups?.main ??
            locations.filter((location) => location.is_global),
        custom:
            apiGroups?.custom ??
            locations.filter((location) => !location.is_global),
    };
}

export function selectedBookingLocationLabel(
    locations: BookingSupportLocation[],
    selectedLocationId: number,
    placeholder: string,
): string {
    const location = locations.find((item) => item.id === selectedLocationId);

    return location?.label ?? placeholder;
}

export function buildBookingLocationGroups(
    source: BookingLocationGroupsSource,
): BookingLocationGroup[] {
    const groups: BookingLocationGroup[] = [];

    if (source.locationGroups.main.length > 0) {
        groups.push({
            key: 'main',
            label: source.labels.main,
            options: source.locationGroups.main,
        });
    }

    if (source.locationGroups.custom.length > 0) {
        groups.push({
            key: 'custom',
            label: source.labels.custom,
            options: source.locationGroups.custom,
        });
    }

    if (groups.length === 0 && source.locations.length > 0) {
        groups.push({
            key: 'all',
            label: '',
            options: source.locations,
        });
    }

    return groups;
}
