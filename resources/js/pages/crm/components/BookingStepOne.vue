<script setup lang="ts">
import { useBookingPageContext } from '@/pages/crm/composables/bookingPageContext';

const {
    continueToStepTwo,
    onCustomerSearchInput,
    onToggleNewCustomer,
    selectCustomerSuggestion,
    state,
    t,
} = useBookingPageContext();
</script>

<template>
    <section class="form-card booking-step">
        <div class="card-head">
            <h3>{{ t('bookings.step1_heading') }}</h3>
        </div>

        <div v-if="state.createStepError !== ''" class="booking-inline-error">
            {{ state.createStepError }}
        </div>

        <form
            class="customer-form-grid form-horizontal"
            @submit.prevent="continueToStepTwo"
        >
            <div class="form-group">
                <label class="col-sm-2 control-label">{{
                    t('bookings.customer')
                }}</label>
                <div class="col-sm-7">
                    <input
                        v-model="state.customerSearch"
                        type="text"
                        :placeholder="t('bookings.customer_placeholder')"
                        class="form-control"
                        :disabled="state.isNewCustomer"
                        @input="onCustomerSearchInput"
                    />

                    <div
                        v-if="
                            !state.isNewCustomer &&
                            state.customerSuggestions.length > 0
                        "
                        class="booking-suggest"
                    >
                        <button
                            v-for="suggestion in state.customerSuggestions"
                            :key="`${suggestion.customer_id}-${suggestion.label}`"
                            type="button"
                            class="booking-suggest-item"
                            @click="selectCustomerSuggestion(suggestion)"
                        >
                            {{ suggestion.label }}
                        </button>
                    </div>
                </div>
                <div class="checkbox col-sm-3">
                    <label>
                        <input
                            v-model="state.isNewCustomer"
                            type="checkbox"
                            @change="onToggleNewCustomer"
                        />
                        {{ t('bookings.new_customer') }}
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-2 control-label">{{
                    t('bookings.service_type')
                }}</label>
                <div class="col-sm-7">
                    <select
                        v-model.number="state.form.service_type"
                        class="form-control"
                    >
                        <option :value="1">
                            {{ t('bookings.service_type_service') }}
                        </option>
                        <option :value="2">
                            {{ t('bookings.service_type_additional') }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="form-actions form-actions--center">
                <button type="submit" class="btn btn-solid">
                    {{ t('bookings.continue') }}
                </button>
            </div>
        </form>
    </section>
</template>
