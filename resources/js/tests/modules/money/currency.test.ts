import { formatEur } from '@/modules/money/currency';
import { describe, expect, it } from 'vitest';

describe('formatEur', () => {
    it('formats euros and cents using the requested locale', () => {
        expect(formatEur(10.25)).toBe('€10.25');
        expect(formatEur(10.25, 'de-DE')).toBe('10,25\u00a0€');
    });
});
