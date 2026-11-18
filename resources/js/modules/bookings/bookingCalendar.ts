import { buildDateSelectionCalendarCells } from '@/modules/calendar/dateSelectionCalendar';
import {
    firstDayOfMonth,
    parseMonthCursor,
} from '@/modules/calendar/dateUtils';

export interface BookingCalendarCell {
    key: string;
    iso: string | null;
    dayNumber: number | null;
    available: boolean;
    selectable: boolean;
    selected: boolean;
    disabled: boolean;
    today: boolean;
}

export interface BuildBookingCalendarCellsInput {
    monthDate: Date;
    selectedDates: string[];
    disabledDates: string[];
    todayIso: string;
}

export function resolveBookingCalendarMonthDate(
    cursor: string,
    fallbackDate: Date,
): Date {
    const parsed = parseMonthCursor(cursor);

    return parsed ?? firstDayOfMonth(fallbackDate);
}

export function buildBookingCalendarCells(
    input: BuildBookingCalendarCellsInput,
): BookingCalendarCell[] {
    return buildDateSelectionCalendarCells(input).map((cell) => ({
        ...cell,
        available: cell.iso !== null && !cell.disabled,
        selectable: cell.iso !== null && !cell.disabled,
    }));
}

export function removeDisabledSelectedDates(
    selectedDates: string[],
    disabledDates: string[],
): string[] {
    const disabledDateSet = new Set(disabledDates);

    return selectedDates.filter((date) => !disabledDateSet.has(date));
}

export function toggleBookingCalendarDateSelection(
    selectedDates: string[],
    date: string,
): string[] {
    const selectedDateSet = new Set(selectedDates);

    if (selectedDateSet.has(date)) {
        selectedDateSet.delete(date);
    } else {
        selectedDateSet.add(date);
    }

    return Array.from(selectedDateSet).sort((left, right) =>
        left.localeCompare(right),
    );
}
