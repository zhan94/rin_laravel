import { buildDateSelectionCalendarCells } from '@/modules/calendar/dateSelectionCalendar';
import { describe, expect, it } from 'vitest';

describe('dateSelectionCalendar', () => {
    it('builds selectable month cells with status flags', () => {
        const cells = buildDateSelectionCalendarCells({
            monthDate: new Date(2026, 1, 1),
            selectedDates: ['2026-02-03'],
            disabledDates: ['2026-02-04'],
            todayIso: '2026-02-05',
        });

        expect(cells).toHaveLength(28);
        expect(cells[0]).toMatchObject({
            key: '2026-02-01',
            iso: '2026-02-01',
            dayNumber: 1,
        });
        expect(cells.find((cell) => cell.iso === '2026-02-03')).toMatchObject({
            selected: true,
            disabled: false,
        });
        expect(cells.find((cell) => cell.iso === '2026-02-04')).toMatchObject({
            selected: false,
            disabled: true,
        });
        expect(cells.find((cell) => cell.iso === '2026-02-05')).toMatchObject({
            today: true,
        });
    });

    it('pads leading and trailing blanks with optional key prefixes', () => {
        const keyPrefix = 'slot-12-';
        const cells = buildDateSelectionCalendarCells({
            monthDate: new Date(2026, 4, 1),
            selectedDates: [],
            disabledDates: [],
            todayIso: '2026-05-10',
            keyPrefix,
        });

        expect(cells[0]).toMatchObject({
            key: `${keyPrefix}blank-leading-0`,
            iso: null,
            disabled: true,
        });
        expect(cells.find((cell) => cell.iso === '2026-05-01')).toMatchObject({
            key: 'slot-12-2026-05-01',
            dayNumber: 1,
        });
        expect(cells.at(-1)).toMatchObject({
            key: 'slot-12-blank-trailing-41',
            iso: null,
        });
    });
});
