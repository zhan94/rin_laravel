<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import BookingListPanel from '@/pages/crm/components/BookingListPanel.vue';
import BookingStepOne from '@/pages/crm/components/BookingStepOne.vue';
import BookingStepTwo from '@/pages/crm/components/BookingStepTwo.vue';
import { provideBookingPageContext } from '@/pages/crm/composables/bookingPageContext';
import { useBookingsPage } from '@/pages/crm/composables/useBookingsPage';
import { Head } from '@inertiajs/vue3';

const bookingPage = useBookingsPage();
provideBookingPageContext(bookingPage);

const { t, state, summaryLabel, heroCopy } = bookingPage;
</script>

<template>
    <Head :title="t('customers.add_customer')" />

    <AppLayout>
        <div
            class="dashboard customer-ui customer-form booking-form customer-list"
        >
            <div class="customer-shell booking-shell">
                <div class="form-hero">
                    <div>
                        <div class="eyebrow">{{ heroCopy.eyebrow }}</div>
                        <h1>{{ heroCopy.title }}</h1>
                        <div class="subline">{{ heroCopy.subtitle }}</div>
                    </div>
                </div>

                <div v-if="state.errors.length > 0" class="booking-errors">
                    <p
                        v-for="error in state.errors"
                        :key="`${error.code}-${error.message}`"
                    >
                        {{ error.message }}
                    </p>
                </div>

                <BookingStepOne v-if="state.createStep === 1" />

                <BookingStepTwo v-else />
            </div>

            <BookingListPanel :summary-label="summaryLabel" />
        </div>
    </AppLayout>
</template>
