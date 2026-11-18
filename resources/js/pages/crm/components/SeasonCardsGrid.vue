<script setup lang="ts">
import type { useSeasonsPage } from '@/pages/crm/composables/useSeasonsPage';

type SeasonPage = ReturnType<typeof useSeasonsPage>;

defineProps<{
    formatCurrency: SeasonPage['formatCurrency'];
    selectSeason: SeasonPage['selectSeason'];
    state: SeasonPage['state'];
    t: SeasonPage['t'];
}>();
</script>

<template>
    <section class="season-grid">
        <div v-if="state.loading" class="empty-state">
            {{ t('seasons.loading') }}
        </div>
        <div
            v-else-if="(state.overview?.seasons.length ?? 0) === 0"
            class="empty-state"
        >
            {{ t('seasons.no_rows') }}
        </div>

        <button
            v-for="season in state.overview?.seasons ?? []"
            :key="season.id"
            type="button"
            class="season-card"
            :class="season.is_selected ? 'is-active' : ''"
            :disabled="state.loadingSelectSeason"
            @click="selectSeason(season.id)"
        >
            <span class="season-title">{{ season.label }}</span>
            <span
                v-if="state.selectedType === 'bonus' && season.title"
                class="season-subtitle"
            >
                {{ season.title }}
            </span>
            <span class="season-range">{{ season.date_range }}</span>
            <div class="season-meta">
                <span
                    v-if="state.selectedType === 'service'"
                    class="pill pill--yes"
                >
                    {{
                        t('seasons.dates', {
                            count: season.dates_count ?? 0,
                        })
                    }}
                </span>
                <span class="pill pill--yes">{{
                    t('seasons.records', {
                        count: season.bookings_count,
                    })
                }}</span>
                <span class="pill pill--no">{{
                    t('seasons.due', {
                        amount: formatCurrency(season.due_total),
                    })
                }}</span>
                <span class="pill pill--yes">{{
                    t('seasons.clients', {
                        count: season.customers_count,
                    })
                }}</span>
            </div>
            <div class="season-money">
                <span>{{
                    t('seasons.sum', {
                        amount: formatCurrency(season.sum_total),
                    })
                }}</span>
                <span>{{
                    t('seasons.paid', {
                        amount: formatCurrency(season.paid_total),
                    })
                }}</span>
            </div>
            <span
                v-if="state.selectedType === 'bonus' && season.location"
                class="season-location"
            >
                {{ season.location }}
            </span>
        </button>
    </section>
</template>
