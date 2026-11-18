import {
    defaultPaginationMeta,
    paginationFromMeta,
} from '@/services/pagination';
import { describe, expect, it } from 'vitest';

describe('pagination helpers', () => {
    it('creates default pagination metadata', () => {
        expect(defaultPaginationMeta({ perPage: 25 })).toEqual({
            total: 0,
            per_page: 25,
            current_page: 1,
            last_page: 1,
        });
    });

    it('uses API pagination values when present', () => {
        expect(
            paginationFromMeta(
                {
                    total: 42,
                    per_page: 10,
                    current_page: 3,
                    last_page: 5,
                },
                { total: 0, perPage: 25, currentPage: 1 },
            ),
        ).toEqual({
            total: 42,
            per_page: 10,
            current_page: 3,
            last_page: 5,
        });
    });

    it('falls back field-by-field for malformed metadata', () => {
        expect(
            paginationFromMeta(
                {
                    total: '42',
                    per_page: 50,
                },
                { total: 2, perPage: 25, currentPage: 4 },
            ),
        ).toEqual({
            total: 2,
            per_page: 50,
            current_page: 4,
            last_page: 1,
        });
    });
});
