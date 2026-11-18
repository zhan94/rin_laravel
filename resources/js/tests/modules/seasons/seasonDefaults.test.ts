import {
    formatSeasonDayMonth,
    parseSeasonDayMonth,
} from '@/modules/seasons/seasonDefaults';
import { describe, expect, it } from 'vitest';

describe('seasonDefaults', () => {
    it('formats season day-month values with two digits', () => {
        expect(formatSeasonDayMonth(1, 9)).toBe('01-09');
        expect(formatSeasonDayMonth(31, 12)).toBe('31-12');
    });

    it('parses day-month values with optional spacing', () => {
        expect(parseSeasonDayMonth('01-09')).toEqual({ day: 1, month: 9 });
        expect(parseSeasonDayMonth(' 31 - 8 ')).toEqual({
            day: 31,
            month: 8,
        });
    });

    it('rejects invalid day-month values', () => {
        expect(parseSeasonDayMonth('')).toBeNull();
        expect(parseSeasonDayMonth('2026-01-01')).toBeNull();
        expect(parseSeasonDayMonth('00-09')).toBeNull();
        expect(parseSeasonDayMonth('32-09')).toBeNull();
        expect(parseSeasonDayMonth('01-00')).toBeNull();
        expect(parseSeasonDayMonth('01-13')).toBeNull();
    });
});
