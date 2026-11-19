import {
    applyCardToggleToBookingForm,
    applyCustomerPrefillToBookingForm,
    applyCustomerToBookingForm,
    applyEquipToggleToBookingForm,
    clearCustomerNameFields,
    clearSelectedCustomerFields,
    createDefaultBookingForm,
    resetBookingCreateFields,
    resetBookingServiceSelection,
    trimCustomerNameFields,
} from '@/modules/bookings/bookingForm';
import type {
    BookingSupportCustomer,
    BookingSupportOption,
    BookingSupportPrefillBooking,
} from '@/modules/bookings/types';
import { describe, expect, it } from 'vitest';

function option(id: number): BookingSupportOption {
    return {
        id,
        title: `Option ${id}`,
        translations: { en: `Option ${id}`, de: '', es: '' },
    };
}

describe('bookingForm', () => {
    it('creates default form state and resets create fields without clearing selected service', () => {
        const form = createDefaultBookingForm();

        Object.assign(form, {
            customer_id: 15,
            firstname: 'Alex',
            service_id: 9,
            location_id: 4,
            datesCsv: '2026-03-01',
            paid: 20,
            approved: true,
        });

        resetBookingCreateFields(form);

        expect(form).toMatchObject({
            customer_id: null,
            firstname: '',
            service_type: 1,
            service_id: 9,
            location_id: 0,
            datesCsv: '',
            paid: null,
            approved: false,
        });
    });

    it('resets service selection fields without clearing customer and payment data', () => {
        const form = createDefaultBookingForm();

        Object.assign(form, {
            customer_id: 15,
            firstname: 'Alex',
            service_id: 9,
            slot_id: 4,
            location_id: 3,
            datesCsv: '2026-03-01',
            skill_id: 2,
            equip_id: 7,
            card_id: 8,
            ski_shoe_size: 31,
            ski_height: 118,
            equip_enabled: true,
            paid: 20,
            note: 'Keep note',
        });

        resetBookingServiceSelection(form);

        expect(form).toMatchObject({
            customer_id: 15,
            firstname: 'Alex',
            service_id: 0,
            slot_id: 0,
            location_id: 0,
            datesCsv: '',
            skill_id: null,
            equip_id: 0,
            card_id: 0,
            ski_shoe_size: null,
            ski_height: null,
            equip_enabled: false,
            paid: 20,
            note: 'Keep note',
        });
    });

    it('applies, trims and clears customer fields', () => {
        const form = createDefaultBookingForm();
        const customer: BookingSupportCustomer = {
            id: 5,
            firstname: ' Alex ',
            surname: null,
            lastname: ' Snow ',
            full_name: 'Alex Snow',
            date_birth: null,
            age: null,
            parent_name: 'Parent',
            parent_phone: null,
            email: 'parent@example.test',
            active: true,
        };

        applyCustomerToBookingForm(form, customer);
        trimCustomerNameFields(form);

        expect(form.firstname).toBe('Alex');
        expect(form.surname).toBe('');
        expect(form.lastname).toBe('Snow');
        expect(form.parent_name).toBe('Parent');
        expect(form.email).toBe('parent@example.test');

        clearSelectedCustomerFields(form);
        expect(form.email).toBe('');
        expect(form.parent_name).toBe('');

        clearCustomerNameFields(form);
        expect(form.firstname).toBe('');
        expect(form.lastname).toBe('');
    });

    it('prefills service details without carrying over a prior payment or overwriting a note', () => {
        const form = createDefaultBookingForm();
        form.note = 'Manual note';

        const booking: BookingSupportPrefillBooking = {
            id: 3,
            location: 6,
            skill_id: 2,
            card_id: null,
            equip_id: 8,
            equip_enabled: true,
            approved: false,
            lunch: false,
            sum_paid: 195.583,
            paid: 97.7915,
            note: 'Existing booking note',
            ski_form: {
                shoe_size: '32',
                height: '120',
                skill_id: '2',
                equip_enabled: '1',
            },
            last_date: null,
            season_start: '2026-01-01',
            season_end: '2026-03-31',
        };

        applyCustomerPrefillToBookingForm(form, booking, [{ id: 6 }]);

        expect(form).toMatchObject({
            location_id: 6,
            skill_id: 2,
            card_id: 0,
            equip_id: 8,
            equip_enabled: true,
            ski_shoe_size: 32,
            ski_height: 120,
            sum_paid: 100,
            paid: null,
            note: 'Manual note',
        });

        form.location_id = 7;
        applyCustomerPrefillToBookingForm(form, booking, [
            { id: 6 },
            { id: 7 },
        ]);
        expect(form.location_id).toBe(7);

        form.location_id = 0;
        applyCustomerPrefillToBookingForm(form, booking, [{ id: 7 }]);
        expect(form.location_id).toBe(0);
    });

    it('applies card and equipment toggle changes', () => {
        const form = createDefaultBookingForm();

        applyCardToggleToBookingForm(form, true, [option(8)]);
        expect(form.card_id).toBe(8);

        applyCardToggleToBookingForm(form, false, [option(8)]);
        expect(form.card_id).toBe(0);

        applyEquipToggleToBookingForm(form, true, [option(11)]);
        expect(form.equip_enabled).toBe(true);
        expect(form.equip_id).toBe(11);

        applyEquipToggleToBookingForm(form, false, [option(11)]);
        expect(form.equip_enabled).toBe(false);
        expect(form.equip_id).toBe(0);
    });
});
