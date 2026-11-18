export interface SeasonDayMonth {
    day: number;
    month: number;
}

export function formatSeasonDayMonth(day: number, month: number): string {
    return `${padSeasonDayMonthPart(day)}-${padSeasonDayMonthPart(month)}`;
}

export function parseSeasonDayMonth(value: string): SeasonDayMonth | null {
    const match = /^\s*(\d{1,2})\s*-\s*(\d{1,2})\s*$/.exec(value);

    if (match === null) {
        return null;
    }

    const day = Number(match[1]);
    const month = Number(match[2]);

    if (!Number.isInteger(day) || !Number.isInteger(month)) {
        return null;
    }

    if (day < 1 || day > 31 || month < 1 || month > 12) {
        return null;
    }

    return { day, month };
}

function padSeasonDayMonthPart(value: number): string {
    return String(value).padStart(2, '0');
}
