import type {
    BookingSupportCustomer,
    BookingSupportLocation,
    BookingSupportOption,
    BookingSupportPrefillBooking,
} from '@/modules/bookings/types';

export interface BookingFormState {
    customer_id: number | null;
    service_type: 1 | 2;
    firstname: string;
    surname: string;
    lastname: string;
    date_birth: string;
    parent_name: string;
    parent_phone: string;
    email: string;
    service_id: number;
    slot_id: number;
    location_id: number;
    datesCsv: string;
    sum_paid: number | null;
    paid: number | null;
    note: string;
    skill_id: number | null;
    equip_id: number;
    card_id: number;
    ski_shoe_size: number | null;
    ski_height: number | null;
    equip_enabled: boolean;
    approved: boolean;
}

export function createDefaultBookingForm(): BookingFormState {
    return {
        customer_id: null,
        service_type: 1,
        firstname: '',
        surname: '',
        lastname: '',
        date_birth: '',
        parent_name: '',
        parent_phone: '',
        email: '',
        service_id: 0,
        slot_id: 0,
        location_id: 0,
        datesCsv: '',
        sum_paid: null,
        paid: null,
        note: '',
        skill_id: null,
        equip_id: 0,
        card_id: 0,
        ski_shoe_size: null,
        ski_height: null,
        equip_enabled: false,
        approved: false,
    };
}

export function resetBookingCreateFields(form: BookingFormState): void {
    const serviceId = form.service_id;

    Object.assign(form, createDefaultBookingForm(), {
        service_id: serviceId,
    });
}

export function resetBookingServiceSelection(form: BookingFormState): void {
    form.service_id = 0;
    form.slot_id = 0;
    form.location_id = 0;
    form.datesCsv = '';
    form.skill_id = null;
    form.equip_id = 0;
    form.card_id = 0;
    form.ski_shoe_size = null;
    form.ski_height = null;
    form.equip_enabled = false;
}

export function clearSelectedCustomerFields(form: BookingFormState): void {
    form.customer_id = null;
    form.date_birth = '';
    form.parent_name = '';
    form.parent_phone = '';
    form.email = '';
}

export function clearCustomerNameFields(form: BookingFormState): void {
    form.firstname = '';
    form.surname = '';
    form.lastname = '';
}

export function trimCustomerNameFields(form: BookingFormState): void {
    form.firstname = form.firstname.trim();
    form.surname = form.surname.trim();
    form.lastname = form.lastname.trim();
}

export function applyCustomerToBookingForm(
    form: BookingFormState,
    customer: BookingSupportCustomer,
): void {
    form.firstname = customer.firstname;
    form.surname = customer.surname ?? '';
    form.lastname = customer.lastname;
    form.date_birth = customer.date_birth ?? '';
    form.parent_name = customer.parent_name ?? '';
    form.parent_phone = customer.parent_phone ?? '';
    form.email = customer.email ?? '';
}

export function applyCustomerPrefillToBookingForm(
    form: BookingFormState,
    booking: BookingSupportPrefillBooking,
    locations: readonly Pick<BookingSupportLocation, 'id'>[],
): void {
    if (
        form.location_id <= 0 &&
        locations.some(({ id }) => id === booking.location)
    ) {
        form.location_id = booking.location;
    }

    form.skill_id = booking.skill_id;
    form.card_id = booking.card_id ?? 0;
    form.equip_id = booking.equip_id ?? 0;
    form.equip_enabled = booking.equip_enabled;

    const shoeSize = Number(booking.ski_form.shoe_size);
    const height = Number(booking.ski_form.height);

    form.ski_shoe_size =
        Number.isFinite(shoeSize) && shoeSize > 0 ? shoeSize : null;
    form.ski_height = Number.isFinite(height) && height > 0 ? height : null;

    form.sum_paid = booking.sum_paid;

    if (form.note.trim() === '' && booking.note !== null) {
        form.note = booking.note;
    }
}

export function applyCardToggleToBookingForm(
    form: BookingFormState,
    checked: boolean,
    cards: BookingSupportOption[],
): void {
    if (!checked) {
        form.card_id = 0;

        return;
    }

    if (form.card_id <= 0 && cards.length > 0) {
        form.card_id = cards[0].id;
    }
}

export function applyEquipToggleToBookingForm(
    form: BookingFormState,
    checked: boolean,
    equips: BookingSupportOption[],
): void {
    form.equip_enabled = checked;

    if (!checked) {
        form.equip_id = 0;

        return;
    }

    if (form.equip_id <= 0 && equips.length > 0) {
        form.equip_id = equips[0].id;
    }
}
