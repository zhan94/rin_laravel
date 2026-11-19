<script setup lang="ts">
import BookingCustomerFields from '@/pages/crm/components/BookingCustomerFields.vue';
import BookingLocationPicker from '@/pages/crm/components/BookingLocationPicker.vue';
import BookingPaymentFields from '@/pages/crm/components/BookingPaymentFields.vue';
import BookingServiceCalendar from '@/pages/crm/components/BookingServiceCalendar.vue';
import BookingServiceOptionFields from '@/pages/crm/components/BookingServiceOptionFields.vue';
import BookingSkiFields from '@/pages/crm/components/BookingSkiFields.vue';
import { useBookingPageContext } from '@/pages/crm/composables/bookingPageContext';

const {
    WEEKDAY_LABELS,
    activeServiceOptions,
    backToStepOne,
    calendarMonthLabel,
    calendarWeeks,
    saveBooking,
    groupedLocations,
    isCardService,
    isEquipService,
    isServiceBooking,
    isSkiService,
    moveCalendarMonth,
    selectedDateCount,
    selectedLocationLabel,
    state,
    t,
    toggleCalendarDate,
} = useBookingPageContext();
</script>

<template>
    <section class="form-card booking-details-card">
        <form
            class="customer-form-grid form-horizontal"
            @submit.prevent="saveBooking"
        >
            <p v-if="state.editingBooking" class="booking-note">
                {{ t('bookings.customer') }}:
                <strong>{{ state.editingBooking.customer?.full_name }}</strong>
            </p>
            <BookingCustomerFields v-else />

            <hr class="section-divider" />

            <div class="card-head">
                <h3>{{ t('bookings.service_section') }}</h3>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{
                    t('bookings.select_service')
                }}</label>
                <div class="col-sm-10">
                    <select
                        v-model.number="state.form.service_id"
                        class="form-control"
                        :disabled="
                            state.loadingServices || state.loadingBonusServices
                        "
                        required
                    >
                        <option :value="0" disabled>
                            {{ t('bookings.select_service_option') }}
                        </option>
                        <option
                            v-for="service in activeServiceOptions"
                            :key="service.id"
                            :value="service.id"
                        >
                            {{ service.title }}
                        </option>
                    </select>
                </div>
            </div>

            <template v-if="isServiceBooking">
                <BookingServiceCalendar
                    v-if="state.form.service_id > 0"
                    :calendar-month-label="calendarMonthLabel"
                    :calendar-weeks="calendarWeeks"
                    :disabled-dates-count="state.disabledDates.length"
                    :loading-support="state.loadingSupport"
                    :selected-date-count="selectedDateCount"
                    :t="t"
                    :toggle-calendar-date="toggleCalendarDate"
                    :weekday-labels="WEEKDAY_LABELS"
                    @move-month="moveCalendarMonth"
                />

                <BookingLocationPicker
                    v-if="state.form.service_id > 0"
                    :grouped-locations="groupedLocations"
                    :selected-location-label="selectedLocationLabel"
                />

                <BookingSkiFields :is-ski-service="isSkiService" />

                <BookingServiceOptionFields
                    :is-card-service="isCardService"
                    :is-equip-service="isEquipService"
                />
            </template>

            <div
                v-else-if="state.bonusContext.found"
                class="booking-bonus-context"
            >
                <p>
                    <span class="booking-context-key"
                        >{{ t('bookings.bonus_location') }}:</span
                    >
                    {{ state.bonusContext.location }}
                </p>
                <p
                    v-if="
                        state.bonusContext.date_from ||
                        state.bonusContext.date_to
                    "
                >
                    <span class="booking-context-key"
                        >{{ t('bookings.bonus_period') }}:</span
                    >
                    {{ state.bonusContext.date_from ?? '-' }} -
                    {{ state.bonusContext.date_to ?? '-' }}
                </p>
            </div>

            <BookingPaymentFields />

            <hr class="section-divider" />

            <div class="form-actions">
                <button
                    type="button"
                    class="btn btn-ghost"
                    @click="backToStepOne"
                >
                    {{ t('common.cancel') }}
                </button>
                <button
                    type="submit"
                    class="btn btn-solid"
                    :disabled="state.submitting || state.loadingSupport"
                >
                    {{
                        state.submitting
                            ? t('bookings.saving')
                            : state.editingBooking
                              ? t('common.save')
                              : t('bookings.add')
                    }}
                </button>
            </div>
        </form>

        <p
            v-if="state.loadingCustomerSuggestions || state.loadingPrefill"
            class="booking-helper-note"
        >
            {{ t('bookings.helper_loading') }}
        </p>
    </section>
</template>
