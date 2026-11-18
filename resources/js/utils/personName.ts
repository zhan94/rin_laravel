export interface PersonNameLike {
    name?: string | null;
    username?: string | null;
    firstname?: string | null;
    surname?: string | null;
    lastname?: string | null;
}

function trimString(value: string | null | undefined): string {
    return typeof value === 'string' ? value.trim() : '';
}

export function formatPersonName(
    person: PersonNameLike | null | undefined,
    fallback = '',
): string {
    if (!person) {
        return trimString(fallback);
    }

    const explicitName = trimString(person.name);

    if (explicitName !== '') {
        return explicitName;
    }

    const parts = [person.firstname, person.surname, person.lastname]
        .map(trimString)
        .filter((value) => value !== '');

    if (parts.length > 0) {
        return parts.join(' ');
    }

    const username = trimString(person.username);

    if (username !== '') {
        return username;
    }

    return trimString(fallback);
}
