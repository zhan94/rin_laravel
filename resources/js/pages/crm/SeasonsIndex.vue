<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import SeasonAdminForms from '@/pages/crm/components/SeasonAdminForms.vue';
import SeasonCardsGrid from '@/pages/crm/components/SeasonCardsGrid.vue';
import SeasonDetailsPanel from '@/pages/crm/components/SeasonDetailsPanel.vue';
import SeasonSelectorBar from '@/pages/crm/components/SeasonSelectorBar.vue';
import { provideSeasonPageContext } from '@/pages/crm/composables/seasonPageContext';
import { useSeasonsPage } from '@/pages/crm/composables/useSeasonsPage';
import { useAuthStore } from '@/stores/authStore';
import { Head } from '@inertiajs/vue3';

const seasonPage = useSeasonsPage();
const auth = useAuthStore();
provideSeasonPageContext(seasonPage);

const {
    t,
    state,
    selectedSeason,
    showDatesColumn,
    formatCurrency,
    switchType,
    selectService,
    selectBonusTitle,
    selectSeason,
} = seasonPage;
</script>

<template>
    <Head :title="t('seasons.head')" />

    <AppLayout>
        <div class="dashboard customer-ui customer-list seasons-ui">
            <div class="customer-shell">
                <section class="list-hero">
                    <div>
                        <p class="eyebrow">{{ t('seasons.eyebrow') }}</p>
                        <h1>{{ t('seasons.title') }}</h1>
                        <p class="subline">{{ t('seasons.subtitle') }}</p>
                    </div>
                    <div class="hero-actions">
                        <a href="/crm/reports" class="btn btn-ghost">{{
                            t('seasons.reports')
                        }}</a>
                    </div>
                </section>

                <div v-if="state.errors.length > 0" class="season-errors">
                    <p
                        v-for="error in state.errors"
                        :key="`${error.code}-${error.message}`"
                    >
                        {{ error.message }}
                    </p>
                </div>

                <SeasonSelectorBar
                    :select-bonus-title="selectBonusTitle"
                    :select-service="selectService"
                    :state="state"
                    :switch-type="switchType"
                    :t="t"
                />

                <SeasonAdminForms
                    v-if="
                        state.selectedType === 'service' &&
                        auth.can('manageBookings')
                    "
                />

                <SeasonCardsGrid
                    :format-currency="formatCurrency"
                    :select-season="selectSeason"
                    :state="state"
                    :t="t"
                />

                <SeasonDetailsPanel
                    :format-currency="formatCurrency"
                    :selected-season="selectedSeason"
                    :show-dates-column="showDatesColumn"
                    :state="state"
                    :t="t"
                />
            </div>
        </div>
    </AppLayout>
</template>
