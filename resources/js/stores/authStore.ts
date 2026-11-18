import type { Auth, AuthPermissions, User } from '@/types/auth';
import { usePage } from '@inertiajs/vue3';
import { defineStore } from 'pinia';
import { computed } from 'vue';

export const useAuthStore = defineStore('auth', () => {
    const page = usePage();

    const auth = computed<Auth | null>(() => {
        const value = page.props.auth;

        return value ? (value as Auth) : null;
    });

    const user = computed<User | null>(() => auth.value?.user ?? null);
    const permissions = computed<Partial<AuthPermissions>>(
        () => auth.value?.permissions ?? {},
    );

    const can = (permission: keyof AuthPermissions): boolean =>
        Boolean(permissions.value?.[permission]);

    return {
        auth,
        user,
        permissions,
        can,
    };
});
