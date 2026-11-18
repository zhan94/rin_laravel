<script setup lang="ts">
import { useBookingPageContext } from '@/pages/crm/composables/bookingPageContext';

const { state, t } = useBookingPageContext();
</script>

<template>
    <hr class="section-divider" />

    <div class="card-head">
        <h3>{{ t('bookings.payment') }}</h3>
    </div>

    <div class="form-group" id="payment_div">
        <label class="col-sm-2 control-label">{{
            t('bookings.total_amount_eur')
        }}</label>
        <div class="col-sm-4">
            <input
                v-model.number="state.form.sum_paid"
                type="number"
                min="0"
                step="0.01"
                :placeholder="t('bookings.total_amount_eur')"
                class="form-control"
            />
        </div>
        <label class="col-sm-2 control-label">{{
            t('bookings.paid_eur')
        }}</label>
        <div class="col-sm-4">
            <input
                v-model.number="state.form.paid"
                type="number"
                min="0"
                step="0.01"
                :placeholder="t('bookings.paid_eur')"
                class="form-control"
            />
        </div>
    </div>

    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
            <div class="filters-note">
                {{ t('bookings.amounts_eur_note') }}
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
            <div class="checkbox">
                <label>
                    <input v-model="state.form.approved" type="checkbox" />
                    {{ t('bookings.parental_consent') }}
                </label>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">{{ t('bookings.note') }}</label>
        <div class="col-sm-10">
            <textarea
                v-model="state.form.note"
                :placeholder="t('bookings.note')"
                class="form-control"
                rows="6"
            />
        </div>
    </div>
</template>
