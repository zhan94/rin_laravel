import type { BookingFormState } from '@/modules/bookings/bookingForm';
import type { BookingCreatePayload } from '@/modules/bookings/types';

export type BookingPayloadForm = BookingFormState;

export interface BookingPayloadSource {
    isNewCustomer: boolean;
    isServiceBooking: boolean;
    form: BookingPayloadForm;
}

export function buildBookingCreatePayload(
    source: BookingPayloadSource,
    dates: string[],
): BookingCreatePayload {
    const { form, isNewCustomer, isServiceBooking } = source;

    return {
        customer_id: isNewCustomer ? null : form.customer_id,
        firstname: form.firstname,
        surname: form.surname || null,
        lastname: form.lastname,
        date_birth: form.date_birth || null,
        parent_name: form.parent_name || null,
        parent_phone: form.parent_phone || null,
        email: form.email || null,
        service_type: form.service_type,
        service_id: form.service_id,
        slot_id: isServiceBooking && form.slot_id > 0 ? form.slot_id : null,
        location_id: isServiceBooking ? form.location_id : null,
        dates: isServiceBooking ? dates : [],
        sum_paid: form.sum_paid,
        paid: form.paid,
        approved: form.approved,
        note: form.note || null,
        skill_id:
            isServiceBooking && form.skill_id !== null && form.skill_id > 0
                ? form.skill_id
                : null,
        equip_id: isServiceBooking && form.equip_id > 0 ? form.equip_id : null,
        card_id: isServiceBooking && form.card_id > 0 ? form.card_id : null,
        ski_shoe_size: isServiceBooking ? form.ski_shoe_size : null,
        ski_height: isServiceBooking ? form.ski_height : null,
        equip_enabled: isServiceBooking && form.equip_enabled,
        lunch: false,
    };
}
