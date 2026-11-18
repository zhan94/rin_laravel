import { firstApiErrorMessage, normalizeApiErrors } from '@/services/apiErrors';
import { HttpError } from '@/services/http';
import { describe, expect, it } from 'vitest';

describe('apiErrors', () => {
    it('returns errors from HttpError', () => {
        const error = new HttpError(422, 'Invalid', [
            {
                code: 'validation',
                message: 'Invalid field.',
            },
        ]);

        expect(normalizeApiErrors(error, 'Fallback')).toEqual([
            {
                code: 'validation',
                message: 'Invalid field.',
            },
        ]);
        expect(firstApiErrorMessage(error, 'Fallback')).toBe('Invalid field.');
    });

    it('returns fallback errors for non-HttpError failures', () => {
        expect(normalizeApiErrors(new Error('Network'), 'Fallback')).toEqual([
            {
                code: 'network_error',
                message: 'Fallback',
            },
        ]);
        expect(firstApiErrorMessage(new Error('Network'), 'Fallback')).toBe(
            'Fallback',
        );
    });
});
