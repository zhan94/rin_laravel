<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useServicePeriodCreatePage } from './composables/useServicePeriodCreatePage';

const {
    SLOT_WEEKDAY_LABELS,
    clearSlotSelectedDates,
    formatDateLabel,
    hasServices,
    moveSlotCalendarMonth,
    removeSlotSelectedDate,
    serviceLabelById,
    slotCalendarCells,
    slotCalendarMonthLabel,
    slotModalBlockedDateSet,
    slotSelectedDates,
    state,
    submitSlotForm,
    toggleDatePicker,
    toggleSlotCalendarDate,
} = useServicePeriodCreatePage();
</script>

<template>
    <Head title="New service period" />

    <AppLayout>
        <div
            class="dashboard customer-form customer-ui service-form service-period-create"
        >
            <div class="customer-shell">
                <div class="form-hero">
                    <div>
                        <Link href="/crm/services" class="btn btn-ghost">
                            <span class="glyphicon glyphicon-chevron-left" />
                            Back to services
                        </Link>
                        <div class="eyebrow">ADD</div>
                        <h1>New service period</h1>
                        <div class="subline">
                            Add season, capacity and dates for the service.
                        </div>
                    </div>
                </div>

                <div
                    v-if="state.errors.length > 0"
                    class="service-period-errors"
                >
                    <p
                        v-for="error in state.errors"
                        :key="`${error.code}-${error.message}`"
                    >
                        {{ error.message }}
                    </p>
                </div>

                <form
                    class="customer-form-grid"
                    @submit.prevent="submitSlotForm"
                >
                    <div class="form-layout">
                        <div class="form-card">
                            <div class="card-head">
                                <h3>Main information</h3>
                            </div>

                            <div class="form-grid service-period-main-grid">
                                <div class="field">
                                    <label for="type">Service</label>
                                    <select
                                        id="type"
                                        v-model.number="
                                            state.slotForm.service_id
                                        "
                                        class="form-control"
                                        :disabled="
                                            state.loading ||
                                            !hasServices ||
                                            state.submitting
                                        "
                                        required
                                    >
                                        <option :value="0">
                                            Please choose
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
                                    <label for="max_input"
                                        >Maximum children</label
                                    >
                                    <input
                                        id="max_input"
                                        v-model.number="state.slotForm.max"
                                        type="number"
                                        min="0"
                                        class="form-control"
                                        placeholder="Maximum children"
                                        required
                                    />
                                </div>

                                <div class="field">
                                    <label for="slot_name_input">Season</label>
                                    <input
                                        id="slot_name_input"
                                        v-model="state.slotForm.name"
                                        type="text"
                                        class="form-control"
                                        placeholder="e.g. Winter season 25/26"
                                        required
                                    />
                                </div>

                                <div class="field field--period-type">
                                    <label for="season_type_input"
                                        >Period type</label
                                    >
                                    <select
                                        id="season_type_input"
                                        v-model="state.slotForm.season_type"
                                        class="form-control"
                                        required
                                    >
                                        <option value="other">
                                            Spring / Summer / Autumn
                                        </option>
                                        <option value="winter">Winter</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-card">
                            <div class="card-head">
                                <h3>Period dates</h3>
                            </div>

                            <div
                                id="datepicker_wrapper"
                                class="service-period-dates-shell"
                            >
                                <div id="dates_div" class="field">
                                    <label for="ca">Select dates</label>
                                    <button
                                        id="ca"
                                        type="button"
                                        class="btn btn-outline service-period-date-trigger"
                                        :disabled="
                                            state.slotForm.service_id <= 0 ||
                                            state.submitting
                                        "
                                        @click="toggleDatePicker"
                                    >
                                        <span
                                            class="glyphicon glyphicon-calendar"
                                        />
                                        Select dates
                                    </button>
                                </div>

                                <div
                                    v-if="state.datePickerOpen"
                                    class="service-period-calendar-panel"
                                >
                                    <div class="booking-calendar">
                                        <div class="booking-calendar__header">
                                            <button
                                                type="button"
                                                class="booking-calendar__nav"
                                                :disabled="state.submitting"
                                                @click="
                                                    moveSlotCalendarMonth(-1)
                                                "
                                            >
                                                ‹
                                            </button>
                                            <p class="booking-calendar__title">
                                                {{ slotCalendarMonthLabel }}
                                            </p>
                                            <button
                                                type="button"
                                                class="booking-calendar__nav"
                                                :disabled="state.submitting"
                                                @click="
                                                    moveSlotCalendarMonth(1)
                                                "
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
                                                        state.submitting
                                                    "
                                                    @click="
                                                        toggleSlotCalendarDate(
                                                            cell,
                                                        )
                                                    "
                                                >
                                                    {{ cell.dayNumber }}
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                    <div
                                        class="service-period-calendar-actions"
                                    >
                                        <button
                                            v-if="slotSelectedDates.length > 0"
                                            type="button"
                                            class="btn btn-ghost btn-xs"
                                            :disabled="state.submitting"
                                            @click="clearSlotSelectedDates"
                                        >
                                            Clear all
                                        </button>
                                    </div>
                                </div>

                                <div
                                    v-if="slotSelectedDates.length > 0"
                                    class="service-period-selected-dates"
                                >
                                    <button
                                        v-for="date in slotSelectedDates"
                                        :key="`slot-selected-date-${date}`"
                                        type="button"
                                        class="service-period-selected-date"
                                        :disabled="state.submitting"
                                        @click="removeSlotSelectedDate(date)"
                                    >
                                        {{ formatDateLabel(date) }} x
                                    </button>
                                </div>
                                <p
                                    v-else-if="state.datePickerOpen"
                                    class="service-period-note"
                                >
                                    Choose one or more dates from the calendar.
                                </p>
                                <p
                                    v-if="
                                        state.loadingSlots &&
                                        state.datePickerOpen
                                    "
                                    class="service-period-note"
                                >
                                    Loading unavailable dates...
                                </p>
                                <p
                                    v-if="
                                        slotModalBlockedDateSet.size > 0 &&
                                        state.datePickerOpen
                                    "
                                    class="service-period-note"
                                >
                                    Dates already assigned in this service are
                                    disabled.
                                </p>
                                <input
                                    id="altField"
                                    v-model="state.slotForm.datesCsv"
                                    type="hidden"
                                    class="form-control"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="form-actions" id="buttons">
                        <Link href="/crm/services" class="btn btn-ghost"
                            >Cancel</Link
                        >
                        <button
                            type="submit"
                            name="go"
                            id="add_service"
                            class="btn btn-solid"
                            :disabled="
                                state.submitting ||
                                state.loading ||
                                !hasServices
                            "
                        >
                            {{ state.submitting ? 'Adding...' : 'Add' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
