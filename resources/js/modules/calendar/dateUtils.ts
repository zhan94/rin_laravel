export function uniqueSortedDateTokens(value: string): string[] {
    return Array.from(
        new Set(
            value
                .split(/[\s,]+/)
                .map((item) => item.trim())
                .filter((item) => item.length > 0),
        ),
    ).sort((left, right) => left.localeCompare(right));
}

export function formatIsoDate(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

export function parseIsoDate(value: string): Date | null {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value.trim());
    if (match === null) {
        return null;
    }

    const year = Number(match[1]);
    const month = Number(match[2]);
    const day = Number(match[3]);

    if (
        !Number.isFinite(year) ||
        !Number.isFinite(month) ||
        !Number.isFinite(day)
    ) {
        return null;
    }

    const date = new Date(year, month - 1, day);

    if (
        date.getFullYear() !== year ||
        date.getMonth() !== month - 1 ||
        date.getDate() !== day
    ) {
        return null;
    }

    return date;
}

export function firstDayOfMonth(date: Date): Date {
    return new Date(date.getFullYear(), date.getMonth(), 1);
}

export function lastDayOfMonth(date: Date): Date {
    return new Date(date.getFullYear(), date.getMonth() + 1, 0);
}

export function parseMonthCursor(value: string): Date | null {
    const match = /^(\d{4})-(\d{2})$/.exec(value.trim());
    if (match === null) {
        return null;
    }

    const year = Number(match[1]);
    const month = Number(match[2]);

    if (
        !Number.isFinite(year) ||
        !Number.isFinite(month) ||
        month < 1 ||
        month > 12
    ) {
        return null;
    }

    return new Date(year, month - 1, 1);
}

export function formatMonthCursor(date: Date): string {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
}

export function chunkIntoWeeks<T>(cells: T[], weekSize = 7): T[][] {
    const weeks: T[][] = [];

    for (let index = 0; index < cells.length; index += weekSize) {
        weeks.push(cells.slice(index, index + weekSize));
    }

    return weeks;
}
