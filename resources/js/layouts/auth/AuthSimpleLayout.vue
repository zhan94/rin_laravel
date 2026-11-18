<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center overflow-hidden bg-[radial-gradient(circle_at_15%_15%,rgba(125,211,252,0.25),transparent_28%),radial-gradient(circle_at_85%_10%,rgba(59,130,246,0.24),transparent_30%),linear-gradient(180deg,#eef4ff_0%,#e2ebf8_45%,#f4f7fb_100%)] p-6 md:p-10"
    >
        <div
            class="pointer-events-none absolute -top-24 right-[-6rem] h-72 w-72 rounded-full bg-sky-200/50 blur-3xl"
        />
        <div
            class="pointer-events-none absolute bottom-[-8rem] left-[-7rem] h-80 w-80 rounded-full bg-indigo-200/40 blur-3xl"
        />
        <div class="w-full max-w-sm">
            <div
                class="flex flex-col gap-8 rounded-3xl border border-white/80 bg-white/88 p-8 shadow-xl backdrop-blur-xl"
            >
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 font-medium"
                    >
                        <div
                            class="mb-1 flex h-10 w-10 items-center justify-center rounded-xl bg-slate-900 text-white"
                        >
                            <AppLogoIcon class="size-4 fill-current" />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-semibold text-slate-900">
                            {{ title }}
                        </h1>
                        <p class="text-center text-sm text-slate-600">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
