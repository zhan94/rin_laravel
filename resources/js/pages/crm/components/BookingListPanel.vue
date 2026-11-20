<script setup lang="ts">
import { useUiI18n } from '@/composables/useUiI18n';
import { formatGermanDate } from '@/modules/calendar/dateDisplay';
import { formatEur } from '@/modules/money/currency';
import { useBookingPageContext } from '@/pages/crm/composables/bookingPageContext';

const {
    applyInlinePayment,
    cancelBooking,
    editBooking,
    goToPage,
    loadBookings,
    state,
    t,
} = useBookingPageContext();

const { localeTag } = useUiI18n();

function formatAmount(value: number | null): string {
    return value === null ? '—' : formatEur(value, localeTag.value);
}

defineProps<{
    summaryLabel: string;
}>();
</script>

<template>
    <div class="customer-shell booking-list-shell">
        <div class="list-hero">
            <div>
                <div class="eyebrow">
                    {{ t('bookings.list_eyebrow') }}
                </div>
                <h1>{{ t('bookings.list_title') }}</h1>
                <div class="subline">{{ summaryLabel }}</div>
            </div>
        </div>

        <section class="table-shell booking-table-shell">
            <div class="table-controls booking-table-controls">
                <input
                    v-model="state.query"
                    type="text"
                    :placeholder="t('bookings.search_placeholder')"
                    class="form-control"
                />
                <button
                    type="button"
                    class="btn btn-outline"
                    @click="loadBookings"
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
                            <th>#</th>
                            <th>{{ t('bookings.table_customer') }}</th>
                            <th>{{ t('bookings.table_service') }}</th>
                            <th>{{ t('bookings.table_dates') }}</th>
                            <th>{{ t('bookings.table_sum') }}</th>
                            <th>{{ t('bookings.table_paid') }}</th>
                            <th>{{ t('bookings.table_due') }}</th>
                            <th>{{ t('bookings.status') }}</th>
                            <th>{{ t('bookings.table_add_payment') }}</th>
                            <th>{{ t('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="state.loading">
                            <td colspan="10" class="empty-row">
                                {{ t('bookings.loading_rows') }}
                            </td>
                        </tr>
                        <tr v-for="booking in state.rows" :key="booking.id">
                            <td data-label="#">{{ booking.id }}</td>
                            <td :data-label="t('bookings.table_customer')">
                                {{ booking.customer?.full_name ?? '-' }}
                            </td>
                            <td :data-label="t('bookings.table_service')">
                                {{ booking.service_id }}
                            </td>
                            <td :data-label="t('bookings.table_dates')">
                                {{
                                    booking.booking_dates
                                        .map((d) =>
                                            formatGermanDate(d.slot_date),
                                        )
                                        .join(', ')
                                }}
                            </td>
                            <td :data-label="t('bookings.table_sum')">
                                {{ formatAmount(booking.sum_paid) }}
                            </td>
                            <td :data-label="t('bookings.table_paid')">
                                {{ formatAmount(booking.paid) }}
                            </td>
                            <td :data-label="t('bookings.table_due')">
                                {{ formatAmount(booking.due) }}
                            </td>
                            <td :data-label="t('bookings.status')">
                                {{
                                    booking.active
                                        ? t('common.active')
                                        : t('bookings.cancelled')
                                }}
                                <p v-if="booking.active" class="filters-note">
                                    {{
                                        booking.approved
                                            ? t('bookings.consent_received')
                                            : t('bookings.consent_pending')
                                    }}
                                </p>
                            </td>
                            <td :data-label="t('bookings.table_add_payment')">
                                <div class="pay-stack">
                                    <input
                                        v-model="
                                            state.paymentDrafts[booking.id]
                                        "
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        :placeholder="t('bookings.amount')"
                                        class="form-control pay_input"
                                    />
                                    <button
                                        type="button"
                                        class="btn btn-outline btn-xs"
                                        :disabled="
                                            state.applyingPaymentId ===
                                            booking.id
                                        "
                                        @click="applyInlinePayment(booking)"
                                    >
                                        {{
                                            state.applyingPaymentId ===
                                            booking.id
                                                ? '...'
                                                : t('bookings.apply')
                                        }}
                                    </button>
                                </div>
                                <p
                                    v-if="state.paymentMessages[booking.id]"
                                    class="booking-payment-message"
                                >
                                    {{ state.paymentMessages[booking.id] }}
                                </p>
                            </td>
                            <td :data-label="t('common.actions')">
                                <div v-if="booking.active" class="action-group">
                                    <button
                                        type="button"
                                        class="btn btn-outline btn-xs"
                                        :disabled="
                                            state.submitting ||
                                            state.cancellingBookingId !== 0 ||
                                            !booking.customer_id
                                        "
                                        @click="editBooking(booking)"
                                    >
                                        {{ t('common.edit') }}
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-outline btn-xs"
                                        :disabled="
                                            state.submitting ||
                                            state.cancellingBookingId !== 0
                                        "
                                        @click="cancelBooking(booking)"
                                    >
                                        {{ t('bookings.cancel_booking') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!state.loading && state.rows.length === 0">
                            <td colspan="10" class="empty-row">
                                {{ t('bookings.no_rows') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="booking-pagination">
                <button
                    type="button"
                    class="btn btn-outline"
                    :disabled="state.pagination.current_page <= 1"
                    @click="goToPage(state.pagination.current_page - 1)"
                >
                    {{ t('common.previous') }}
                </button>
                <span class="filters-note">
                    {{
                        t('common.page_of', {
                            current: state.pagination.current_page,
                            last: state.pagination.last_page,
                        })
                    }}
                </span>
                <button
                    type="button"
                    class="btn btn-outline"
                    :disabled="
                        state.pagination.current_page >=
                        state.pagination.last_page
                    "
                    @click="goToPage(state.pagination.current_page + 1)"
                >
                    {{ t('common.next') }}
                </button>
            </div>
        </section>
    </div>
</template>
