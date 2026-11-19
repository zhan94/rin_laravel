<script setup lang="ts">
import { useUiI18n } from '@/composables/useUiI18n';

import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';
import { send } from '@/routes/verification';
import { Form, Head } from '@inertiajs/vue3';

const { t } = useUiI18n();

defineProps<{
    status?: string;
}>();

const csrfToken =
    document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content') ?? '';
</script>

<template>
    <AuthLayout
        :title="t('auth.verify_email')"
        :description="t('auth.verify_email_desc')"
    >
        <Head :title="t('auth.verify_email')" />

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ t('auth.verify_email_sent') }}
        </div>

        <Form
            v-bind="send.form()"
            class="space-y-6 text-center"
            v-slot="{ processing }"
        >
            <Button :disabled="processing" variant="secondary">
                <Spinner v-if="processing" />
                {{ t('auth.verify_email_resend') }}
            </Button>
        </Form>

        <form :action="logout().url" method="post" class="mt-6 text-center">
            <input type="hidden" name="_token" :value="csrfToken" />
            <button
                type="submit"
                class="mx-auto block text-sm text-slate-600 hover:text-slate-900"
            >
                {{ t('common.logout') }}
            </button>
        </form>
    </AuthLayout>
</template>
