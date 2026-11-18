import type { UiTranslationKey } from '@/composables/useUiI18n';

export interface ParsedInlinePayment {
    valid: boolean;
    amount: number;
    messageKey?: UiTranslationKey;
}

export interface BookingPaymentMessage {
    messageKey: UiTranslationKey;
    params?: Record<string, string | number>;
}

export function parseInlinePaymentAmount(
    rawValue: string | null | undefined,
): ParsedInlinePayment {
    const amount = Number((rawValue ?? '').trim());

    if (!Number.isFinite(amount) || amount <= 0) {
        return {
            valid: false,
            amount: 0,
            messageKey: 'bookings.error_payment_positive',
        };
    }

    return {
        valid: true,
        amount,
    };
}

export function buildPaymentAppliedMessage(
    remainingEur: number | null,
): BookingPaymentMessage {
    if (remainingEur !== null) {
        return {
            messageKey: 'bookings.payment_remaining',
            params: {
                amount: remainingEur.toFixed(2),
            },
        };
    }

    return {
        messageKey: 'bookings.payment_applied',
    };
}
