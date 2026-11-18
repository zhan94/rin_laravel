<script setup lang="ts">
import { useBookingPageContext } from '@/pages/crm/composables/bookingPageContext';

const { state, t } = useBookingPageContext();
</script>

<template>
    <div class="card-head">
        <h3>{{ t('bookings.customer_data') }}</h3>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">{{
            t('bookings.first_name')
        }}</label>
        <div class="col-sm-10">
            <input
                v-model="state.form.firstname"
                type="text"
                class="form-control"
                :placeholder="t('bookings.first_name')"
                required
            />
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">{{
            t('bookings.surname')
        }}</label>
        <div class="col-sm-10">
            <input
                v-model="state.form.surname"
                type="text"
                class="form-control"
                :placeholder="t('bookings.surname')"
            />
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">{{
            t('bookings.last_name')
        }}</label>
        <div class="col-sm-10">
            <input
                v-model="state.form.lastname"
                type="text"
                class="form-control"
                :placeholder="t('bookings.last_name')"
                required
            />
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">{{
            t('bookings.birth_date')
        }}</label>
        <div class="col-sm-10">
            <input
                v-model="state.form.date_birth"
                type="date"
                class="form-control"
            />
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">{{
            t('bookings.parent_name')
        }}</label>
        <div class="col-sm-10">
            <input
                v-model="state.form.parent_name"
                type="text"
                class="form-control"
                :placeholder="t('bookings.parent_name')"
            />
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">{{
            t('bookings.parent_phone')
        }}</label>
        <div class="col-sm-10">
            <input
                v-model="state.form.parent_phone"
                type="text"
                class="form-control"
                :placeholder="t('bookings.parent_phone')"
            />
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-2 control-label">{{ t('bookings.email') }}</label>
        <div class="col-sm-10">
            <input
                v-model="state.form.email"
                type="email"
                class="form-control"
                :placeholder="t('bookings.email')"
            />
        </div>
    </div>
</template>
