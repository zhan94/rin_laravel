<script setup lang="ts">
import {
    Dialog,
    DialogDescription,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';
import { useUiI18n } from '@/composables/useUiI18n';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatEur } from '@/modules/money/currency';
import { useCardsPage } from '@/pages/crm/composables/useCardsPage';
import { Head } from '@inertiajs/vue3';

const {
    t,
    translationFields,
    state,
    summaryLabel,
    linkedServicesCount,
    pricePackCount,
    formModalTitle,
    openCreateModal,
    openEditModal,
    closeFormModal,
    loadCards,
    addPriceRow,
    removePriceRow,
    submitCard,
    removeCard,
    goToPage,
} = useCardsPage();

const { localeTag } = useUiI18n();
</script>

<template>
    <Head :title="t('cards.head')" />

    <AppLayout>
        <div
            class="dashboard customer-ui customer-list catalog-list cards-list"
        >
            <div class="customer-shell">
                <section class="list-hero">
                    <div>
                        <div class="eyebrow">{{ t('cards.eyebrow') }}</div>
                        <h1>{{ t('cards.title') }}</h1>
                        <div class="subline">{{ t('cards.subtitle') }}</div>
                    </div>

                    <div class="hero-actions">
                        <button
                            type="button"
                            class="btn btn-solid"
                            @click="openCreateModal"
                        >
                            <span class="glyphicon glyphicon-plus" />
                            {{ t('cards.add_card') }}
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
                            t('cards.stat_cards')
                        }}</span>
                        <span class="stat-value">{{
                            state.pagination.total
                        }}</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">{{
                            t('cards.stat_linked_services')
                        }}</span>
                        <span class="stat-value">{{
                            linkedServicesCount
                        }}</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">{{
                            t('cards.stat_price_packs')
                        }}</span>
                        <span class="stat-value">{{ pricePackCount }}</span>
                    </div>
                </div>

                <section class="table-shell">
                    <div class="table-head">
                        <span class="badge badge--info">{{
                            t('cards.badge_catalog')
                        }}</span>
                    </div>

                    <div class="table-controls">
                        <div class="field">
                            <label for="cardSearch">{{
                                t('common.search')
                            }}</label>
                            <input
                                id="cardSearch"
                                v-model="state.query"
                                type="text"
                                :placeholder="t('cards.search_placeholder')"
                                class="form-control"
                            />
                        </div>
                        <div class="field">
                            <label for="cardServiceFilter">{{
                                t('cards.for_service')
                            }}</label>
                            <select
                                id="cardServiceFilter"
                                v-model.number="state.serviceFilter"
                                class="form-control"
                            >
                                <option :value="0">
                                    {{ t('cards.all_services') }}
                                </option>
                                <option
                                    v-for="service in state.services"
                                    :key="`filter-service-${service.id}`"
                                    :value="service.id"
                                >
                                    {{ service.title }}
                                </option>
                            </select>
                        </div>
                        <div class="table-meta">{{ summaryLabel }}</div>
                        <button
                            type="button"
                            class="btn btn-outline"
                            @click="loadCards"
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
                                    <th>{{ t('cards.col_name') }}</th>
                                    <th>{{ t('cards.col_service') }}</th>
                                    <th>{{ t('cards.col_prices') }}</th>
                                    <th>{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="state.loading">
                                    <td colspan="4" class="empty-row">
                                        {{ t('cards.loading') }}
                                    </td>
                                </tr>
                                <tr v-for="card in state.rows" :key="card.id">
                                    <td :data-label="t('cards.col_name')">
                                        <p class="font-medium">
                                            {{ card.title || '-' }}
                                        </p>
                                    </td>
                                    <td :data-label="t('cards.col_service')">
                                        {{ card.service?.title ?? '-' }}
                                    </td>
                                    <td :data-label="t('cards.col_prices')">
                                        <div
                                            class="price-stack price-stack--smooth price-stack--toggle"
                                        >
                                            <input
                                                v-if="card.prices.length > 2"
                                                :id="`card-price-toggle-${card.id}`"
                                                type="checkbox"
                                                class="price-toggle"
                                            />
                                            <div
                                                v-for="(
                                                    price, index
                                                ) in card.prices"
                                                :key="price.id"
                                                class="price-item"
                                                :class="{
                                                    'price-item--extra':
                                                        card.prices.length >
                                                            2 && index >= 2,
                                                }"
                                            >
                                                <span class="price-days"
                                                    >{{ price.days }}
                                                    {{
                                                        price.days === 1
                                                            ? t('cards.day')
                                                            : t('cards.days')
                                                    }}</span
                                                >
                                                <span class="price-sep">—</span>
                                                <span class="price-amount">{{
                                                    formatEur(
                                                        price.price_eur,
                                                        localeTag,
                                                    )
                                                }}</span>
                                            </div>
                                            <label
                                                v-if="card.prices.length > 2"
                                                :for="`card-price-toggle-${card.id}`"
                                                class="price-compact-btn"
                                            >
                                                <span class="price-more">{{
                                                    t('cards.more')
                                                }}</span>
                                                <span class="price-less">{{
                                                    t('cards.less')
                                                }}</span>
                                            </label>
                                            <p
                                                v-if="card.prices.length === 0"
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
                                                @click="openEditModal(card)"
                                            >
                                                <span
                                                    class="glyphicon glyphicon-edit"
                                                />
                                            </button>
                                            <button
                                                type="button"
                                                class="icon-btn icon-btn--danger disabled:opacity-60"
                                                :disabled="
                                                    state.deletingId === card.id
                                                "
                                                :title="t('common.delete')"
                                                @click="removeCard(card.id)"
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
                                    <td colspan="4" class="empty-row">
                                        {{ t('cards.no_rows') }}
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
                            {{ t('cards.prev') }}
                        </button>
                        <span id="servicePageInfo">
                            {{
                                t('cards.page_of', {
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
                            {{ t('cards.next') }}
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
                            {{ t('cards.modal_desc') }}
                        </DialogDescription>
                    </DialogHeader>

                    <form class="mt-2 grid gap-3" @submit.prevent="submitCard">
                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                            <div
                                v-for="field in translationFields"
                                :key="`card-translation-${field.locale}`"
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
                            <select
                                v-model.number="state.form.service"
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                required
                                :disabled="state.loadingServices"
                            >
                                <option :value="0" disabled>
                                    {{ t('services.field_select_service') }}
                                </option>
                                <option
                                    v-for="service in state.services"
                                    :key="service.id"
                                    :value="service.id"
                                >
                                    {{ service.title }}
                                </option>
                            </select>
                        </div>

                        <div class="rounded-md border border-input/70 p-3">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-sm font-medium">
                                    {{ t('cards.price_rows') }}
                                </p>
                                <button
                                    type="button"
                                    class="rounded-md border border-input px-3 py-1 text-xs"
                                    @click="addPriceRow"
                                >
                                    {{ t('cards.add_row') }}
                                </button>
                            </div>

                            <div
                                v-for="(row, index) in state.form.prices"
                                :key="`card-price-${index}`"
                                class="mb-2 grid gap-2 md:grid-cols-[120px_1fr_auto]"
                            >
                                <input
                                    v-model="row.days"
                                    type="number"
                                    min="1"
                                    :placeholder="t('cards.field_days')"
                                    class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                />
                                <input
                                    v-model="row.price_eur"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :placeholder="t('cards.field_price_eur')"
                                    class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                />
                                <button
                                    type="button"
                                    class="rounded-md border border-input px-3 py-2 text-sm"
                                    @click="removePriceRow(index)"
                                >
                                    {{ t('cards.remove') }}
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
                                :disabled="
                                    state.submitting || state.loadingServices
                                "
                            >
                                {{
                                    state.submitting
                                        ? state.formMode === 'edit'
                                            ? t('cards.saving')
                                            : t('cards.adding')
                                        : state.formMode === 'edit'
                                          ? t('cards.save_card')
                                          : t('cards.add_card')
                                }}
                            </button>
                        </div>
                    </form>
                </DialogScrollContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
