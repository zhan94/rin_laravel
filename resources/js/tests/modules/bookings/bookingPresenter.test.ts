import type { BonusRecord } from '@/modules/bonus/types';
import {
    buildActiveBookingServiceOptions,
    buildBookingHeroCopy,
    isCardServiceBooking,
    isEquipServiceBooking,
    isServiceBookingType,
    isSkiServiceBooking,
} from '@/modules/bookings/bookingPresenter';
import type { ServiceDefinition } from '@/modules/services/types';
import { describe, expect, it } from 'vitest';

function service(id: number, title: string): ServiceDefinition {
    return {
        id,
        title,
        translations: { en: title, de: '', es: '' },
        title_key: null,
        max: 20,
        active: true,
        slots_count: 0,
    };
}

function bonus(id: number, title: string): BonusRecord {
    return {
        id,
        title,
        translations: { en: title, de: '', es: '' },
        title_key: null,
        location_from: '',
        location_to: '',
        date_from: null,
        date_to: null,
        description: null,
        price_bgn: null,
        price_eur: null,
        prices: [],
        prices_count: 0,
    };
}

describe('bookingPresenter', () => {
    it('classifies service booking types', () => {
        expect(isServiceBookingType(1)).toBe(true);
        expect(isServiceBookingType(2)).toBe(false);
        expect(isSkiServiceBooking(1, 1)).toBe(true);
        expect(isSkiServiceBooking(1, 11)).toBe(true);
        expect(isSkiServiceBooking(2, 1)).toBe(false);
        expect(isEquipServiceBooking(1, 1)).toBe(true);
        expect(isEquipServiceBooking(1, 2)).toBe(false);
        expect(isCardServiceBooking(1, 1)).toBe(true);
    });

    it('builds service or bonus options based on service type', () => {
        const services = [service(1, 'Ski school')];
        const bonuses = [bonus(8, 'Camp')];

        expect(buildActiveBookingServiceOptions(1, services, bonuses)).toEqual([
            { id: 1, title: 'Ski school' },
        ]);
        expect(buildActiveBookingServiceOptions(2, services, bonuses)).toEqual([
            { id: 8, title: 'Camp' },
        ]);
    });

    it('selects hero copy for the active booking step', () => {
        const labels = {
            stepOne: {
                eyebrow: '',
                title: 'Booking',
                subtitle: 'Choose customer.',
            },
            stepTwo: {
                eyebrow: 'Step 2',
                title: 'Booking details',
                subtitle: 'Fill details.',
            },
        };

        expect(buildBookingHeroCopy(1, labels)).toEqual(labels.stepOne);
        expect(buildBookingHeroCopy(2, labels)).toEqual(labels.stepTwo);
    });
});
