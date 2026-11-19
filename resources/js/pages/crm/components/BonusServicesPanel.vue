<script setup lang="ts">
import { formatGermanDate } from '@/modules/calendar/dateDisplay';
import type { BonusGroup } from '@/pages/crm/composables/useServicesPage';

type Translate = (key: string, params?: Record<string, unknown>) => string;

defineProps<{
    groups: BonusGroup[];
    loading: boolean;
    t: Translate;
}>();
</script>

<template>
    <section class="tab-panel is-active">
        <div v-if="loading" class="empty-state">
            {{ t('services.loading_bonus') }}
        </div>
        <div v-else-if="groups.length === 0" class="empty-state">
            {{ t('services.no_bonus') }}
        </div>
        <div v-else class="services-block">
            <article
                v-for="group in groups"
                :key="group.title"
                class="table-shell"
            >
                <div class="table-head">
                    <span class="badge badge--teal">{{ group.title }}</span>
                    <span class="filters-note">{{
                        t('services.note_bonus_routes')
                    }}</span>
                </div>
                <div class="table-wrap">
                    <table
                        class="table-hover table-bordered table-responsive customer-table table"
                    >
                        <thead>
                            <tr>
                                <th>{{ t('services.col_title') }}</th>
                                <th>{{ t('services.col_period') }}</th>
                                <th>{{ t('services.col_location') }}</th>
                                <th>{{ t('services.col_base_price') }}</th>
                                <th>{{ t('services.col_price_rows') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in group.rows" :key="row.id">
                                <td>{{ row.title || '-' }}</td>
                                <td>
                                    {{ formatGermanDate(row.date_from, '-') }} -
                                    {{ formatGermanDate(row.date_to, '-') }}
                                </td>
                                <td>
                                    {{ row.location_from }} ->
                                    {{ row.location_to }}
                                </td>
                                <td>
                                    {{
                                        row.price_eur !== null
                                            ? `${row.price_eur.toFixed(2)} EUR`
                                            : '-'
                                    }}
                                </td>
                                <td>{{ row.prices.length }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>
        </div>
    </section>
</template>
