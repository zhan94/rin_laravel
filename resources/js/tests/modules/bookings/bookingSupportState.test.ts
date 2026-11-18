import {
    reconcileBookingSupportSelections,
    selectedSlotIdForAvailability,
} from '@/modules/bookings/bookingSupportState';
import type {
    BookingSupportLocation,
    BookingSupportOption,
    BookingSupportSlot,
} from '@/modules/bookings/types';
import { describe, expect, it } from 'vitest';

function option(id: number): BookingSupportOption {
    return {
        id,
        title: `Option ${id}`,
        translations: { en: `Option ${id}`, de: '', es: '' },
    };
}

function slot(id: number): BookingSupportSlot {
    return {
        id,
        service_id: 1,
        name: `Slot ${id}`,
        date_start: null,
        date_end: null,
        max: 20,
    };
}

function location(id: number): BookingSupportLocation {
    return {
        id,
        location_from: 'A',
        location_to: 'B',
        from_time: '08:00',
        to_time: '16:00',
        service_id: 1,
        is_global: true,
        label: `Location ${id}`,
    };
}

describe('bookingSupportState', () => {
    it('keeps selected support ids when they still exist', () => {
        expect(
            reconcileBookingSupportSelections(
                {
                    cards: [option(3)],
                    equips: [option(4)],
                    slots: [slot(5)],
                    locations: [location(6)],
                },
                {
                    cardId: 3,
                    equipId: 4,
                    slotId: 5,
                    locationId: 6,
                },
            ),
        ).toEqual({
            selections: {
                cardId: 3,
                equipId: 4,
                slotId: 5,
                locationId: 6,
            },
            shouldCloseLocationPicker: false,
        });
    });

    it('resets invalid support ids and closes picker when no locations remain', () => {
        expect(
            reconcileBookingSupportSelections(
                {
                    cards: [option(3)],
                    equips: [],
                    slots: [slot(5)],
                    locations: [],
                },
                {
                    cardId: 99,
                    equipId: 4,
                    slotId: 99,
                    locationId: 6,
                },
            ),
        ).toEqual({
            selections: {
                cardId: 0,
                equipId: 0,
                slotId: 0,
                locationId: 0,
            },
            shouldCloseLocationPicker: true,
        });
    });

    it('returns only positive slot ids for availability requests', () => {
        expect(selectedSlotIdForAvailability(5)).toBe(5);
        expect(selectedSlotIdForAvailability(0)).toBeUndefined();
    });
});
