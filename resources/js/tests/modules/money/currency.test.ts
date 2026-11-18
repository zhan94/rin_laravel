import {
    bgnToEur,
    eurToBgn,
    formatEurBgnPairFromBgn,
    formatEurBgnPairFromEur,
} from '@/modules/money/currency';
import { describe, expect, it } from 'vitest';

describe('currency', () => {
    it('converts between BGN and EUR with money rounding', () => {
        expect(bgnToEur(1.95583)).toBe(1);
        expect(eurToBgn(10)).toBe(19.56);
        expect(bgnToEur(null)).toBeNull();
        expect(eurToBgn(null)).toBeNull();
    });

    it('formats EUR/BGN pairs from BGN values', () => {
        expect(formatEurBgnPairFromBgn(19.5583)).toBe('10.00€ (19.56 BGN)');
    });

    it('formats EUR/BGN pairs from EUR values with locale support', () => {
        expect(formatEurBgnPairFromEur(10, 'de-DE')).toBe('10,00€ (19,56 BGN)');
    });
});
