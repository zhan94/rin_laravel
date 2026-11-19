<script setup lang="ts">
import { formatGermanDate } from '@/modules/calendar/dateDisplay';
import type { SeasonSummary } from '@/modules/seasons/types';
import type { useSeasonsPage } from '@/pages/crm/composables/useSeasonsPage';

type SeasonPage = ReturnType<typeof useSeasonsPage>;

defineProps<{
    formatCurrency: SeasonPage['formatCurrency'];
    selectedSeason: SeasonSummary | null;
    showDatesColumn: boolean;
    state: SeasonPage['state'];
    t: SeasonPage['t'];
}>();
</script>

<template>
    <section class="table-shell">
        <div class="table-head">
            <span class="badge badge--info">{{ t('seasons.details') }}</span>
            <span v-if="selectedSeason" class="filters-note">
                {{ selectedSeason.label }} ·
                {{ selectedSeason.date_range }}
            </span>
        </div>

        <div v-if="selectedSeason" class="stat-grid season-stats">
            <article class="stat-card">
                <span class="stat-label">{{ t('seasons.stat_records') }}</span>
                <span class="stat-value">{{
                    selectedSeason.bookings_count
                }}</span>
            </article>
            <article class="stat-card">
                <span class="stat-label">{{ t('seasons.stat_clients') }}</span>
                <span class="stat-value">{{
                    selectedSeason.customers_count
                }}</span>
            </article>
            <article v-if="state.selectedType === 'service'" class="stat-card">
                <span class="stat-label">{{ t('seasons.stat_dates') }}</span>
                <span class="stat-value">{{
                    selectedSeason.dates_count ?? 0
                }}</span>
            </article>
            <article class="stat-card">
                <span class="stat-label">{{ t('seasons.stat_sum') }}</span>
                <span class="stat-value">{{
                    formatCurrency(selectedSeason.sum_total)
                }}</span>
            </article>
            <article class="stat-card">
                <span class="stat-label">{{ t('seasons.stat_paid') }}</span>
                <span class="stat-value">{{
                    formatCurrency(selectedSeason.paid_total)
                }}</span>
            </article>
            <article class="stat-card">
                <span class="stat-label">{{
                    t('seasons.stat_remaining')
                }}</span>
                <span class="stat-value">{{
                    formatCurrency(selectedSeason.due_total)
                }}</span>
            </article>
        </div>

        <div class="table-wrap">
            <table
                class="table-hover table-bordered table-responsive customer-table table"
            >
                <thead>
                    <tr>
                        <th>{{ t('seasons.col_client') }}</th>
                        <th v-if="showDatesColumn">
                            {{ t('seasons.col_dates') }}
                        </th>
                        <th>{{ t('seasons.col_location') }}</th>
                        <th>{{ t('seasons.col_payment') }}</th>
                        <th class="text-center">
                            {{ t('seasons.col_contact') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="state.loadingSelectSeason">
                        <td
                            :colspan="showDatesColumn ? 5 : 4"
                            class="empty-row"
                        >
                            {{ t('seasons.loading_records') }}
                        </td>
                    </tr>
                    <tr
                        v-else-if="
                            (state.overview?.season_bookings.length ?? 0) === 0
                        "
                    >
                        <td
                            :colspan="showDatesColumn ? 5 : 4"
                            class="empty-row"
                        >
                            {{ t('seasons.no_records') }}
                        </td>
                    </tr>
                    <tr
                        v-for="row in state.overview?.season_bookings ?? []"
                        v-else
                        :key="row.booking_id"
                    >
                        <td>{{ row.name }}</td>
                        <td v-if="showDatesColumn">
                            <div class="chip-row">
                                <span
                                    v-if="row.dates.length === 0"
                                    class="chip chip--muted"
                                    >{{ t('seasons.no_dates') }}</span
                                >
                                <span
                                    v-for="date in row.dates"
                                    v-else
                                    :key="`${row.booking_id}-${date}`"
                                    class="chip"
                                >
                                    {{ formatGermanDate(date) }}
                                </span>
                            </div>
                        </td>
                        <td>{{ row.location || '—' }}</td>
                        <td>
                            <span
                                class="pill"
                                :class="
                                    row.due > 0.01 ? 'pill--no' : 'pill--yes'
                                "
                            >
                                {{
                                    row.due > 0.01
                                        ? t('seasons.payment_remaining', {
                                              amount: formatCurrency(row.due),
                                          })
                                        : t('seasons.payment_paid')
                                }}
                            </span>
                            <div class="season-payment text-muted">
                                {{ formatCurrency(row.paid) }} /
                                {{ formatCurrency(row.sum_paid) }}
                            </div>
                        </td>
                        <td class="text-center">
                            <a
                                v-if="row.phone"
                                :href="`tel:${row.phone}`"
                                class="link"
                                >{{ row.phone }}</a
                            >
                            <span v-else>—</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
