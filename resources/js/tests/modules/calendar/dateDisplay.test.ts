import {
    dateInputToIso,
    formatGermanDate,
    formatGermanDateTime,
} from '@/modules/calendar/dateDisplay';
import { describe, expect, it } from 'vitest';

describe('German date display', () => {
    it('formats calendar dates with four-digit years and padded days and months', () => {
        expect(formatGermanDate('2026-01-05')).toBe('05.01.2026');
        expect(formatGermanDate('2026-01-05 00:00:00')).toBe('05.01.2026');
        expect(formatGermanDate(null)).toBe('—');
    });

    it('normalizes German and ISO entries without accepting invalid dates', () => {
        expect(dateInputToIso('29.02.2028')).toBe('2028-02-29');
        expect(dateInputToIso('2028-02-29')).toBe('2028-02-29');
        expect(dateInputToIso('29.02.2026')).toBeNull();
        expect(dateInputToIso('31.11.2026')).toBeNull();
    });

    it('keeps wall-clock times and adds a German date to timestamps', () => {
        expect(formatGermanDateTime('2026-11-19 13:02:03')).toBe(
            '19.11.2026 13:02:03',
        );
        expect(formatGermanDateTime('2026-11-19T13:02:03')).toBe(
            '19.11.2026 13:02:03',
        );
        expect(formatGermanDateTime('invalid', '-')).toBe('-');
    });
});
