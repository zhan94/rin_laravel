import {
    readFirstDeepLinkParam,
    readPositiveIntDeepLinkParam,
} from '@/modules/notifications/deepLinkQuery';
import { afterEach, describe, expect, it } from 'vitest';

function setSearch(search: string): void {
    window.history.pushState({}, '', `/crm/test${search}`);
}

describe('deepLinkQuery', () => {
    afterEach(() => {
        setSearch('');
    });

    it('reads the first non-empty query value by priority', () => {
        setSearch('?first=&second=value');

        expect(readFirstDeepLinkParam(['first', 'second'])).toBe('value');
    });

    it('reads positive integer query values', () => {
        setSearch('?issue=42');

        expect(readPositiveIntDeepLinkParam(['issue'])).toBe(42);

        setSearch('?issue=0');
        expect(readPositiveIntDeepLinkParam(['issue'])).toBeNull();

        setSearch('?issue=abc');
        expect(readPositiveIntDeepLinkParam(['issue'])).toBeNull();
    });
});
