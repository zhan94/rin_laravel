<script setup lang="ts">
import { useUiI18n } from '@/composables/useUiI18n';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatGermanDate } from '@/modules/calendar/dateDisplay';
import { formatEurBgnPairFromBgn } from '@/modules/money/currency';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    season: { label: string; start: string; end: string };
    today: string;
    canCreate: boolean;
    customers: { total: number; active: number };
    stats: {
        records: number;
        clients: number;
        dates: number;
        sum: number;
        paid: number;
        due: number;
        today: number;
    };
    daily: {
        id: number;
        customer: string | null;
        service: string | null;
        location: string | null;
        contact: string | null;
    }[];
    byService: {
        id: number;
        title: string;
        dates: number;
        records: number;
        clients: number;
    }[];
}>();
const { t, localeTag } = useUiI18n();

function number(value: number): string {
    return new Intl.NumberFormat(localeTag.value).format(value);
}

const seasonCards = computed(() => [
    {
        title: t('dashboard.stat_season_records'),
        value: number(props.stats.records),
    },
    {
        title: t('dashboard.stat_season_clients'),
        value: number(props.stats.clients),
    },
    {
        title: t('dashboard.stat_season_dates'),
        value: number(props.stats.dates),
    },
    {
        title: t('dashboard.stat_season_sum'),
        value: formatEurBgnPairFromBgn(props.stats.sum, localeTag.value),
    },
    {
        title: t('dashboard.stat_paid'),
        value: formatEurBgnPairFromBgn(props.stats.paid, localeTag.value),
    },
    {
        title: t('dashboard.stat_due'),
        value: formatEurBgnPairFromBgn(props.stats.due, localeTag.value),
    },
    {
        title: t('dashboard.stat_today_records'),
        value: number(props.stats.today),
    },
]);
</script>

<template>
    <Head :title="t('dashboard.head')" />

    <AppLayout>
        <div class="dashboard customer-ui customer-list">
            <div class="customer-shell">
                <section class="list-hero">
                    <div>
                        <p class="eyebrow">{{ t('dashboard.eyebrow') }}</p>
                        <h1>
                            {{ t('dashboard.title', { season: season.label }) }}
                        </h1>
                        <p class="subline">
                            {{ t('dashboard.period') }}:
                            <strong>{{
                                formatGermanDate(season.start)
                            }}</strong>
                            -
                            <strong>{{ formatGermanDate(season.end) }}</strong>
                            · {{ t('dashboard.today') }}:
                            <strong>{{ formatGermanDate(today) }}</strong> ·
                            {{ t('dashboard.clients') }}:
                            <strong>{{ number(customers.active) }}</strong> /
                            {{ number(customers.total) }}
                        </p>
                    </div>
                    <div class="hero-actions">
                        <Link
                            v-if="canCreate"
                            href="/crm/bookings"
                            class="btn btn-solid"
                        >
                            + {{ t('bookings.title') }}
                        </Link>
                        <Link href="/crm/customers" class="btn btn-outline">{{
                            t('dashboard.action_database')
                        }}</Link>
                        <Link href="/crm/reports" class="btn btn-ghost">{{
                            t('dashboard.action_reports')
                        }}</Link>
                    </div>
                </section>

                <section class="stat-grid">
                    <article
                        v-for="card in seasonCards"
                        :key="card.title"
                        class="stat-card"
                    >
                        <span class="stat-label">{{ card.title }}</span>
                        <span class="stat-value">{{ card.value }}</span>
                    </article>
                </section>

                <section class="dashboard-grid">
                    <article class="table-shell dashboard-table">
                        <div class="table-head">
                            <span class="badge badge--info">{{
                                t('dashboard.today_badge')
                            }}</span>
                            <span class="filters-note">{{
                                formatGermanDate(today)
                            }}</span>
                        </div>
                        <div class="table-wrap">
                            <table
                                class="table-hover table-bordered table-responsive customer-table table"
                            >
                                <thead>
                                    <tr>
                                        <th scope="col">
                                            {{ t('dashboard.col_client') }}
                                        </th>
                                        <th scope="col">
                                            {{ t('dashboard.col_service') }}
                                        </th>
                                        <th scope="col">
                                            {{ t('dashboard.col_location') }}
                                        </th>
                                        <th scope="col" class="text-center">
                                            {{ t('dashboard.col_contact') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in daily" :key="row.id">
                                        <td>{{ row.customer || '—' }}</td>
                                        <td>{{ row.service || '—' }}</td>
                                        <td>{{ row.location || '—' }}</td>
                                        <td class="text-center">
                                            {{ row.contact || '—' }}
                                        </td>
                                    </tr>
                                    <tr v-if="daily.length === 0">
                                        <td colspan="4" class="empty-row">
                                            {{
                                                t('dashboard.no_today_records')
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p
                            v-if="stats.today > daily.length"
                            class="filters-note"
                        >
                            {{
                                t('dashboard.today_truncated', {
                                    shown: number(daily.length),
                                    total: number(stats.today),
                                })
                            }}
                            <Link href="/crm/reports">{{
                                t('dashboard.action_reports')
                            }}</Link>
                        </p>
                    </article>

                    <article class="table-shell">
                        <div class="table-head">
                            <span class="badge badge--info">{{
                                t('dashboard.by_services_badge')
                            }}</span>
                            <span class="filters-note">{{
                                t('dashboard.by_services_note')
                            }}</span>
                        </div>
                        <div class="table-wrap">
                            <table
                                class="table-hover table-bordered table-responsive customer-table table"
                            >
                                <thead>
                                    <tr>
                                        <th scope="col">
                                            {{ t('dashboard.col_service') }}
                                        </th>
                                        <th scope="col" class="text-center">
                                            {{ t('dashboard.col_dates') }}
                                        </th>
                                        <th scope="col" class="text-center">
                                            {{ t('dashboard.col_records') }}
                                        </th>
                                        <th scope="col" class="text-center">
                                            {{ t('dashboard.col_clients') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="service in byService"
                                        :key="service.id"
                                    >
                                        <td>{{ service.title }}</td>
                                        <td class="text-center">
                                            {{ number(service.dates) }}
                                        </td>
                                        <td class="text-center">
                                            {{ number(service.records) }}
                                        </td>
                                        <td class="text-center">
                                            {{ number(service.clients) }}
                                        </td>
                                    </tr>
                                    <tr v-if="byService.length === 0">
                                        <td colspan="4" class="empty-row">
                                            {{
                                                t(
                                                    'dashboard.no_active_services',
                                                )
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </article>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
