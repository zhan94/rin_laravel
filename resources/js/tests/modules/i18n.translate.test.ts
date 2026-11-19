import {
    interpolate,
    normalizeLocale,
    translateUiMessage,
} from '@/modules/i18n/translate';
import { describe, expect, it } from 'vitest';

describe('i18n translate helpers', () => {
    it('normalizes supported locales and falls back for invalid values', () => {
        expect(normalizeLocale('de', 'en')).toBe('de');
        expect(normalizeLocale(' ES ', 'en')).toBe('es');
        expect(normalizeLocale('fr', 'en')).toBe('en');
        expect(normalizeLocale(null, 'en')).toBe('en');
    });

    it('interpolates named placeholders', () => {
        expect(interpolate(':count unread', { count: 3 })).toBe('3 unread');
    });

    it('translates with the selected locale and parameters', () => {
        expect(
            translateUiMessage('common.unread_count', 'de', 'en', {
                count: 7,
            }),
        ).toBe('7 ungelesen');
    });

    it.each([
        ['en', 'Booking', 'Add customer', 'New customer'],
        ['de', 'Buchung', 'Kunden hinzufügen', 'Neuer Kunde'],
        ['es', 'Reserva', 'Añadir cliente', 'Nuevo cliente'],
    ] as const)(
        'uses distinct module and customer labels in %s',
        (locale, booking, addCustomer, newCustomer) => {
            expect(translateUiMessage('bookings.title', locale, 'en')).toBe(
                booking,
            );
            expect(
                translateUiMessage('customers.add_customer', locale, 'en'),
            ).toBe(addCustomer);
            expect(
                translateUiMessage('bookings.new_customer', locale, 'en'),
            ).toBe(newCustomer);
            expect(
                translateUiMessage(
                    'bookings.error_existing_customer',
                    locale,
                    'en',
                ),
            ).toContain(newCustomer);
        },
    );
});
