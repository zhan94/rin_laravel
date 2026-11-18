import {
    validateBookingCreateInput,
    validateBookingStepOne,
} from '@/modules/bookings/bookingCreateValidation';
import { describe, expect, it } from 'vitest';

describe('bookingCreateValidation', () => {
    it('validates step one service type and existing customer selection', () => {
        expect(
            validateBookingStepOne({
                serviceType: 0,
                isNewCustomer: false,
                customerId: null,
            }),
        ).toEqual({
            messageKey: 'bookings.error_service_type',
        });

        expect(
            validateBookingStepOne({
                serviceType: 1,
                isNewCustomer: false,
                customerId: null,
            }),
        ).toEqual({
            messageKey: 'bookings.error_existing_customer',
        });

        expect(
            validateBookingStepOne({
                serviceType: 1,
                isNewCustomer: true,
                customerId: null,
            }),
        ).toBeNull();
    });

    it('allows bonus bookings without service-only requirements', () => {
        expect(
            validateBookingCreateInput({
                isServiceBooking: false,
                isSkiService: false,
                locationId: 0,
                dates: [],
                skillId: null,
            }),
        ).toBeNull();
    });

    it('requires location and dates for service bookings', () => {
        expect(
            validateBookingCreateInput({
                isServiceBooking: true,
                isSkiService: false,
                locationId: 0,
                dates: ['2026-03-01'],
                skillId: null,
            }),
        ).toEqual({
            code: 'booking_location_required',
            messageKey: 'bookings.error_location',
        });

        expect(
            validateBookingCreateInput({
                isServiceBooking: true,
                isSkiService: false,
                locationId: 5,
                dates: [],
                skillId: null,
            }),
        ).toEqual({
            code: 'booking_dates_required',
            messageKey: 'bookings.error_dates',
        });
    });

    it('requires skill only for ski service bookings', () => {
        expect(
            validateBookingCreateInput({
                isServiceBooking: true,
                isSkiService: true,
                locationId: 5,
                dates: ['2026-03-01'],
                skillId: 0,
            }),
        ).toEqual({
            code: 'booking_skill_required',
            messageKey: 'bookings.error_skill',
        });

        expect(
            validateBookingCreateInput({
                isServiceBooking: true,
                isSkiService: true,
                locationId: 5,
                dates: ['2026-03-01'],
                skillId: 2,
            }),
        ).toBeNull();
    });
});
