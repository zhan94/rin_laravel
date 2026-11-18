import {
    firstDayOfMonth,
    formatIsoDate,
    lastDayOfMonth,
} from '@/modules/calendar/dateUtils';

export interface DateSelectionCalendarCell {
    key: string;
    iso: string | null;
    dayNumber: number | null;
    disabled: boolean;
    selected: boolean;
    today: boolean;
}

export interface BuildDateSelectionCalendarCellsInput {
    monthDate: Date;
    selectedDates: Iterable<string>;
    disabledDates: Iterable<string>;
    todayIso: string;
    keyPrefix?: string;
}

export function buildDateSelectionCalendarCells({
    monthDate,
    selectedDates,
    disabledDates,
    todayIso,
    keyPrefix = '',
}: BuildDateSelectionCalendarCellsInput): DateSelectionCalendarCell[] {
    const firstDay = firstDayOfMonth(monthDate);
    const daysInMonth = lastDayOfMonth(monthDate).getDate();
    const leadingBlankDays = firstDay.getDay();
    const selectedDateSet = new Set(selectedDates);
    const disabledDateSet = new Set(disabledDates);

    const cells: DateSelectionCalendarCell[] = [];
    for (let index = 0; index < leadingBlankDays; index += 1) {
        cells.push(createBlankCell(`${keyPrefix}blank-leading-${index}`));
    }

    for (let day = 1; day <= daysInMonth; day += 1) {
        const iso = formatIsoDate(
            new Date(firstDay.getFullYear(), firstDay.getMonth(), day),
        );

        cells.push({
            key: `${keyPrefix}${iso}`,
            iso,
            dayNumber: day,
            disabled: disabledDateSet.has(iso),
            selected: selectedDateSet.has(iso),
            today: iso === todayIso,
        });
    }

    while (cells.length % 7 !== 0) {
        cells.push(
            createBlankCell(`${keyPrefix}blank-trailing-${cells.length}`),
        );
    }

    return cells;
}

function createBlankCell(key: string): DateSelectionCalendarCell {
    return {
        key,
        iso: null,
        dayNumber: null,
        disabled: true,
        selected: false,
        today: false,
    };
}
