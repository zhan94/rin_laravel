import {
    buildBookingLocationGroups,
    resolveBookingLocationGroups,
    selectedBookingLocationLabel,
} from '@/modules/bookings/bookingLocations';
import type { BookingSupportLocation } from '@/modules/bookings/types';
import { describe, expect, it } from 'vitest';

function makeLocation(
    overrides: Partial<BookingSupportLocation> = {},
): BookingSupportLocation {
    return {
        id: 1,
        location_from: 'A',
        location_to: 'B',
        from_time: '08:00',
        to_time: '16:00',
        service_id: 1,
        is_global: true,
        label: 'A -> B',
        ...overrides,
    };
}

describe('bookingLocations', () => {
    it('returns selected location label or placeholder', () => {
        const locations = [makeLocation({ id: 2, label: 'Selected' })];

        expect(selectedBookingLocationLabel(locations, 2, 'Choose')).toBe(
            'Selected',
        );
        expect(selectedBookingLocationLabel(locations, 3, 'Choose')).toBe(
            'Choose',
        );
    });

    it('builds main and custom grouped options', () => {
        const main = makeLocation({ id: 1, is_global: true });
        const custom = makeLocation({ id: 2, is_global: false });

        expect(
            buildBookingLocationGroups({
                locations: [main, custom],
                locationGroups: {
                    main: [main],
                    custom: [custom],
                },
                labels: {
                    main: 'Main',
                    custom: 'Custom',
                    placeholder: 'Choose',
                },
            }),
        ).toEqual([
            {
                key: 'main',
                label: 'Main',
                options: [main],
            },
            {
                key: 'custom',
                label: 'Custom',
                options: [custom],
            },
        ]);
    });

    it('resolves API groups or falls back to global/custom grouping', () => {
        const main = makeLocation({ id: 1, is_global: true });
        const custom = makeLocation({ id: 2, is_global: false });

        expect(resolveBookingLocationGroups([main, custom])).toEqual({
            main: [main],
            custom: [custom],
        });

        expect(
            resolveBookingLocationGroups([main, custom], {
                main: [custom],
                custom: [main],
            }),
        ).toEqual({
            main: [custom],
            custom: [main],
        });
    });

    it('falls back to a single all group when API groups are empty', () => {
        const location = makeLocation();

        expect(
            buildBookingLocationGroups({
                locations: [location],
                locationGroups: {
                    main: [],
                    custom: [],
                },
                labels: {
                    main: 'Main',
                    custom: 'Custom',
                    placeholder: 'Choose',
                },
            }),
        ).toEqual([
            {
                key: 'all',
                label: '',
                options: [location],
            },
        ]);
    });
});
