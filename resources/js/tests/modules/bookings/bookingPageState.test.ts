import {
    createDefaultBookingPageState,
    createEmptyBookingBonusContext,
    resetBookingServiceSupport,
} from '@/modules/bookings/bookingPageState';
import { describe, expect, it } from 'vitest';

describe('bookingPageState', () => {
    it('creates default page state for the bookings screen', () => {
        const state = createDefaultBookingPageState();

        expect(state.rows).toEqual([]);
        expect(state.locationGroups).toEqual({
            main: [],
            custom: [],
        });
        expect(state.bonusContext).toEqual(createEmptyBookingBonusContext());
        expect(state.pagination).toEqual({
            total: 0,
            per_page: 25,
            current_page: 1,
            last_page: 1,
        });
        expect(state.form).toMatchObject({
            customer_id: null,
            service_type: 1,
            service_id: 0,
        });
    });

    it('creates a fresh bonus context object each time', () => {
        const first = createEmptyBookingBonusContext();
        const second = createEmptyBookingBonusContext();

        first.location = 'Changed';

        expect(second.location).toBe('');
    });

    it('resets loaded service support without clearing unrelated form data', () => {
        const state = createDefaultBookingPageState();
        const location = {
            id: 1,
            location_from: 'A',
            location_to: 'B',
            from_time: '08:00',
            to_time: '16:00',
            service_id: 1,
            is_global: true,
            label: 'A to B',
        };

        Object.assign(state, {
            locations: [location],
            locationGroups: {
                main: [location],
                custom: [],
            },
            slots: [
                {
                    id: 7,
                    service_id: 1,
                    name: 'Slot',
                    date_start: null,
                    date_end: null,
                    max: 20,
                },
            ],
            disabledDates: ['2026-03-01'],
            calendarCursor: '2026-03',
            cards: [
                {
                    id: 2,
                    title: 'Card',
                    translations: { en: 'Card', de: '', es: '' },
                },
            ],
            equips: [
                {
                    id: 3,
                    title: 'Equip',
                    translations: { en: 'Equip', de: '', es: '' },
                },
            ],
            locationPickerOpen: true,
        });
        state.form.firstname = 'Alex';

        resetBookingServiceSupport(state);

        expect(state).toMatchObject({
            locations: [],
            locationGroups: { main: [], custom: [] },
            slots: [],
            disabledDates: [],
            calendarCursor: '',
            cards: [],
            equips: [],
            locationPickerOpen: false,
        });
        expect(state.form.firstname).toBe('Alex');
    });
});
