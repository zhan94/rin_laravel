import type {
    UserCreatePayload,
    UserListParams,
    UserRecord,
    UserUpdatePayload,
} from '@/modules/users/types';
import { httpRequest, type ApiEnvelope } from '@/services/http';
import { withQuery } from '@/services/queryString';

const usersBasePath = '/api/v1/users';

export function listUsers(
    params: UserListParams = {},
): Promise<ApiEnvelope<UserRecord[]>> {
    return httpRequest<UserRecord[]>(
        withQuery(usersBasePath, {
            q: params.q,
            page: params.page || undefined,
            per_page: params.per_page || undefined,
        }),
    );
}

export function createUser(
    payload: UserCreatePayload,
): Promise<ApiEnvelope<UserRecord>> {
    return httpRequest<UserRecord>(usersBasePath, {
        method: 'POST',
        body: payload,
    });
}

export function updateUser(
    userId: number,
    payload: UserUpdatePayload,
): Promise<ApiEnvelope<UserRecord>> {
    return httpRequest<UserRecord>(`${usersBasePath}/${userId}`, {
        method: 'PUT',
        body: payload,
    });
}

export function deleteUser(
    userId: number,
): Promise<ApiEnvelope<{ deleted: boolean }>> {
    return httpRequest<{ deleted: boolean }>(`${usersBasePath}/${userId}`, {
        method: 'DELETE',
    });
}
