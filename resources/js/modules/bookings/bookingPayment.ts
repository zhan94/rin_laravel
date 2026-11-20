import type { UiTranslationKey } from '@/composables/useUiI18n';
import { formatEur } from '@/modules/money/currency';

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
    rawValue: string | number | null | undefined,
): ParsedInlinePayment {
    const amount = Number(
        typeof rawValue === 'string' ? rawValue.trim() : (rawValue ?? ''),
    );

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
    localeTag?: string,
): BookingPaymentMessage {
    if (remainingEur !== null) {
        return {
            messageKey: 'bookings.payment_remaining',
            params: {
                amount: formatEur(remainingEur, localeTag),
            },
        };
    }

    return {
        messageKey: 'bookings.payment_applied',
    };
}
