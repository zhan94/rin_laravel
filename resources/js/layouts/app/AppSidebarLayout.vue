<script setup lang="ts">
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { usePage } from '@inertiajs/vue3';
import { useMediaQuery } from '@vueuse/core';
import { nextTick, ref, watch } from 'vue';

const page = usePage();
const menuOpen = ref(false);
const mobileLayout = useMediaQuery('(max-width: 900px)');
watch(
    () => page.url,
    () => {
        menuOpen.value = false;
    },
);

function closeMenu(): void {
    if (menuOpen.value) {
        menuOpen.value = false;
        document.getElementById('mobile-nav-toggle')?.focus();
    }
}

async function toggleMenu(): Promise<void> {
    if (menuOpen.value) {
        closeMenu();
        return;
    }

    menuOpen.value = true;
    await nextTick();
    document.querySelector<HTMLElement>('#mobile-nav a')?.focus();
}
</script>

<template>
    <AppShell
        variant="sidebar"
        class="legacy-shell-bg"
        @keydown.esc="closeMenu"
    >
        <AppSidebarHeader :menu-open="menuOpen" @toggle-menu="toggleMenu" />
        <div class="crm-sidebar-layout" :class="{ 'app-shell': !mobileLayout }">
            <AppSidebar
                id="mobile-nav"
                :class="{ 'sidebar-mobile-open': menuOpen }"
            />
            <main class="app-main">
                <div class="app-main-container">
                    <slot />
                </div>
            </main>
        </div>
    </AppShell>
</template>

<style scoped>
@media (max-width: 900px) {
    .crm-sidebar-layout {
        display: flex !important;
        flex-direction: column;
        gap: 12px !important;
        padding: 12px;
    }

    .crm-sidebar-layout .app-main {
        width: 100%;
        min-width: 0;
    }

    :deep(.topbar-title-link) {
        display: none !important;
    }

    :deep(.topbar-menu-btn) {
        width: auto !important;
        gap: 5px;
        padding-inline: 10px;
    }

    #mobile-nav {
        display: none !important;
    }

    #mobile-nav.sidebar-mobile-open {
        display: block !important;
        position: static !important;
        width: 100% !important;
        max-width: none !important;
    }
}
</style>
