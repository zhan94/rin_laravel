import { describe, expect, it } from 'vitest';

import { formatPersonName } from '@/utils/personName';

describe('personName', () => {
    it('prefers the explicit name field when present', () => {
        expect(
            formatPersonName({
                name: 'Mira Petrova',
                firstname: 'Ignored',
                lastname: 'User',
                username: 'mira',
            }),
        ).toBe('Mira Petrova');
    });

    it('builds a name from parts and falls back to username or a provided label', () => {
        expect(
            formatPersonName({
                firstname: 'Mira',
                surname: 'V.',
                lastname: 'Petrova',
                username: 'mira',
            }),
        ).toBe('Mira V. Petrova');

        expect(
            formatPersonName({
                firstname: '',
                surname: null,
                lastname: '',
                username: 'mira',
            }),
        ).toBe('mira');

        expect(formatPersonName(null, '#17')).toBe('#17');
    });
});
