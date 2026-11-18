import { buildQueryString, withQuery } from '@/services/queryString';
import { describe, expect, it } from 'vitest';

describe('queryString', () => {
    it('skips empty values while preserving false and zero', () => {
        expect(
            buildQueryString({
                active: false,
                empty: '',
                missing: undefined,
                none: null,
                page: 0,
                q: 'snow camp',
            }),
        ).toBe('active=false&page=0&q=snow+camp');
    });

    it('appends query parameters only when present', () => {
        expect(withQuery('/api/v1/services', {})).toBe('/api/v1/services');
        expect(withQuery('/api/v1/services', { page: 2 })).toBe(
            '/api/v1/services?page=2',
        );
    });
});
