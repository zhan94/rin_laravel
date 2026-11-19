<script setup lang="ts">
import { useUiI18n } from '@/composables/useUiI18n';
import { parseIsoDate } from '@/modules/calendar/dateUtils';
import { formatEurBgnPairFromBgn } from '@/modules/money/currency';
import type {
    PaginationMeta,
    ServiceDefinition,
    ServiceSeason,
    ServiceSeasonFilters,
    ServiceSeasonSort,
    ServiceSeasonStatus,
} from '@/modules/services/types';
import ServiceSeasonAvailability from '@/pages/crm/components/ServiceSeasonAvailability.vue';
import { withQuery } from '@/services/queryString';
import { nextTick, ref, shallowRef } from 'vue';

const props = defineProps<{
    seasons: ServiceSeason[];
    services: ServiceDefinition[];
    pagination: PaginationMeta | null;
    loading: boolean;
    canManage: boolean;
    loadingEdit: boolean;
    deletingSlotId: number | null;
}>();

const emit = defineEmits<{
    page: [number];
    edit: [ServiceSeason];
    remove: [number];
}>();

const filters = defineModel<ServiceSeasonFilters>({ required: true });
const { t, localeTag } = useUiI18n();
const selected = shallowRef<ServiceSeason | null>(null);
const availabilityTrigger = ref<HTMLButtonElement | null>(null);
const statuses: ServiceSeasonStatus[] = [
    'active',
    'upcoming',
    'expired',
    'unscheduled',
];
const sorts: ServiceSeasonSort[] = ['service', 'season', 'date', 'status'];
const statusClasses: Record<ServiceSeasonStatus, string> = {
    active: 'pill--yes',
    upcoming: 'border-blue-200 bg-blue-100 text-blue-800',
    expired: 'pill--no',
    unscheduled: '',
};

function formatDate(date: string | null): string {
    const parsed = date ? parseIsoDate(date) : null;
    return parsed?.toLocaleDateString(localeTag.value) ?? '—';
}

function openAvailability(season: ServiceSeason, event: MouseEvent): void {
    availabilityTrigger.value = event.currentTarget as HTMLButtonElement;
    selected.value = season;
}

async function closeAvailability(): Promise<void> {
    selected.value = null;
    await nextTick();
    availabilityTrigger.value?.focus();
}

function reportsForSeason(season: ServiceSeason): string {
    return withQuery('/crm/reports', {
        service_id: season.service_id,
        from: season.date_start,
        to: season.date_end,
    });
}

function changePage(offset: number): void {
    if (props.pagination) emit('page', props.pagination.current_page + offset);
}
</script>

