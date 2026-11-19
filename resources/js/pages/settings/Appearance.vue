<script setup lang="ts">
import { useUiI18n } from '@/composables/useUiI18n';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Head } from '@inertiajs/vue3';

const { t } = useUiI18n();
</script>

<template>
    <AppLayout>
        <Head :title="t('settings.appearance_title')" />

        <h1 class="sr-only">{{ t('settings.appearance_title') }}</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    :title="t('settings.appearance_title')"
                    :description="t('settings.appearance_desc')"
                />
                <AppearanceTabs />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
