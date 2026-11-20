<script setup lang="ts">
import {
    Dialog,
    DialogDescription,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { useLocationsPage } from '@/pages/crm/composables/useLocationsPage';
import { Head } from '@inertiajs/vue3';
import { Pencil, Trash2 } from 'lucide-vue-next';

const {
    t,
    state,
    formModalTitle,
    mainLocations,
    customLocationGroups,
    serviceTotal,
    mainLocationsTotal,
    customLocationsTotal,
    locationsTotal,
    inlineTimeValue,
    onInlineTimeInput,
    isInlineSaving,
    openCreateModal,
    openEditModal,
    closeFormModal,
    saveInlineTime,
    submitLocation,
    removeLocation,
} = useLocationsPage();
</script>

<template>
    <Head :title="t('locations.head')" />

    <AppLayout>
        <div
            class="dashboard customer-ui customer-list catalog-list location-list locations-ui"
        >
            <div class="customer-shell">
                <section class="list-hero">
                    <div>
                        <p class="eyebrow">{{ t('locations.eyebrow') }}</p>
                        <h1>{{ t('locations.title') }}</h1>
                        <p class="subline">{{ t('locations.subtitle') }}</p>
                    </div>
                    <div class="hero-actions">
                        <button
                            type="button"
                            class="btn btn-solid"
                            @click="openCreateModal"
                        >
                            {{ t('locations.add') }}
                        </button>
                    </div>
                </section>

                <div v-if="state.errors.length > 0" class="locations-errors">
                    <p
                        v-for="error in state.errors"
                        :key="`${error.code}-${error.message}`"
                    >
                        {{ error.message }}
                    </p>
                </div>

                <section class="stat-grid locations-stats">
                    <article class="stat-card">
                        <span class="stat-label">{{
                            t('locations.stat_services')
                        }}</span>
                        <span class="stat-value">{{ serviceTotal }}</span>
                    </article>
                    <article class="stat-card">
                        <span class="stat-label">{{
                            t('locations.stat_locations')
                        }}</span>
                        <span class="stat-value">{{ locationsTotal }}</span>
                    </article>
                    <article class="stat-card">
                        <span class="stat-label">{{
                            t('locations.stat_main')
                        }}</span>
                        <span class="stat-value">{{ mainLocationsTotal }}</span>
                    </article>
                    <article class="stat-card">
                        <span class="stat-label">{{
                            t('locations.stat_custom')
                        }}</span>
                        <span class="stat-value">{{
                            customLocationsTotal
                        }}</span>
                    </article>
                </section>

                <section
                    v-if="
                        state.loading ||
                        mainLocations.length > 0 ||
                        customLocationGroups.length === 0
                    "
                    class="table-shell"
                >
                    <div class="table-head">
                        <span class="badge badge--info">{{
                            t('locations.badge_main')
                        }}</span>
                        <span class="filters-note">{{
                            t('locations.note_main')
                        }}</span>
                    </div>
                    <div class="table-wrap">
                        <table
                            class="table-hover table-bordered table-responsive customer-table table-stack-mobile table"
                        >
                            <thead>
                                <tr>
                                    <th>{{ t('common.pickup') }}</th>
                                    <th>{{ t('common.dropoff') }}</th>
                                    <th>{{ t('common.pickup_time') }}</th>
                                    <th>{{ t('common.dropoff_time') }}</th>
                                    <th>{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="state.loading">
                                    <td colspan="5" class="empty-row">
                                        {{ t('locations.loading') }}
                                    </td>
                                </tr>
                                <tr v-else-if="mainLocations.length === 0">
                                    <td colspan="5" class="empty-row">
                                        {{ t('locations.no_main') }}
                                    </td>
                                </tr>
                                <tr
                                    v-for="location in mainLocations"
                                    :key="`main-location-${location.id}`"
                                >
                                    <td :data-label="t('common.pickup')">
                                        {{ location.location_from }}
                                    </td>
                                    <td :data-label="t('common.dropoff')">
                                        {{ location.location_to }}
                                    </td>
                                    <td :data-label="t('common.pickup_time')">
                                        <div class="time-edit">
                                            <input
                                                :value="
                                                    inlineTimeValue(
                                                        location,
                                                        'from',
                                                    )
                                                "
                                                type="time"
                                                class="form-control location-time-input"
                                                step="60"
                                                @input="
                                                    onInlineTimeInput(
                                                        location,
                                                        'from',
                                                        $event,
                                                    )
                                                "
                                            />
                                            <button
                                                type="button"
                                                class="btn btn-outline btn-xs hour_btn"
                                                :disabled="
                                                    isInlineSaving(
                                                        location.id,
                                                        'from',
                                                    )
                                                "
                                                @click="
                                                    saveInlineTime(
                                                        location,
                                                        'from',
                                                    )
                                                "
                                            >
                                                {{
                                                    isInlineSaving(
                                                        location.id,
                                                        'from',
                                                    )
                                                        ? '...'
                                                        : t('common.save')
                                                }}
                                            </button>
                                        </div>
                                    </td>
                                    <td :data-label="t('common.dropoff_time')">
                                        <div class="time-edit">
                                            <input
                                                :value="
                                                    inlineTimeValue(
                                                        location,
                                                        'to',
                                                    )
                                                "
                                                type="time"
                                                class="form-control location-time-input"
                                                step="60"
                                                @input="
                                                    onInlineTimeInput(
                                                        location,
                                                        'to',
                                                        $event,
                                                    )
                                                "
                                            />
                                            <button
                                                type="button"
                                                class="btn btn-outline btn-xs hour_btn"
                                                :disabled="
                                                    isInlineSaving(
                                                        location.id,
                                                        'to',
                                                    )
                                                "
                                                @click="
                                                    saveInlineTime(
                                                        location,
                                                        'to',
                                                    )
                                                "
                                            >
                                                {{
                                                    isInlineSaving(
                                                        location.id,
                                                        'to',
                                                    )
                                                        ? '...'
                                                        : t('common.save')
                                                }}
                                            </button>
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
                                                @click="openEditModal(location)"
                                            >
                                                <Pencil class="h-3.5 w-3.5" />
                                            </button>
                                            <button
                                                type="button"
                                                class="icon-btn icon-btn--danger"
                                                :disabled="
                                                    state.deletingId ===
                                                    location.id
                                                "
                                                @click="
                                                    removeLocation(location.id)
                                                "
                                            >
                                                <Trash2
                                                    v-if="
                                                        state.deletingId !==
                                                        location.id
                                                    "
                                                    class="h-3.5 w-3.5"
                                                />
                                                <span v-else>...</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section
                    v-for="group in customLocationGroups"
                    :key="`custom-location-group-${group.serviceId}`"
                    class="table-shell"
                >
                    <div class="table-head">
                        <span class="badge badge--info">{{ group.title }}</span>
                        <span class="badge badge--info">{{
                            t('locations.badge_custom')
                        }}</span>
                        <span class="filters-note">{{
                            t('locations.note_custom')
                        }}</span>
                    </div>
                    <div class="table-wrap">
                        <table
                            class="table-hover table-bordered table-responsive customer-table table-stack-mobile table"
                        >
                            <thead>
                                <tr>
                                    <th>{{ t('common.pickup') }}</th>
                                    <th>{{ t('common.dropoff') }}</th>
                                    <th>{{ t('common.pickup_time') }}</th>
                                    <th>{{ t('common.dropoff_time') }}</th>
                                    <th>{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="location in group.rows"
                                    :key="`custom-location-${location.id}`"
                                >
                                    <td :data-label="t('common.pickup')">
                                        {{ location.location_from }}
                                    </td>
                                    <td :data-label="t('common.dropoff')">
                                        {{ location.location_to }}
                                    </td>
                                    <td :data-label="t('common.pickup_time')">
                                        <div class="time-edit">
                                            <input
                                                :value="
                                                    inlineTimeValue(
                                                        location,
                                                        'from',
                                                    )
                                                "
                                                type="time"
                                                class="form-control location-time-input"
                                                step="60"
                                                @input="
                                                    onInlineTimeInput(
                                                        location,
                                                        'from',
                                                        $event,
                                                    )
                                                "
                                            />
                                            <button
                                                type="button"
                                                class="btn btn-outline btn-xs hour_btn"
                                                :disabled="
                                                    isInlineSaving(
                                                        location.id,
                                                        'from',
                                                    )
                                                "
                                                @click="
                                                    saveInlineTime(
                                                        location,
                                                        'from',
                                                    )
                                                "
                                            >
                                                {{
                                                    isInlineSaving(
                                                        location.id,
                                                        'from',
                                                    )
                                                        ? '...'
                                                        : t('common.save')
                                                }}
                                            </button>
                                        </div>
                                    </td>
                                    <td :data-label="t('common.dropoff_time')">
                                        <div class="time-edit">
                                            <input
                                                :value="
                                                    inlineTimeValue(
                                                        location,
                                                        'to',
                                                    )
                                                "
                                                type="time"
                                                class="form-control location-time-input"
                                                step="60"
                                                @input="
                                                    onInlineTimeInput(
                                                        location,
                                                        'to',
                                                        $event,
                                                    )
                                                "
                                            />
                                            <button
                                                type="button"
                                                class="btn btn-outline btn-xs hour_btn"
                                                :disabled="
                                                    isInlineSaving(
                                                        location.id,
                                                        'to',
                                                    )
                                                "
                                                @click="
                                                    saveInlineTime(
                                                        location,
                                                        'to',
                                                    )
                                                "
                                            >
                                                {{
                                                    isInlineSaving(
                                                        location.id,
                                                        'to',
                                                    )
                                                        ? '...'
                                                        : t('common.save')
                                                }}
                                            </button>
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
                                                @click="openEditModal(location)"
                                            >
                                                <Pencil class="h-3.5 w-3.5" />
                                            </button>
                                            <button
                                                type="button"
                                                class="icon-btn icon-btn--danger"
                                                :disabled="
                                                    state.deletingId ===
                                                    location.id
                                                "
                                                @click="
                                                    removeLocation(location.id)
                                                "
                                            >
                                                <Trash2
                                                    v-if="
                                                        state.deletingId !==
                                                        location.id
                                                    "
                                                    class="h-3.5 w-3.5"
                                                />
                                                <span v-else>...</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
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
                            {{ t('locations.modal_desc') }}
                        </DialogDescription>
                    </DialogHeader>

                    <form
                        class="mt-2 grid gap-3 md:grid-cols-2"
                        @submit.prevent="submitLocation"
                    >
                        <div class="field">
                            <label
                                for="location-from"
                                class="text-sm font-medium"
                                >{{ t('common.pickup') }}</label
                            >
                            <input
                                id="location-from"
                                v-model="state.form.location_from"
                                type="text"
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                required
                            />
                        </div>

                        <div class="field">
                            <label
                                for="location-to"
                                class="text-sm font-medium"
                                >{{ t('common.dropoff') }}</label
                            >
                            <input
                                id="location-to"
                                v-model="state.form.location_to"
                                type="text"
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                required
                            />
                        </div>

                        <div class="field">
                            <label
                                for="location-from-time"
                                class="text-sm font-medium"
                                >{{ t('common.pickup_time') }}</label
                            >
                            <input
                                id="location-from-time"
                                v-model="state.form.from_time"
                                type="time"
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                required
                            />
                        </div>

                        <div class="field">
                            <label
                                for="location-to-time"
                                class="text-sm font-medium"
                                >{{ t('common.dropoff_time') }}</label
                            >
                            <input
                                id="location-to-time"
                                v-model="state.form.to_time"
                                type="time"
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                required
                            />
                        </div>

                        <div class="field md:col-span-2">
                            <label
                                for="location-service"
                                class="text-sm font-medium"
                                >{{ t('locations.service_scope') }}</label
                            >
                            <select
                                id="location-service"
                                v-model.number="state.form.service_id"
                                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                                :disabled="state.loadingServices"
                            >
                                <option :value="0">
                                    {{ t('locations.scope_main') }}
                                </option>
                                <option
                                    v-for="service in state.services"
                                    :key="service.id"
                                    :value="service.id"
                                >
                                    {{
                                        service.title ||
                                        `Service #${service.id}`
                                    }}
                                </option>
                            </select>
                        </div>

                        <div
                            class="flex items-center justify-end gap-2 md:col-span-2"
                        >
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
                                            ? t('common.saving')
                                            : t('common.adding')
                                        : state.formMode === 'edit'
                                          ? t('locations.save_location')
                                          : t('locations.add')
                                }}
                            </button>
                        </div>
                    </form>
                </DialogScrollContent>
            </Dialog>
        </div>
    </AppLayout>
</template>