<template>
    <section class="tab-panel is-active">
        <div class="table-shell mb-4">
            <div
                class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6"
            >
                <div class="grid gap-1 text-sm font-medium">
                    <label for="season-filter-q">{{
                        t('common.search')
                    }}</label>
                    <input
                        id="season-filter-q"
                        v-model.trim="filters.q"
                        type="search"
                        maxlength="120"
                        :placeholder="t('services.search_seasons')"
                        class="min-w-0 rounded-md border border-input bg-background px-3 py-2"
                    />
                </div>
                <div class="grid gap-1 text-sm font-medium">
                    <label for="season-filter-service-id">{{
                        t('common.service')
                    }}</label>
                    <select
                        id="season-filter-service-id"
                        v-model="filters.service_id"
                        class="min-w-0 rounded-md border border-input bg-background px-3 py-2"
                    >
                        <option :value="0">
                            {{ t('common.all_services') }}
                        </option>
                        <option
                            v-for="service in services"
                            :key="service.id"
                            :value="service.id"
                        >
                            {{ service.title }}
                        </option>
                    </select>
                </div>
                <div class="grid gap-1 text-sm font-medium">
                    <label for="season-filter-date">{{
                        t('services.filter_date')
                    }}</label>
                    <input
                        id="season-filter-date"
                        v-model="filters.date"
                        type="date"
                        class="min-w-0 rounded-md border border-input bg-background px-3 py-2"
                    />
                </div>
                <div class="grid gap-1 text-sm font-medium">
                    <label for="season-filter-status">{{
                        t('common.status')
                    }}</label>
                    <select
                        id="season-filter-status"
                        v-model="filters.status"
                        class="rounded-md border border-input bg-background px-3 py-2"
                    >
                        <option value="">
                            {{ t('services.all_statuses') }}
                        </option>
                        <option
                            v-for="status in statuses"
                            :key="status"
                            :value="status"
                        >
                            {{ t(`services.status_${status}`) }}
                        </option>
                    </select>
                </div>
                <div class="grid gap-1 text-sm font-medium">
                    <label for="season-filter-sort">{{
                        t('services.sort_by')
                    }}</label>
                    <select
                        id="season-filter-sort"
                        v-model="filters.sort"
                        class="rounded-md border border-input bg-background px-3 py-2"
                    >
                        <option v-for="sort in sorts" :key="sort" :value="sort">
                            {{ t(`services.sort_${sort}`) }}
                        </option>
                    </select>
                </div>
                <div class="grid gap-1 text-sm font-medium">
                    <label for="season-filter-direction">{{
                        t('services.sort_order')
                    }}</label>
                    <select
                        id="season-filter-direction"
                        v-model="filters.direction"
                        class="rounded-md border border-input bg-background px-3 py-2"
                    >
                        <option value="asc">
                            {{ t('services.sort_asc') }}
                        </option>
                        <option value="desc">
                            {{ t('services.sort_desc') }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <div class="table-shell" :aria-busy="loading">
            <div v-if="loading" class="empty-state" role="status">
                {{ t('services.loading') }}
            </div>
            <div
                v-else-if="seasons.length === 0"
                class="empty-state"
                role="status"
            >
                {{ t('services.no_matching_seasons') }}
            </div>
            <div v-else class="table-wrap">
                <table
                    class="table-hover table-bordered table-stack-mobile customer-table table"
                    role="table"
                    :aria-label="t('services.service_seasons')"
                >
                    <thead role="rowgroup">
                        <tr role="row">
                            <th scope="col" role="columnheader">
                                {{ t('common.service') }}
                            </th>
                            <th scope="col" role="columnheader">
                                {{ t('services.col_season') }}
                            </th>
                            <th scope="col" role="columnheader">
                                {{ t('services.col_period') }}
                            </th>
                            <th scope="col" role="columnheader">
                                {{ t('services.service_price') }}
                            </th>
                            <th scope="col" role="columnheader">
                                {{ t('services.daily_capacity') }}
                            </th>
                            <th scope="col" role="columnheader">
                                {{ t('common.status') }}
                            </th>
                            <th scope="col" role="columnheader">
                                {{ t('common.actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody role="rowgroup">
                        <tr
                            v-for="season in seasons"
                            :key="season.id"
                            role="row"
                        >
                            <td role="cell" :data-label="t('common.service')">
                                <div>
                                    <strong>{{ season.service_name }}</strong>
                                    <p
                                        v-if="!season.service_active"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ t('services.inactive_service') }}
                                    </p>
                                </div>
                            </td>
                            <td
                                role="cell"
                                :data-label="t('services.col_season')"
                            >
                                {{ season.season_name }}
                            </td>
                            <td
                                role="cell"
                                :data-label="t('services.col_period')"
                            >
                                <div class="whitespace-nowrap">
                                    {{ formatDate(season.date_start) }} –
                                    {{ formatDate(season.date_end) }}
                                </div>
                            </td>
                            <td
                                role="cell"
                                :data-label="t('services.service_price')"
                            >
                                <span
                                    v-if="season.prices.length === 0"
                                    class="text-muted-foreground"
                                    >{{ t('services.price_missing') }}</span
                                >
                                <ul v-else class="space-y-1 text-sm">
                                    <li
                                        v-for="price in season.prices"
                                        :key="price.id"
                                    >
                                        <span class="whitespace-nowrap">{{
                                            formatEurBgnPairFromBgn(
                                                Number(price.price),
                                                localeTag,
                                            )
                                        }}</span>
                                        <span
                                            class="block text-xs text-muted-foreground"
                                            >{{
                                                t(
                                                    price.days === 1
                                                        ? 'services.price_one_day'
                                                        : 'services.price_days',
                                                    { count: price.days },
                                                )
                                            }}</span
                                        >
                                    </li>
                                </ul>
                            </td>
                            <td
                                role="cell"
                                :data-label="t('services.daily_capacity')"
                            >
                                {{ season.capacity }}
                            </td>
                            <td role="cell" :data-label="t('common.status')">
                                <span
                                    class="pill"
                                    :class="statusClasses[season.status]"
                                    >{{
                                        t(`services.status_${season.status}`)
                                    }}</span
                                >
                            </td>
                            <td role="cell" :data-label="t('common.actions')">
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="btn btn-outline btn-xs"
                                        @click="
                                            openAvailability(season, $event)
                                        "
                                    >
                                        {{ t('services.availability') }}
                                    </button>
                                    <a
                                        v-if="
                                            season.date_start && season.date_end
                                        "
                                        :href="reportsForSeason(season)"
                                        class="btn btn-outline btn-xs"
                                    >
                                        {{ t('common.reports') }}
                                    </a>
                                    <button
                                        v-if="canManage"
                                        type="button"
                                        class="btn btn-outline btn-xs"
                                        :disabled="loadingEdit"
                                        @click="emit('edit', season)"
                                    >
                                        {{ t('common.edit') }}
                                    </button>
                                    <button
                                        v-if="canManage"
                                        type="button"
                                        class="btn btn-ghost btn-xs"
                                        :disabled="deletingSlotId === season.id"
                                        @click="emit('remove', season.id)"
                                    >
                                        {{ t('common.delete') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="pagination && pagination.last_page > 1" class="pager">
                <button
                    type="button"
                    class="btn btn-ghost"
                    :disabled="loading || pagination.current_page <= 1"
                    @click="changePage(-1)"
                >
                    {{ t('common.previous') }}
                </button>
                <span>{{
                    t('common.page_of', {
                        current: pagination.current_page,
                        last: pagination.last_page,
                    })
                }}</span>
                <button
                    type="button"
                    class="btn btn-ghost"
                    :disabled="
                        loading ||
                        pagination.current_page >= pagination.last_page
                    "
                    @click="changePage(1)"
                >
                    {{ t('common.next') }}
                </button>
            </div>
        </div>

        <ServiceSeasonAvailability
            v-if="selected"
            :season="selected"
            :initial-date="filters.date"
            @close="closeAvailability"
        />
    </section>
</template>
