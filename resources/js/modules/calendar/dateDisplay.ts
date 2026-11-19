import { formatIsoDate, parseIsoDate } from './dateUtils';

// Forms and APIs continue to exchange ISO dates regardless of the UI language.
export function dateInputToIso(value: string): string | null {
    const text = value.trim();
    const german = /^(\d{1,2})\.(\d{1,2})\.(\d{4})$/.exec(text);
    const iso = german
        ? `${german[3]}-${german[2].padStart(2, '0')}-${german[1].padStart(2, '0')}`
        : text;

    return parseIsoDate(iso) ? iso : null;
}

export function formatGermanDate(
    value: string | null | undefined,
    fallback = '—',
): string {
    const databaseDate = value
        ?.trim()
        .match(/^(\d{4}-\d{2}-\d{2})[ T]\d{2}:\d{2}/);
    const iso = value ? dateInputToIso(databaseDate?.[1] ?? value) : null;
    if (iso === null) return fallback;

    const [year, month, day] = iso.split('-');
    return `${day}.${month}.${year}`;
}

export function formatGermanDateTime(
    value: string | null | undefined,
    fallback = '—',
): string {
    if (!value) return fallback;

    const date = dateInputToIso(value);
    if (date !== null) return formatGermanDate(date);

    const timestamp = value.trim().replace(' ', 'T');
    const parts =
        /^(\d{4}-\d{2}-\d{2})T([01]\d|2[0-3]):([0-5]\d)(?::([0-5]\d)(?:\.\d+)?)?(Z|[+-]\d{2}:?\d{2})?$/.exec(
            timestamp,
        );
    if (!parts || !dateInputToIso(parts[1])) return fallback;

    // Preserve the wall time of timestamps supplied without a time zone.
    if (!parts[5]) {
        return `${formatGermanDate(parts[1])} ${parts[2]}:${parts[3]}:${parts[4] ?? '00'}`;
    }

    const instant = new Date(timestamp);
    if (Number.isNaN(instant.getTime())) return fallback;

    return `${formatGermanDate(formatIsoDate(instant))} ${instant.toLocaleTimeString(
        'de-DE',
        {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        },
    )}`;
}
