import {
    buildBookingCreatePayload,
    type BookingPayloadForm,
} from '@/modules/bookings/bookingPayload';
import { describe, expect, it } from 'vitest';

function makeForm(
    overrides: Partial<BookingPayloadForm> = {},
): BookingPayloadForm {
    return {
        customer_id: 12,
        service_type: 1,
        firstname: 'Alex',
        surname: 'Middle',
        lastname: 'Snow',
        date_birth: '2018-02-01',
        parent_name: 'Parent Snow',
        parent_phone: '0888000000',
        email: 'parent@example.test',
        service_id: 1,
        slot_id: 44,
        location_id: 7,
        datesCsv: '',
        sum_paid: 120,
        paid: 40,
        note: 'Bring gloves',
        skill_id: 3,
        equip_id: 5,
        card_id: 6,
        ski_shoe_size: 32,
        ski_height: 120,
        equip_enabled: true,
        approved: true,
        ...overrides,
    };
}

describe('bookingPayload', () => {
    it('builds a service booking payload with dates and ski options', () => {
        expect(
            buildBookingCreatePayload(
                {
                    isNewCustomer: false,
                    isServiceBooking: true,
                    form: makeForm(),
                },
                ['2026-03-01', '2026-03-02'],
            ),
        ).toEqual({
            customer_id: 12,
            firstname: 'Alex',
            surname: 'Middle',
            lastname: 'Snow',
            date_birth: '2018-02-01',
            parent_name: 'Parent Snow',
            parent_phone: '0888000000',
            email: 'parent@example.test',
            service_type: 1,
            service_id: 1,
            slot_id: 44,
            location_id: 7,
            dates: ['2026-03-01', '2026-03-02'],
            sum_paid: 120,
            paid: 40,
            approved: true,
            note: 'Bring gloves',
            skill_id: 3,
            equip_id: 5,
            card_id: 6,
            ski_shoe_size: 32,
            ski_height: 120,
            equip_enabled: true,
            lunch: false,
        });
    });

    it('nulls optional service fields when they are empty or disabled', () => {
        const payload = buildBookingCreatePayload(
            {
                isNewCustomer: true,
                isServiceBooking: true,
                form: makeForm({
                    customer_id: 99,
                    surname: '',
                    date_birth: '',
                    parent_name: '',
                    parent_phone: '',
                    email: '',
                    note: '',
                    slot_id: 0,
                    skill_id: 0,
                    equip_id: 0,
                    card_id: 0,
                    equip_enabled: false,
                }),
            },
            ['2026-03-01'],
        );

        expect(payload.customer_id).toBeNull();
        expect(payload.surname).toBeNull();
        expect(payload.date_birth).toBeNull();
        expect(payload.parent_name).toBeNull();
        expect(payload.parent_phone).toBeNull();
        expect(payload.email).toBeNull();
        expect(payload.note).toBeNull();
        expect(payload.slot_id).toBeNull();
        expect(payload.skill_id).toBeNull();
        expect(payload.equip_id).toBeNull();
        expect(payload.card_id).toBeNull();
        expect(payload.equip_enabled).toBe(false);
    });

    it('builds a bonus booking payload without service-only data', () => {
        const payload = buildBookingCreatePayload(
            {
                isNewCustomer: false,
                isServiceBooking: false,
                form: makeForm({
                    service_type: 2,
                    service_id: 9,
                }),
            },
            ['2026-03-01'],
        );

        expect(payload).toMatchObject({
            customer_id: 12,
            service_type: 2,
            service_id: 9,
            slot_id: null,
            location_id: null,
            dates: [],
            skill_id: null,
            equip_id: null,
            card_id: null,
            ski_shoe_size: null,
            ski_height: null,
            equip_enabled: false,
            lunch: false,
        });
    });
});
