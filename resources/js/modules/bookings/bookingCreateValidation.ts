import type { UiTranslationKey } from '@/composables/useUiI18n';

export type BookingCreateValidationCode =
    | 'booking_location_required'
    | 'booking_dates_required'
    | 'booking_skill_required';

export interface BookingCreateValidationInput {
    isServiceBooking: boolean;
    isSkiService: boolean;
    locationId: number;
    dates: string[];
    skillId: number | null;
}

export interface BookingCreateValidationFailure {
    code: BookingCreateValidationCode;
    messageKey: UiTranslationKey;
}

export interface BookingStepOneValidationInput {
    serviceType: number;
    isNewCustomer: boolean;
    customerId: number | null;
}

export interface BookingStepOneValidationFailure {
    messageKey: UiTranslationKey;
}

export function validateBookingStepOne(
    input: BookingStepOneValidationInput,
): BookingStepOneValidationFailure | null {
    if (input.serviceType !== 1 && input.serviceType !== 2) {
        return {
            messageKey: 'bookings.error_service_type',
        };
    }

    if (!input.isNewCustomer && (input.customerId ?? 0) <= 0) {
        return {
            messageKey: 'bookings.error_existing_customer',
        };
    }

    return null;
}

export function validateBookingCreateInput(
    input: BookingCreateValidationInput,
): BookingCreateValidationFailure | null {
    if (!input.isServiceBooking) {
        return null;
    }

    if (input.locationId <= 0) {
        return {
            code: 'booking_location_required',
            messageKey: 'bookings.error_location',
        };
    }

    if (input.dates.length === 0) {
        return {
            code: 'booking_dates_required',
            messageKey: 'bookings.error_dates',
        };
    }

    if (input.isSkiService && (input.skillId ?? 0) <= 0) {
        return {
            code: 'booking_skill_required',
            messageKey: 'bookings.error_skill',
        };
    }

    return null;
}
