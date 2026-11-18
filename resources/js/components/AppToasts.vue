<script setup lang="ts">
import { useToast } from '@/composables/useToast';
import { CheckCircle2, Info, X, XCircle } from 'lucide-vue-next';
import { computed } from 'vue';

const { toasts, removeToast } = useToast();

function iconFor(kind: string) {
    if (kind === 'success') {
        return CheckCircle2;
    }
    if (kind === 'error') {
        return XCircle;
    }

    return Info;
}

function toneClasses(kind: string): string {
    if (kind === 'success') {
        return 'border-green-200/80 bg-green-50/85 text-green-900';
    }
    if (kind === 'error') {
        return 'border-red-200/80 bg-red-50/90 text-red-900';
    }

    return 'border-sky-200/80 bg-sky-50/90 text-sky-900';
}

const visibleToasts = computed(() => toasts.value);
</script>

<template>
    <div
        class="pointer-events-none fixed top-5 right-5 z-[100] flex w-full max-w-sm flex-col gap-2"
    >
        <TransitionGroup name="toast" tag="div">
            <div
                v-for="toast in visibleToasts"
                :key="toast.id"
                class="pointer-events-auto rounded-2xl border p-3 shadow-lg backdrop-blur-sm"
                :class="toneClasses(toast.kind)"
            >
                <div class="flex items-start gap-3">
                    <component
                        :is="iconFor(toast.kind)"
                        class="mt-0.5 h-4 w-4 shrink-0"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium">{{ toast.title }}</p>
                        <p class="mt-0.5 text-sm opacity-90">
                            {{ toast.message }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded p-1 opacity-70 hover:opacity-100"
                        @click="removeToast(toast.id)"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </TransitionGroup>
    </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.2s ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-8px) scale(0.97);
}
</style>
