<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useNotifications } from '@/composables/useNotifications';
import { useUiI18n } from '@/composables/useUiI18n';
import type { NotificationItem } from '@/modules/notifications/types';
import { formatPersonName } from '@/utils/personName';
import { Bell, CheckCheck, Loader2, RefreshCcw } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted } from 'vue';

const props = withDefaults(
    defineProps<{
        showLabel?: boolean;
    }>(),
    {
        showLabel: false,
    },
);

const {
    state,
    refresh,
    startPolling,
    stopPolling,
    markAllRead,
    openNotification,
} = useNotifications();
const { formatDateTime, t } = useUiI18n();

const unreadLabel = computed(() => {
    if (state.unreadCount <= 0) {
        return '';
    }

    if (state.unreadCount > 99) {
        return '99+';
    }

    return String(state.unreadCount);
});

function timestampLabel(value: string | null): string {
    return value ? formatDateTime(value) : '';
}

function actorLabel(notification: NotificationItem): string {
    return formatPersonName(notification.actor, '');
}

async function onOpenNotification(
    notification: NotificationItem,
): Promise<void> {
    if (state.actionInProgress) {
        return;
    }

    state.actionInProgress = true;

    try {
        await openNotification(notification);
    } finally {
        state.actionInProgress = false;
    }
}

onMounted(() => {
    startPolling();
    void refresh();
});

onBeforeUnmount(() => {
    stopPolling();
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                :size="props.showLabel ? 'default' : 'icon'"
                :class="
                    props.showLabel
                        ? 'topbar-link topbar-link--notify relative gap-1.5 px-3'
                        : 'relative h-9 w-9'
                "
                :aria-label="t('common.notifications')"
            >
                <Bell class="h-4 w-4" />
                <span v-if="props.showLabel">{{
                    t('common.notifications')
                }}</span>
                <span
                    v-if="unreadLabel !== ''"
                    class="absolute -top-1 -right-1 rounded-full bg-red-600 px-1.5 py-0.5 text-[10px] leading-none font-semibold text-white"
                >
                    {{ unreadLabel }}
                </span>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-[360px] p-0">
            <div class="border-b border-border px-3 py-2">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-semibold">
                        {{ t('common.notifications') }}
                    </p>
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            class="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50"
                            :disabled="state.loading || state.actionInProgress"
                            :title="t('common.refresh')"
                            @click="() => refresh()"
                        >
                            <RefreshCcw class="h-4 w-4" />
                        </button>
                        <button
                            type="button"
                            class="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground disabled:opacity-50"
                            :disabled="
                                state.unreadCount <= 0 || state.actionInProgress
                            "
                            :title="t('common.mark_all_read')"
                            @click="() => markAllRead()"
                        >
                            <CheckCheck class="h-4 w-4" />
                        </button>
                    </div>
                </div>
                <p class="mt-0.5 text-xs text-muted-foreground">
                    {{ t('common.unread_count', { count: state.unreadCount }) }}
                </p>
            </div>

            <div class="max-h-[360px] overflow-y-auto">
                <div
                    v-if="state.loading && state.notifications.length === 0"
                    class="flex items-center justify-center gap-2 px-4 py-6 text-sm text-muted-foreground"
                >
                    <Loader2 class="h-4 w-4 animate-spin" />
                    {{ t('common.loading_notifications') }}
                </div>

                <button
                    v-for="notification in state.notifications"
                    :key="notification.id"
                    type="button"
                    class="w-full border-b border-border/60 px-3 py-2 text-left transition-colors hover:bg-muted/50"
                    :class="
                        notification.status === 'unread' ? 'bg-blue-50/40' : ''
                    "
                    @click="onOpenNotification(notification)"
                >
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-sm leading-snug font-medium">
                            {{ notification.title }}
                        </p>
                        <span
                            v-if="notification.status === 'unread'"
                            class="mt-1 inline-block h-2 w-2 shrink-0 rounded-full bg-blue-600"
                        />
                    </div>
                    <p class="mt-1 line-clamp-2 text-xs text-muted-foreground">
                        {{ notification.message }}
                    </p>
                    <div
                        class="mt-1 flex items-center gap-2 text-[11px] text-muted-foreground"
                    >
                        <span v-if="actorLabel(notification) !== ''">{{
                            actorLabel(notification)
                        }}</span>
                        <span
                            v-if="
                                timestampLabel(notification.created_at) !== ''
                            "
                        >
                            {{ timestampLabel(notification.created_at) }}
                        </span>
                    </div>
                </button>

                <div
                    v-if="!state.loading && state.notifications.length === 0"
                    class="px-4 py-6 text-center text-sm text-muted-foreground"
                >
                    {{ t('common.no_notifications') }}
                </div>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
