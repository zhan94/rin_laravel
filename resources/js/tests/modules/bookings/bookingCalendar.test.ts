import {
    buildBookingCalendarCells,
    removeDisabledSelectedDates,
    resolveBookingCalendarMonthDate,
    toggleBookingCalendarDateSelection,
} from '@/modules/bookings/bookingCalendar';
import { describe, expect, it } from 'vitest';

describe('bookingCalendar', () => {
    it('resolves the cursor month or falls back to the current month', () => {
        const resolved = resolveBookingCalendarMonthDate(
            '2026-03',
            new Date(2026, 4, 14),
        );

        expect([
            resolved.getFullYear(),
            resolved.getMonth(),
            resolved.getDate(),
        ]).toEqual([2026, 2, 1]);

        expect(
            resolveBookingCalendarMonthDate(
                '',
                new Date(2026, 4, 14),
            ).getDate(),
        ).toBe(1);
    });

    it('builds month cells with blanks, selected dates, disabled dates and today marker', () => {
        const cells = buildBookingCalendarCells({
            monthDate: new Date(2026, 1, 1),
            selectedDates: ['2026-02-03'],
            disabledDates: ['2026-02-04'],
            todayIso: '2026-02-05',
        });

        expect(cells).toHaveLength(28);
        expect(cells[0]).toMatchObject({
            iso: '2026-02-01',
            dayNumber: 1,
        });
        expect(cells.find((cell) => cell.iso === '2026-02-03')).toMatchObject({
            selected: true,
            selectable: true,
        });
        expect(cells.find((cell) => cell.iso === '2026-02-04')).toMatchObject({
            disabled: true,
            selectable: false,
        });
        expect(cells.find((cell) => cell.iso === '2026-02-05')).toMatchObject({
            today: true,
        });
    });

    it('removes disabled selected dates and toggles date selection in sorted order', () => {
        expect(
            removeDisabledSelectedDates(
                ['2026-02-01', '2026-02-02'],
                ['2026-02-02'],
            ),
        ).toEqual(['2026-02-01']);

        expect(
            toggleBookingCalendarDateSelection(['2026-02-03'], '2026-02-01'),
        ).toEqual(['2026-02-01', '2026-02-03']);

        expect(
            toggleBookingCalendarDateSelection(['2026-02-03'], '2026-02-03'),
        ).toEqual([]);
    });
});
