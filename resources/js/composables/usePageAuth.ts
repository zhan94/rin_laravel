import type { Auth } from '@/types/auth';
import { usePage } from '@inertiajs/vue3';
import { computed, type ComputedRef } from 'vue';

export function usePageAuth(): ComputedRef<Auth> {
    const page = usePage();

    return computed(() => page.props.auth as Auth);
}
