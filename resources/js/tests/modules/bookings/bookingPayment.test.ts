import {
    buildPaymentAppliedMessage,
    parseInlinePaymentAmount,
} from '@/modules/bookings/bookingPayment';
import { describe, expect, it } from 'vitest';

describe('bookingPayment', () => {
    it('parses positive inline payment amounts', () => {
        expect(parseInlinePaymentAmount(' 25.50 ')).toEqual({
            valid: true,
            amount: 25.5,
        });
    });

    it('accepts the numeric value emitted by the payment input', () => {
        expect(parseInlinePaymentAmount(24)).toEqual({
            valid: true,
            amount: 24,
        });
    });

    it('rejects empty, zero and invalid inline payment amounts', () => {
        expect(parseInlinePaymentAmount('')).toEqual({
            valid: false,
            amount: 0,
            messageKey: 'bookings.error_payment_positive',
        });
        expect(parseInlinePaymentAmount('0').valid).toBe(false);
        expect(parseInlinePaymentAmount('abc').valid).toBe(false);
    });

    it('builds success message metadata for applied payments', () => {
        expect(buildPaymentAppliedMessage(null)).toEqual({
            messageKey: 'bookings.payment_applied',
        });
        expect(buildPaymentAppliedMessage(12.345)).toEqual({
            messageKey: 'bookings.payment_remaining',
            params: {
                amount: '12.35',
            },
        });
    });
});
