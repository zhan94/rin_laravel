import { useToast } from '@/composables/useToast';
import { useUiI18n } from '@/composables/useUiI18n';
import { readFirstDeepLinkParam } from '@/modules/notifications/deepLinkQuery';
import type {
    PaginationMeta,
    UserCreatePayload,
    UserRecord,
    UserRoleOption,
    UserUpdatePayload,
} from '@/modules/users/types';
import {
    createUser,
    deleteUser,
    listUsers,
    updateUser,
} from '@/modules/users/userService';
import { normalizeApiErrors } from '@/services/apiErrors';
import type { ApiError } from '@/services/http';
import { paginationFromMeta } from '@/services/pagination';
import { computed, onMounted, reactive } from 'vue';

export function useUsersPage() {
    const { info, success } = useToast();
    const { t } = useUiI18n();

    const state = reactive({
        rows: [] as UserRecord[],
        roles: [] as UserRoleOption[],
        loading: false,
        submitting: false,
        deletingId: null as number | null,
        errors: [] as ApiError[],
        query: '',
        page: 1,
        perPage: 25,
        pagination: {
            total: 0,
            per_page: 25,
            current_page: 1,
            last_page: 1,
        } as PaginationMeta,
        formOpen: false,
        editingId: null as number | null,
        form: {
            username: '',
            firstname: '',
            surname: '',
            lastname: '',
            email: '',
            password: '',
            role_id: 0,
            is_active: true,
        },
    });

    const summaryLabel = computed(() => {
        if (state.pagination.total === 0) {
            return t('users.summary_empty');
        }

        return t('users.summary_total', { count: state.pagination.total });
    });

    const userStats = computed(() => {
        const counts = {
            admins: 0,
            managers: 0,
            groupLeaders: 0,
            drivers: 0,
        };

        state.rows.forEach((user) => {
            const roleKey = user.role?.role_key ?? '';

            if (roleKey === 'admin' || roleKey === 'super_admin') {
                counts.admins += 1;
            } else if (roleKey === 'manager') {
                counts.managers += 1;
            } else if (roleKey === 'group_leader') {
                counts.groupLeaders += 1;
            } else if (roleKey === 'driver') {
                counts.drivers += 1;
            }
        });

        return counts;
    });

    function isEditing(): boolean {
        return state.editingId !== null;
    }

    function syncDefaultRole(): void {
        if (state.roles.length === 0) {
            state.form.role_id = 0;

            return;
        }

        const hasSelected = state.roles.some(
            (role) => role.id === state.form.role_id,
        );
        if (hasSelected) {
            return;
        }

        const active = state.roles.find((role) => role.is_active);
        state.form.role_id = active?.id ?? state.roles[0].id;
    }

    function resetForm(): void {
        state.editingId = null;
        state.form.username = '';
        state.form.firstname = '';
        state.form.surname = '';
        state.form.lastname = '';
        state.form.email = '';
        state.form.password = '';
        state.form.is_active = true;
        syncDefaultRole();
    }

    function openCreateForm(): void {
        resetForm();
        state.formOpen = true;
    }

    function closeForm(): void {
        resetForm();
        state.formOpen = false;
    }

    async function loadUsers(): Promise<void> {
        state.loading = true;
        state.errors = [];

        try {
            const response = await listUsers({
                q: state.query || undefined,
                page: state.page,
                per_page: state.perPage,
            });

            state.rows = response.data;
            state.pagination = paginationFromMeta(response.meta.pagination, {
                perPage: state.perPage,
            });
            state.roles = Array.isArray(response.meta.roles)
                ? (response.meta.roles as UserRoleOption[])
                : [];

            syncDefaultRole();
        } catch (error) {
            state.errors = normalizeApiErrors(error, t('users.error_load'));
        } finally {
            state.loading = false;
        }
    }

    function startEdit(user: UserRecord): void {
        state.editingId = user.id;
        state.form.username = user.username;
        state.form.firstname = user.firstname ?? '';
        state.form.surname = user.surname ?? '';
        state.form.lastname = user.lastname ?? '';
        state.form.email = user.email ?? '';
        state.form.password = '';
        state.form.role_id = user.role_id ?? 0;
        state.form.is_active = user.is_active;
        syncDefaultRole();
        state.formOpen = true;
    }

    async function submitUser(): Promise<void> {
        if (state.form.role_id <= 0) {
            state.errors = [
                {
                    code: 'validation_error',
                    message: t('users.error_role_required'),
                },
            ];

            return;
        }

        if (!isEditing() && state.form.password.trim() === '') {
            state.errors = [
                {
                    code: 'validation_error',
                    message: t('users.error_password_required'),
                },
            ];

            return;
        }

        state.submitting = true;
        state.errors = [];

        try {
            if (isEditing() && state.editingId !== null) {
                const payload: UserUpdatePayload = {
                    username: state.form.username,
                    firstname: state.form.firstname,
                    surname: state.form.surname || null,
                    lastname: state.form.lastname,
                    email: state.form.email || null,
                    password:
                        state.form.password.trim() !== ''
                            ? state.form.password
                            : null,
                    role_id: state.form.role_id,
                    is_active: state.form.is_active,
                };

                await updateUser(state.editingId, payload);
                success(
                    t('users.updated_title'),
                    t('users.updated_body', { id: state.editingId }),
                );
            } else {
                const payload: UserCreatePayload = {
                    username: state.form.username,
                    firstname: state.form.firstname,
                    surname: state.form.surname || null,
                    lastname: state.form.lastname,
                    email: state.form.email || null,
                    password: state.form.password,
                    role_id: state.form.role_id,
                    is_active: state.form.is_active,
                };

                const created = await createUser(payload);
                success(
                    t('users.created_title'),
                    t('users.created_body', { id: created.data.id }),
                );
            }

            await loadUsers();
            closeForm();
        } catch (error) {
            state.errors = normalizeApiErrors(error, t('users.error_save'));
        } finally {
            state.submitting = false;
        }
    }

    async function removeUser(userId: number): Promise<void> {
        if (!window.confirm(t('users.confirm_delete'))) {
            return;
        }

        state.deletingId = userId;
        state.errors = [];

        try {
            await deleteUser(userId);

            if (state.editingId === userId) {
                resetForm();
            }

            await loadUsers();
            success(
                t('users.deleted_title'),
                t('users.deleted_body', { id: userId }),
            );
        } catch (error) {
            state.errors = normalizeApiErrors(error, t('users.error_delete'));
        } finally {
            state.deletingId = null;
        }
    }

    function goToPage(page: number): void {
        state.page = page;
        void loadUsers();
    }

    function deepLinkedUserQuery(): string | null {
        return readFirstDeepLinkParam(['user', 'q']);
    }

    onMounted(async () => {
        const deepLink = deepLinkedUserQuery();
        if (deepLink !== null) {
            state.query = deepLink;
        }

        await loadUsers();
        resetForm();

        if (deepLink !== null) {
            info(
                t('users.deep_link_title'),
                t('users.deep_link_body', { id: deepLink }),
            );
        }
    });

    return {
        t,
        state,
        summaryLabel,
        userStats,
        isEditing,
        openCreateForm,
        closeForm,
        loadUsers,
        startEdit,
        submitUser,
        removeUser,
        goToPage,
    };
}
