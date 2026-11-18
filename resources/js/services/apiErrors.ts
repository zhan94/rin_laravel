import { HttpError, type ApiError } from '@/services/http';

export function normalizeApiErrors(
    error: unknown,
    fallbackMessage: string,
): ApiError[] {
    if (error instanceof HttpError) {
        return error.errors;
    }

    return [
        {
            code: 'network_error',
            message: fallbackMessage,
        },
    ];
}

export function firstApiErrorMessage(
    error: unknown,
    fallbackMessage: string,
): string {
    if (error instanceof HttpError && error.errors.length > 0) {
        return error.errors[0].message;
    }

    return fallbackMessage;
}
