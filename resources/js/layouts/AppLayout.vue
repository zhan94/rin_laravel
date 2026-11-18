<script setup lang="ts">
import AppToasts from '@/components/AppToasts.vue';
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head>
        <link rel="stylesheet" href="/css/styles.css?v=legacy-20260306" />
    </Head>
    <AppLayout>
        <slot />
        <AppToasts />
    </AppLayout>
</template>
