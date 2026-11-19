<script setup lang="ts">
import GermanDateInput from '@/components/GermanDateInput.vue';
import {
    Dialog,
    DialogDescription,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatGermanDate } from '@/modules/calendar/dateDisplay';
import { useBonusPage } from '@/pages/crm/composables/useBonusPage';
import { Head } from '@inertiajs/vue3';

const {
    t,
    translationFields,
    state,
    summaryLabel,
    pricePackCount,
    formModalTitle,
    openCreateModal,
    openEditModal,
    closeFormModal,
    loadBonusRows,
    addPriceRow,
    removePriceRow,
    submitBonus,
    removeBonus,
    goToPage,
} = useBonusPage();
</script>

<template>
    <Head :title="t('bonus.head')" />

    <AppLayout>
        <div
            class="dashboard customer-ui customer-list catalog-list bonus-list"
        >
            <div class="customer-shell">
                <section class="list-hero">
                    <div>
                        <div class="eyebrow">{{ t('bonus.eyebrow') }}</div>
                        <h1>{{ t('bonus.title') }}</h1>
                        <div class="subline">{{ t('bonus.subtitle') }}</div>
                    </div>

                    <div class="hero-actions">
                        <button
                            type="button"
                            class="btn btn-solid"
                            @click="openCreateModal"
                        >
                            <span class="glyphicon glyphicon-plus" />
                            {{ t('bonus.add_service') }}
                        </button>
                    </div>
                </section>

                <div v-if="state.errors.length > 0" class="alert alert-danger">
                    <p
                        v-for="error in state.errors"
                        :key="`${error.code}-${error.message}`"
                    >
                        {{ error.message }}
                    </p>
                </div>

                <div class="stat-grid">
                    <div class="stat-card">
                        <span class="stat-label">{{
                            t('bonus.stat_services')
                        }}</span>
                        <span class="stat-value">{{
                            state.pagination.total
                        }}</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">{{
                            t('bonus.stat_price_packs')
                        }}</span>
                        <span class="stat-value">{{ pricePackCount }}</span>
                    </div>
                </div>

                <section class="table-shell">
                    <div class="table-head">
                        <span class="badge badge--info">{{
                            t('bonus.badge_active')
                        }}</span>
                        <span class="filters-note">{{
                            t('bonus.note_registrations')
                        }}</span>
                    </div>

                    <div class="table-controls">
                        <div class="field">
                            <label for="bonusSearch">{{
                                t('common.search')
                            }}</label>
                            <input
                                id="bonusSearch"
                                v-model="state.query"
                                type="text"
                                :placeholder="t('bonus.search_placeholder')"
                                class="form-control"
                            />
                        </div>
                        <div class="table-meta">{{ summaryLabel }}</div>
                        <button
                            type="button"
                            class="btn btn-outline"
                            @click="loadBonusRows"
                        >
                            {{ t('common.search') }}
                        </button>
                    </div>

                    <div class="table-wrap">
                        <table
                            class="table-hover table-bordered table-responsive customer-table table-stack-mobile table"
                        >
                            <thead>
                                <tr>
                                    <th>{{ t('bonus.col_name') }}</th>
                                    <th>{{ t('bonus.col_period') }}</th>
                                    <th>{{ t('bonus.col_pickup') }}</th>
                                    <th>{{ t('bonus.col_dropoff') }}</th>
                                    <th>{{ t('bonus.col_prices') }}</th>
                                    <th>{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="state.loading">
                                    <td colspan="6" class="empty-row">
                                        {{ t('bonus.loading') }}
                                    </td>
                                </tr>
                                <tr v-for="bonus in state.rows" :key="bonus.id">
                                    <td :data-label="t('bonus.col_name')">
                                        <p class="font-medium">
                                            {{ bonus.title || '-' }}
                                        </p>
                                    </td>
                                    <td :data-label="t('bonus.col_period')">
                                        {{
                                            formatGermanDate(
                                                bonus.date_from,
                                                '-',
                                            )
                                        }}
                                        ->
                                        {{
                                            formatGermanDate(bonus.date_to, '-')
                                        }}
                                    </td>
                                    <td :data-label="t('bonus.col_pickup')">
                                        {{ bonus.location_from }}
                                    </td>
                                    <td :data-label="t('bonus.col_dropoff')">
                                        {{ bonus.location_to }}
                                    </td>
                                    <td :data-label="t('bonus.col_prices')">
                                        <div class="price-stack">
                                            <span
                                                v-for="price in bonus.prices"
                                                :key="price.id"
                                                class="price-pill"
                                            >
                                                {{
                                                    t('bonus.price_for_days', {
                                                        days: price.days,
                                                        eur: price.price_eur.toFixed(
                                                            2,
                                                        ),
                                                        bgn: price.price_bgn.toFixed(
                                                            2,
                                                        ),
                                                    })
                                                }}
                                            </span>
                                            <p
                                                v-if="bonus.prices.length === 0"
                                                class="text-muted-foreground"
                                            >
                                                -
                                            </p>
                                        </div>
                                    </td>
                                    <td
                                        class="text-center"
                                        :data-label="t('common.actions')"
                                    >
                                        <div class="action-group">
                                            <button
                                                type="button"
                                                class="icon-btn icon-btn--primary"
                                                :title="t('common.edit')"
                                                @click="openEditModal(bonus)"
                                            >
                                                <span
                                                    class="glyphicon glyphicon-edit"
                                                />
                                            </button>
                                            <button
                                                type="button"
                                                class="icon-btn icon-btn--danger disabled:opacity-60"
                                                :disabled="
                                                    state.deletingId ===
                                                    bonus.id
                                                "
                                                :title="t('common.delete')"
                                                @click="removeBonus(bonus.id)"
                                            >
                                                <span
                                                    class="glyphicon glyphicon-trash"
                                                />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr
                                    v-if="
                                        !state.loading &&
                                        state.rows.length === 0
                                    "
                                >
                                    <td colspan="6" class="empty-row">
                                        {{ t('bonus.no_rows') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pager">
                        <button
                            type="button"
                            class="btn btn-ghost"
                            :disabled="state.pagination.current_page <= 1"
                            @click="goToPage(state.pagination.current_page - 1)"
                        >
                            {{ t('bonus.prev') }}
                        </button>
                        <span id="servicePageInfo">
                            {{
                                t('bonus.page_of', {
                                    current: state.pagination.current_page,
                                    last: state.pagination.last_page,
                                })
                            }}
                        </span>
                        <button
                            type="button"
                            class="btn btn-ghost"
                            :disabled="
                                state.pagination.current_page >=
                                state.pagination.last_page
                            "
                            @click="goToPage(state.pagination.current_page + 1)"
                        >
                            {{ t('bonus.next') }}
                        </button>
                    </div>
                </section>
            </div>

            <Dialog
                :open="state.formModalOpen"
                @update:open="state.formModalOpen = $event"
            >
                <DialogScrollContent class="sm:max-w-4xl">
                    <DialogHeader>
                        <DialogTitle>{{ formModalTitle }}</DialogTitle>
                        <DialogDescription>
                            {{ t('bonus.modal_desc') }}
                        </DialogDescription>
                    </DialogHeader>

                    <form class="mt-2 grid gap-3" @submit.prevent="submitBonus">
                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <div
                                v-for="field in translationFields"
                                :key="`bonus-translation-${field.locale}`"
                                class="field"
                            >
                                <label class="text-sm font-medium">{{
                                    t('services.field_title_locale', {
                                        label: field.label,
                                    })
                                }}</label>
                                <input
                                    v-model="
                                        state.form.translations[field.locale]
                                    "
                                    type="text"
                                    :placeholder="
                                        t('services.field_title_locale', {
                                            label: field.label,
                                        })
                                    "
                                    class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                />
                            </div>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                            <input
                                v-model="state.form.location_from"
                                type="text"
                                :placeholder="t('bonus.field_location_from')"
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                required
                            />
                            <input
                                v-model="state.form.location_to"
                                type="text"
                                :placeholder="t('bonus.field_location_to')"
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                required
                            />
                        </div>

                        <div class="grid gap-3 md:grid-cols-3">
                            <GermanDateInput
                                v-model="state.form.date_from"
                                :aria-label="t('common.from')"
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                            />
                            <GermanDateInput
                                v-model="state.form.date_to"
                                :aria-label="t('common.to')"
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                            />
                            <input
                                v-model="state.form.price_eur"
                                type="number"
                                step="0.01"
                                min="0"
                                :placeholder="t('bonus.field_base_price_eur')"
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                            />
                        </div>

                        <textarea
                            v-model="state.form.description"
                            :placeholder="t('bonus.field_description')"
                            class="min-h-[88px] rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />

                        <div class="rounded-md border border-input/70 p-3">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-sm font-medium">
                                    {{ t('bonus.price_rows') }}
                                </p>
                                <button
                                    type="button"
                                    class="rounded-md border border-input px-3 py-1 text-xs"
                                    @click="addPriceRow"
                                >
                                    {{ t('bonus.add_row') }}
                                </button>
                            </div>

                            <div
                                v-for="(row, index) in state.form.prices"
                                :key="`bonus-price-${index}`"
                                class="mb-2 grid gap-2 md:grid-cols-[120px_1fr_auto]"
                            >
                                <input
                                    v-model="row.days"
                                    type="number"
                                    min="1"
                                    :placeholder="t('bonus.field_days')"
                                    class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                />
                                <input
                                    v-model="row.price_eur"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :placeholder="t('bonus.field_price_eur')"
                                    class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                />
                                <button
                                    type="button"
                                    class="rounded-md border border-input px-3 py-2 text-sm"
                                    @click="removePriceRow(index)"
                                >
                                    {{ t('bonus.remove') }}
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2">
                            <button
                                type="button"
                                class="rounded-md border border-input px-4 py-2 text-sm"
                                :disabled="state.submitting"
                                @click="closeFormModal"
                            >
                                {{ t('common.cancel') }}
                            </button>
                            <button
                                type="submit"
                                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-60"
                                :disabled="state.submitting"
                            >
                                {{
                                    state.submitting
                                        ? state.formMode === 'edit'
                                            ? t('bonus.saving')
                                            : t('bonus.adding')
                                        : state.formMode === 'edit'
                                          ? t('bonus.save_service')
                                          : t('bonus.add_service')
                                }}
                            </button>
                        </div>
                    </form>
                </DialogScrollContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
