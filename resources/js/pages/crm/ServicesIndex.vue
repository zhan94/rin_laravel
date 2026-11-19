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
import { formatGermanDate } from '@/modules/calendar/dateDisplay';
import BonusServicesPanel from '@/pages/crm/components/BonusServicesPanel.vue';
import ServiceSchedulePanel from '@/pages/crm/components/ServiceSchedulePanel.vue';
import { useServicesPage } from '@/pages/crm/composables/useServicesPage';
import { Head, Link } from '@inertiajs/vue3';

const {
    t,
    SLOT_WEEKDAY_LABELS,
    translationFields,
    state,
    canManage,
    loadSeasons,
    serviceModalTitle,
    slotModalTitle,
    slotSelectedDates,
    slotModalBlockedDateSet,
    slotCalendarMonthLabel,
    slotCalendarCells,
    bonusGroups,
    serviceLabelById,
    openServiceNamesModal,
    openEditServiceModal,
    submitServiceForm,
    moveSlotCalendarMonth,
    toggleSlotCalendarDate,
    removeSlotSelectedDate,
    clearSlotSelectedDates,
    openEditSlotModal,
    submitSlotForm,
    removeSlot,
} = useServicesPage();

const { formatNumber } = useUiI18n();
</script>

<template>
    <Head :title="t('services.head')" />

    <AppLayout>
        <div
            class="dashboard customer-ui customer-list catalog-list service-list services-ui"
        >
            <div class="customer-shell">
                <section class="list-hero">
                    <div>
                        <p class="eyebrow">{{ t('services.eyebrow') }}</p>
                        <h1>{{ t('services.title') }}</h1>
                        <p class="subline">{{ t('services.subtitle') }}</p>
                    </div>
                    <div v-if="canManage" class="hero-actions">
                        <Link
                            href="/crm/services/periods/create"
                            class="btn btn-solid"
                        >
                            {{ t('services.add_dates') }}
                        </Link>
                        <button
                            type="button"
                            class="btn btn-outline"
                            @click="openServiceNamesModal"
                        >
                            {{ t('services.names') }}
                        </button>
                    </div>
                </section>

                <div
                    v-if="state.errors.length > 0 && !state.slotModalOpen"
                    class="services-errors"
                    role="alert"
                >
                    <p
                        v-for="error in state.errors"
                        :key="`${error.code}-${error.message}`"
                    >
                        {{ error.message }}
                    </p>
                </div>

                <section class="stat-grid services-stats">
                    <article class="stat-card">
                        <span class="stat-label">{{
                            t('services.stat_services')
                        }}</span>
                        <span class="stat-value">{{
                            formatNumber(state.counts.services)
                        }}</span>
                    </article>
                    <article class="stat-card">
                        <span class="stat-label">{{
                            t('services.stat_periods')
                        }}</span>
                        <span class="stat-value">{{
                            formatNumber(state.counts.seasons)
                        }}</span>
                    </article>
                    <article class="stat-card">
                        <span class="stat-label">{{
                            t('services.stat_dates')
                        }}</span>
                        <span class="stat-value">{{
                            formatNumber(state.counts.dates)
                        }}</span>
                    </article>
                </section>

                <section v-if="canManage" class="tab-bar services-tab-bar">
                    <button
                        type="button"
                        class="tab-btn"
                        :class="state.activeTab === 'main' ? 'is-active' : ''"
                        @click="state.activeTab = 'main'"
                    >
                        {{ t('services.service_seasons') }}
                    </button>
                    <button
                        type="button"
                        class="tab-btn"
                        :class="state.activeTab === 'bonus' ? 'is-active' : ''"
                        @click="state.activeTab = 'bonus'"
                    >
                        {{ t('services.tab_bonus') }}
                    </button>
                </section>

                <ServiceSchedulePanel
                    v-if="state.activeTab === 'main'"
                    v-model="state.seasonFilters"
                    :seasons="state.seasons"
                    :services="state.services"
                    :pagination="state.seasonPagination"
                    :loading="state.loadingOverview"
                    :can-manage="canManage"
                    :loading-edit="state.loadingSlotEdit"
                    :deleting-slot-id="state.deletingSlotId"
                    @page="loadSeasons"
                    @edit="openEditSlotModal"
                    @remove="removeSlot"
                />

                <BonusServicesPanel
                    v-else
                    :groups="bonusGroups"
                    :loading="state.loadingBonus"
                    :t="t"
                />
            </div>

            <Dialog
                :open="state.slotModalOpen"
                @update:open="state.slotModalOpen = $event"
            >
                <DialogScrollContent class="sm:max-w-4xl">
                    <DialogHeader>
                        <DialogTitle>{{ slotModalTitle }}</DialogTitle>
                        <DialogDescription>
                            {{ t('services.modal_desc_period') }}
                        </DialogDescription>
                    </DialogHeader>

                    <div
                        v-if="state.errors.length > 0"
                        class="services-errors"
                        role="alert"
                    >
                        <p
                            v-for="error in state.errors"
                            :key="`${error.code}-${error.message}`"
                        >
                            {{ error.message }}
                        </p>
                    </div>

                    <form
                        class="mt-2 grid gap-3 md:grid-cols-2"
                        @submit.prevent="submitSlotForm"
                    >
                        <div class="field">
                            <label
                                for="slot-service"
                                class="text-sm font-medium"
                                >{{ t('services.field_service') }}</label
                            >
                            <select
                                id="slot-service"
                                v-model.number="state.slotForm.service_id"
                                disabled
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                required
                            >
                                <option :value="0" disabled>
                                    {{ t('services.field_select_service') }}
                                </option>
                                <option
                                    v-for="service in state.services"
                                    :key="service.id"
                                    :value="service.id"
                                >
                                    {{ serviceLabelById(service.id) }}
                                </option>
                            </select>
                        </div>

                        <div class="field">
                            <label
                                for="slot-name"
                                class="text-sm font-medium"
                                >{{ t('services.field_season') }}</label
                            >
                            <input
                                id="slot-name"
                                v-model="state.slotForm.name"
                                type="text"
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                :placeholder="
                                    t('services.field_season_placeholder')
                                "
                                required
                            />
                        </div>

                        <div class="field">
                            <label for="slot-max" class="text-sm font-medium">{{
                                t('services.daily_capacity')
                            }}</label>
                            <input
                                id="slot-max"
                                v-model.number="state.slotForm.max"
                                type="number"
                                min="0"
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                            />
                        </div>

                        <div class="field">
                            <label
                                for="slot-season-type"
                                class="text-sm font-medium"
                                >{{ t('services.field_season_group') }}</label
                            >
                            <select
                                id="slot-season-type"
                                v-model="state.slotForm.season_type"
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="other">
                                    {{ t('services.group_other') }}
                                </option>
                                <option value="winter">
                                    {{ t('services.group_winter') }}
                                </option>
                                <option value="summer">
                                    {{ t('services.group_summer') }}
                                </option>
                            </select>
                        </div>

                        <div class="field md:col-span-2">
                            <label class="text-sm font-medium">{{
                                t('services.field_dates')
                            }}</label>
                            <div class="service-slot-calendar-shell">
                                <div class="booking-calendar">
                                    <div class="booking-calendar__header">
                                        <button
                                            type="button"
                                            class="booking-calendar__nav"
                                            :disabled="state.submittingSlot"
                                            @click="moveSlotCalendarMonth(-1)"
                                        >
                                            ‹
                                        </button>
                                        <p class="booking-calendar__title">
                                            {{ slotCalendarMonthLabel }}
                                        </p>
                                        <button
                                            type="button"
                                            class="booking-calendar__nav"
                                            :disabled="state.submittingSlot"
                                            @click="moveSlotCalendarMonth(1)"
                                        >
                                            ›
                                        </button>
                                    </div>

                                    <div class="booking-calendar__weekdays">
                                        <span
                                            v-for="weekday in SLOT_WEEKDAY_LABELS"
                                            :key="`slot-calendar-weekday-${weekday}`"
                                            class="booking-calendar__weekday"
                                        >
                                            {{ weekday }}
                                        </span>
                                    </div>

                                    <div class="booking-calendar__grid">
                                        <template
                                            v-for="cell in slotCalendarCells"
                                            :key="cell.key"
                                        >
                                            <span
                                                v-if="cell.iso === null"
                                                class="booking-calendar__cell booking-calendar__cell--blank"
                                            />
                                            <button
                                                v-else
                                                type="button"
                                                class="booking-calendar__cell"
                                                :class="{
                                                    'booking-calendar__cell--selected':
                                                        cell.selected,
                                                    'booking-calendar__cell--disabled':
                                                        cell.disabled,
                                                    'booking-calendar__cell--today':
                                                        cell.today,
                                                }"
                                                :disabled="
                                                    cell.disabled ||
                                                    state.submittingSlot
                                                "
                                                @click="
                                                    toggleSlotCalendarDate(cell)
                                                "
                                            >
                                                {{ cell.dayNumber }}
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <div class="service-slot-calendar-meta">
                                    <div class="service-slot-calendar-actions">
                                        <button
                                            v-if="slotSelectedDates.length > 0"
                                            type="button"
                                            class="rounded-full border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:border-red-300 hover:text-red-700"
                                            :disabled="state.submittingSlot"
                                            @click="clearSlotSelectedDates"
                                        >
                                            {{ t('services.clear_all') }}
                                        </button>
                                    </div>

                                    <div
                                        v-if="slotSelectedDates.length > 0"
                                        class="service-slot-selected-dates"
                                    >
                                        <button
                                            v-for="date in slotSelectedDates"
                                            :key="`slot-selected-date-${date}`"
                                            type="button"
                                            class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 transition hover:border-blue-300 hover:bg-blue-100"
                                            :disabled="state.submittingSlot"
                                            @click="
                                                removeSlotSelectedDate(date)
                                            "
                                        >
                                            {{ formatGermanDate(date) }} x
                                        </button>
                                    </div>
                                    <p
                                        v-else
                                        class="service-slot-calendar-note"
                                    >
                                        {{
                                            t('services.calendar_select_dates')
                                        }}
                                    </p>

                                    <p
                                        v-if="slotModalBlockedDateSet.size > 0"
                                        class="service-slot-calendar-note"
                                    >
                                        {{
                                            t('services.calendar_disabled_note')
                                        }}
                                    </p>

                                    <input
                                        v-model="state.slotForm.datesCsv"
                                        type="hidden"
                                    />
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-end gap-2 md:col-span-2"
                        >
                            <button
                                type="button"
                                class="rounded-md border border-input px-4 py-2 text-sm"
                                :disabled="state.submittingSlot"
                                @click="state.slotModalOpen = false"
                            >
                                {{ t('common.cancel') }}
                            </button>
                            <button
                                type="submit"
                                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-60"
                                :disabled="state.submittingSlot"
                            >
                                {{
                                    state.submittingSlot
                                        ? t('services.saving')
                                        : t('services.save_period')
                                }}
                            </button>
                        </div>
                    </form>
                </DialogScrollContent>
            </Dialog>

            <Dialog
                :open="state.serviceModalOpen"
                @update:open="state.serviceModalOpen = $event"
            >
                <DialogScrollContent class="sm:max-w-4xl">
                    <DialogHeader>
                        <DialogTitle>{{ serviceModalTitle }}</DialogTitle>
                        <DialogDescription>
                            {{ t('services.modal_desc_service') }}
                        </DialogDescription>
                    </DialogHeader>

                    <form
                        class="mt-2 grid gap-3 md:grid-cols-2 xl:grid-cols-3"
                        @submit.prevent="submitServiceForm"
                    >
                        <div
                            v-for="field in translationFields"
                            :key="`service-translation-${field.locale}`"
                            class="field"
                        >
                            <label
                                :for="`service-title-${field.locale}`"
                                class="text-sm font-medium"
                            >
                                {{
                                    t('services.field_title_locale', {
                                        label: field.label,
                                    })
                                }}
                            </label>
                            <input
                                :id="`service-title-${field.locale}`"
                                v-model="
                                    state.serviceForm.translations[field.locale]
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

                        <div class="field">
                            <label
                                for="service-max"
                                class="text-sm font-medium"
                                >{{ t('services.field_default_max') }}</label
                            >
                            <input
                                id="service-max"
                                v-model.number="state.serviceForm.max"
                                type="number"
                                min="0"
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                required
                            />
                        </div>

                        <label
                            class="inline-flex items-center gap-2 text-sm font-medium md:col-span-2"
                        >
                            <input
                                v-model="state.serviceForm.active"
                                type="checkbox"
                                class="h-4 w-4"
                            />
                            {{ t('services.field_active') }}
                        </label>

                        <div
                            class="flex items-center justify-end gap-2 md:col-span-2"
                        >
                            <button
                                type="button"
                                class="rounded-md border border-input px-4 py-2 text-sm"
                                :disabled="state.submittingService"
                                @click="state.serviceModalOpen = false"
                            >
                                {{ t('common.cancel') }}
                            </button>
                            <button
                                type="submit"
                                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-60"
                                :disabled="state.submittingService"
                            >
                                {{
                                    state.submittingService
                                        ? state.serviceFormMode === 'edit'
                                            ? t('services.saving')
                                            : t('services.adding')
                                        : state.serviceFormMode === 'edit'
                                          ? t('services.save_service_name')
                                          : t('services.add_service_name')
                                }}
                            </button>
                        </div>
                    </form>

                    <div class="mt-4 rounded-md border border-input/70 p-3">
                        <div class="mb-2 text-sm font-medium">
                            {{ t('services.existing_services') }}
                        </div>
                        <div class="services-modal-list">
                            <button
                                v-for="service in state.services"
                                :key="service.id"
                                type="button"
                                class="services-modal-item"
                                @click="openEditServiceModal(service)"
                            >
                                <span>{{ serviceLabelById(service.id) }}</span>
                                <span class="services-modal-edit">{{
                                    t('services.edit_short')
                                }}</span>
                            </button>
                        </div>
                    </div>
                </DialogScrollContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
