import {
    chunkIntoWeeks,
    firstDayOfMonth,
    formatIsoDate,
    formatMonthCursor,
    lastDayOfMonth,
    parseIsoDate,
    parseMonthCursor,
    uniqueSortedDateTokens,
} from '@/modules/calendar/dateUtils';
import { describe, expect, it } from 'vitest';

describe('dateUtils', () => {
    it('normalizes comma and whitespace separated date tokens', () => {
        expect(
            uniqueSortedDateTokens('2026-02-03, 2026-02-01 2026-02-03'),
        ).toEqual(['2026-02-01', '2026-02-03']);
    });

    it('formats and parses ISO dates without timezone drift', () => {
        const date = new Date(2026, 1, 9);

        expect(formatIsoDate(date)).toBe('2026-02-09');
        expect(parseIsoDate('2026-02-09')).toEqual(date);
        expect(parseIsoDate('2026-02-31')).toBeNull();
    });

    it('formats and parses calendar month cursors', () => {
        const date = new Date(2026, 1, 1);

        expect(formatMonthCursor(date)).toBe('2026-02');
        expect(parseMonthCursor('2026-02')).toEqual(date);
        expect(parseMonthCursor('2026-13')).toBeNull();
    });

    it('calculates month bounds and week chunks', () => {
        const date = new Date(2026, 1, 15);

        expect(firstDayOfMonth(date)).toEqual(new Date(2026, 1, 1));
        expect(lastDayOfMonth(date)).toEqual(new Date(2026, 1, 28));
        expect(chunkIntoWeeks([1, 2, 3, 4, 5], 2)).toEqual([
            [1, 2],
            [3, 4],
            [5],
        ]);
    });
});
