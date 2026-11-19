<script setup lang="ts">
import GermanDateInput from '@/components/GermanDateInput.vue';
import { useUiI18n } from '@/composables/useUiI18n';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatGermanDate } from '@/modules/calendar/dateDisplay';
import { useCustomersPage } from '@/pages/crm/composables/useCustomersPage';
import { Head } from '@inertiajs/vue3';

const {
    t,
    state,
    activeUnpaidTab,
    visibleUnpaidRows,
    summaryLabel,
    formTitle,
    searchPlaceholder,
    customersStats,
    isEditing,
    openCreateForm,
    closeForm,
    formatAmount,
    canManage,
    loadCustomerDetails,
    activateCustomersMode,
    activateUnpaidMode,
    selectUnpaidTab,
    openCustomerFromUnpaid,
    startEdit,
    submitCustomer,
    removeCustomer,
    applyUnpaidPayment,
    runSearch,
    goToPage,
} = useCustomersPage();

const { formatNumber } = useUiI18n();
</script>

<template>
    <Head :title="t('customers.head')" />

    <AppLayout>
        <div
            class="dashboard customer-ui customer-form customer-list customers-ui"
        >
            <div class="customer-shell">
                <section class="list-hero">
                    <div>
                        <div class="eyebrow">{{ t('customers.eyebrow') }}</div>
                        <h1>{{ t('customers.title') }}</h1>
                        <div class="subline">{{ t('customers.subtitle') }}</div>
                    </div>

                    <div class="hero-actions">
                        <button
                            type="button"
                            class="btn btn-solid"
                            v-if="canManage"
                            @click="openCreateForm"
                        >
                            <span
                                class="glyphicon glyphicon-plus"
                                aria-hidden="true"
                            />
                            {{ t('customers.add_customer') }}
                        </button>
                        <button
                            type="button"
                            class="btn"
                            :class="
                                state.unpaidMode ? 'btn-solid' : 'btn-outline'
                            "
                            @click="activateUnpaidMode"
                        >
                            <span
                                class="glyphicon glyphicon-list-alt"
                                aria-hidden="true"
                            />
                            {{ t('customers.unpaid_fees') }}
                        </button>
                    </div>
                </section>

                <div
                    v-if="state.errors.length > 0"
                    class="alert alert-danger"
                    role="alert"
                >
                    <p
                        v-for="error in state.errors"
                        :key="`${error.code}-${error.message}`"
                    >
                        {{ error.message }}
                    </p>
                </div>

                <div class="stat-grid">
                    <div v-if="!state.unpaidMode" class="stat-card">
                        <span class="stat-label">{{
                            t('customers.total')
                        }}</span>
                        <span class="stat-value">{{
                            formatNumber(customersStats.total)
                        }}</span>
                    </div>
                    <div v-if="!state.unpaidMode" class="stat-card">
                        <span class="stat-label">{{
                            t('customers.active_on_page')
                        }}</span>
                        <span class="stat-value">{{
                            formatNumber(customersStats.active)
                        }}</span>
                    </div>
                    <div v-if="state.unpaidMode" class="stat-card">
                        <span class="stat-label">{{
                            t('customers.unpaid_bookings')
                        }}</span>
                        <span class="stat-value">{{
                            formatNumber(customersStats.unpaid)
                        }}</span>
                    </div>
                    <div v-if="state.unpaidMode" class="stat-card">
                        <span class="stat-label">{{
                            t('customers.total_due')
                        }}</span>
                        <span class="stat-value">{{ customersStats.due }}</span>
                    </div>
                </div>

                <section
                    v-if="state.formOpen && !state.unpaidMode"
                    class="form-card"
                >
                    <div class="card-head">
                        <h3>{{ formTitle }}</h3>
                    </div>

                    <form
                        class="customer-form-grid grid gap-3 md:grid-cols-4"
                        @submit.prevent="submitCustomer"
                    >
                        <label class="grid gap-1 text-sm font-medium">
                            <span>{{ t('customers.first_name') }}</span>
                            <input
                                v-model="state.form.firstname"
                                type="text"
                                class="form-control"
                                required
                            />
                        </label>
                        <label class="grid gap-1 text-sm font-medium">
                            <span>{{ t('customers.surname') }}</span>
                            <input
                                v-model="state.form.surname"
                                type="text"
                                class="form-control"
                            />
                        </label>
                        <label class="grid gap-1 text-sm font-medium">
                            <span>{{ t('customers.last_name') }}</span>
                            <input
                                v-model="state.form.lastname"
                                type="text"
                                class="form-control"
                                required
                            />
                        </label>
                        <div class="grid gap-1 text-sm font-medium">
                            <label for="customer-birth-date">{{
                                t('customers.birth_date')
                            }}</label>
                            <GermanDateInput
                                id="customer-birth-date"
                                v-model="state.form.date_birth"
                                class="form-control"
                            />
                        </div>
                        <label class="grid gap-1 text-sm font-medium">
                            <span>{{ t('customers.age') }}</span>
                            <input
                                v-model.number="state.form.age"
                                type="number"
                                min="0"
                                max="120"
                                class="form-control"
                            />
                        </label>
                        <label class="grid gap-1 text-sm font-medium">
                            <span>{{ t('customers.parent_name') }}</span>
                            <input
                                v-model="state.form.parent_name"
                                type="text"
                                class="form-control"
                            />
                        </label>
                        <label class="grid gap-1 text-sm font-medium">
                            <span>{{ t('customers.parent_phone') }}</span>
                            <input
                                v-model="state.form.parent_phone"
                                type="text"
                                class="form-control"
                            />
                        </label>
                        <label class="grid gap-1 text-sm font-medium">
                            <span>{{ t('customers.email') }}</span>
                            <input
                                v-model="state.form.email"
                                type="email"
                                class="form-control"
                            />
                        </label>

                        <div class="flex items-center gap-3 md:col-span-4">
                            <label
                                class="inline-flex items-center gap-2 text-sm"
                            >
                                <input
                                    v-model="state.form.active"
                                    type="checkbox"
                                    class="h-4 w-4"
                                />
                                {{ t('customers.active') }}
                            </label>
                            <button
                                type="submit"
                                class="btn btn-solid"
                                :disabled="state.submitting"
                            >
                                {{
                                    state.submitting
                                        ? t('customers.saving')
                                        : isEditing()
                                          ? t('customers.update_customer')
                                          : t('customers.add_customer_button')
                                }}
                            </button>
                            <button
                                type="button"
                                class="btn btn-outline"
                                @click="closeForm"
                            >
                                {{ t('common.cancel') }}
                            </button>
                        </div>
                    </form>
                </section>

                <section class="table-shell">
                    <div class="table-head">
                        <span class="badge badge--info">{{
                            t('customers.filters_badge')
                        }}</span>
                        <span class="filters-note">{{
                            state.unpaidMode
                                ? t('customers.unpaid_records')
                                : t('customers.search_pagination')
                        }}</span>
                    </div>

                    <div class="table-controls">
                        <div class="field">
                            <label for="customerSearch">{{
                                t('customers.search_label')
                            }}</label>
                            <input
                                id="customerSearch"
                                v-model="state.query"
                                type="text"
                                :placeholder="searchPlaceholder"
                                class="form-control"
                            />
                        </div>
                        <div class="table-meta">{{ summaryLabel }}</div>
                        <button
                            type="button"
                            class="btn btn-outline"
                            @click="runSearch"
                        >
                            {{ t('customers.filter') }}
                        </button>
                        <button
                            v-if="state.unpaidMode"
                            type="button"
                            class="btn btn-ghost"
                            @click="activateCustomersMode"
                        >
                            {{ t('customers.customers_mode') }}
                        </button>
                    </div>

                    <div v-if="!state.unpaidMode" class="table-wrap">
                        <table
                            class="table-hover table-bordered table-responsive customer-table table-stack-mobile table"
                        >
                            <thead>
                                <tr>
                                    <th>{{ t('customers.name') }}</th>
                                    <th>{{ t('customers.birth_age') }}</th>
                                    <th>{{ t('customers.parent_phone') }}</th>
                                    <th>{{ t('customers.email') }}</th>
                                    <th>{{ t('customers.status') }}</th>
                                    <th>{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="state.loading">
                                    <td colspan="6" class="empty-row">
                                        {{ t('customers.loading') }}
                                    </td>
                                </tr>
                                <tr
                                    v-for="customer in state.rows"
                                    :key="customer.id"
                                >
                                    <td :data-label="t('customers.name')">
                                        {{ customer.full_name }}
                                    </td>
                                    <td :data-label="t('customers.birth_age')">
                                        {{
                                            formatGermanDate(
                                                customer.date_birth,
                                                '-',
                                            )
                                        }}
                                        <template v-if="customer.age !== null">
                                            <br />
                                            {{
                                                t('customers.years', {
                                                    count: customer.age,
                                                })
                                            }}
                                        </template>
                                    </td>
                                    <td
                                        :data-label="
                                            t('customers.parent_phone')
                                        "
                                    >
                                        {{ customer.parent_phone ?? '-' }}
                                    </td>
                                    <td :data-label="t('customers.email')">
                                        {{ customer.email ?? '-' }}
                                    </td>
                                    <td :data-label="t('customers.status')">
                                        <span
                                            class="pill"
                                            :class="
                                                customer.active
                                                    ? 'pill--yes'
                                                    : 'pill--no'
                                            "
                                        >
                                            {{
                                                customer.active
                                                    ? t('common.active')
                                                    : t('common.inactive')
                                            }}
                                        </span>
                                    </td>
                                    <td
                                        class="text-center"
                                        :data-label="t('common.actions')"
                                    >
                                        <div class="action-group">
                                            <button
                                                type="button"
                                                class="icon-btn icon-btn--success"
                                                :title="t('common.view')"
                                                @click="
                                                    loadCustomerDetails(
                                                        customer.id,
                                                    )
                                                "
                                            >
                                                <span
                                                    class="glyphicon glyphicon-search"
                                                    aria-hidden="true"
                                                />
                                            </button>
                                            <button
                                                type="button"
                                                v-if="canManage"
                                                class="icon-btn icon-btn--primary"
                                                :title="t('common.edit')"
                                                @click="startEdit(customer)"
                                            >
                                                <span
                                                    class="glyphicon glyphicon-edit"
                                                    aria-hidden="true"
                                                />
                                            </button>
                                            <button
                                                type="button"
                                                v-if="canManage"
                                                class="icon-btn icon-btn--danger disabled:opacity-60"
                                                :disabled="
                                                    state.deletingId ===
                                                    customer.id
                                                "
                                                :title="t('common.delete')"
                                                @click="
                                                    removeCustomer(customer)
                                                "
                                            >
                                                <span
                                                    class="glyphicon glyphicon-trash"
                                                    aria-hidden="true"
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
                                    <td colspan="6" class="empty-row">
                                        {{ t('customers.no_rows') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else>
                        <div v-if="state.unpaidTabs.length > 0" class="tab-bar">
                            <button
                                v-for="tab in state.unpaidTabs"
                                :key="tab.id"
                                type="button"
                                class="tab-btn"
                                :class="{
                                    'is-active': activeUnpaidTab?.id === tab.id,
                                }"
                                @click="selectUnpaidTab(tab.id)"
                            >
                                {{ tab.label }} ({{ tab.count }})
                            </button>
                        </div>

                        <p
                            v-if="
                                !state.loading &&
                                activeUnpaidTab !== null &&
                                state.unpaidTabs.length > 1
                            "
                            class="filters-note"
                        >
                            {{
                                t('customers.season_total_due', {
                                    due: formatAmount(
                                        activeUnpaidTab.due_total,
                                    ),
                                })
                            }}
                        </p>

                        <div class="table-wrap">
                            <table
                                class="table-hover table-bordered table-responsive customer-table table-stack-mobile table"
                            >
                                <thead>
                                    <tr>
                                        <th>
                                            {{ t('customers.unpaid_customer') }}
                                        </th>
                                        <th>
                                            {{ t('customers.unpaid_birth') }}
                                        </th>
                                        <th>
                                            {{ t('customers.unpaid_service') }}
                                        </th>
                                        <th>{{ t('customers.unpaid_due') }}</th>
                                        <th v-if="canManage">
                                            {{ t('customers.unpaid_pay_eur') }}
                                        </th>
                                        <th>{{ t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="state.loading">
                                        <td
                                            :colspan="canManage ? 6 : 5"
                                            class="empty-row"
                                        >
                                            {{ t('customers.loading_unpaid') }}
                                        </td>
                                    </tr>
                                    <tr
                                        v-for="row in visibleUnpaidRows"
                                        :key="row.booking_id"
                                    >
                                        <td
                                            :data-label="
                                                t('customers.unpaid_customer')
                                            "
                                        >
                                            {{ row.customer_name }}
                                        </td>
                                        <td
                                            :data-label="
                                                t('customers.unpaid_birth')
                                            "
                                        >
                                            {{
                                                formatGermanDate(
                                                    row.date_birth,
                                                    '-',
                                                )
                                            }}
                                        </td>
                                        <td
                                            :data-label="
                                                t('customers.unpaid_service')
                                            "
                                        >
                                            {{ row.service_title }}
                                        </td>
                                        <td
                                            :data-label="
                                                t('customers.unpaid_due')
                                            "
                                        >
                                            {{ formatAmount(row.due) }}
                                        </td>
                                        <td
                                            v-if="canManage"
                                            :data-label="
                                                t('customers.unpaid_pay_eur')
                                            "
                                        >
                                            <div class="pay-stack">
                                                <input
                                                    v-model="
                                                        state.paymentDrafts[
                                                            row.booking_id
                                                        ]
                                                    "
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    inputmode="decimal"
                                                    :aria-label="
                                                        t(
                                                            'customers.unpaid_pay_eur',
                                                        )
                                                    "
                                                    placeholder="0.00"
                                                    class="form-control pay_input"
                                                />
                                                <button
                                                    type="button"
                                                    class="btn btn-solid"
                                                    :disabled="
                                                        state.applyingPaymentBookingId ===
                                                        row.booking_id
                                                    "
                                                    @click="
                                                        applyUnpaidPayment(row)
                                                    "
                                                >
                                                    {{
                                                        state.applyingPaymentBookingId ===
                                                        row.booking_id
                                                            ? t(
                                                                  'customers.applying',
                                                              )
                                                            : t('customers.pay')
                                                    }}
                                                </button>
                                            </div>
                                        </td>
                                        <td :data-label="t('common.actions')">
                                            <button
                                                type="button"
                                                class="btn btn-outline"
                                                @click="
                                                    openCustomerFromUnpaid(row)
                                                "
                                            >
                                                {{
                                                    t('customers.open_customer')
                                                }}
                                            </button>
                                        </td>
                                    </tr>
                                    <tr
                                        v-if="
                                            !state.loading &&
                                            visibleUnpaidRows.length === 0
                                        "
                                    >
                                        <td
                                            :colspan="canManage ? 6 : 5"
                                            class="empty-row"
                                        >
                                            {{ t('customers.no_unpaid_found') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-if="!state.unpaidMode" class="pager">
                        <button
                            type="button"
                            class="btn btn-ghost"
                            :disabled="state.pagination.current_page <= 1"
                            @click="goToPage(state.pagination.current_page - 1)"
                        >
                            {{ t('customers.prev') }}
                        </button>
                        <span id="servicePageInfo">
                            {{
                                t('customers.page_of', {
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
                            {{ t('customers.next') }}
                        </button>
                    </div>
                </section>

                <section
                    v-if="!state.unpaidMode && state.selectedCustomer !== null"
                    class="form-card"
                >
                    <div class="flex items-center justify-between gap-2">
                        <div class="card-head">
                            <h3>
                                {{
                                    t('customers.details_title', {
                                        name: state.selectedCustomer.full_name,
                                    })
                                }}
                            </h3>
                        </div>
                        <button
                            type="button"
                            class="btn btn-outline"
                            @click="
                                state.selectedCustomer = null;
                                state.selectedCustomerBookings = [];
                            "
                        >
                            {{ t('customers.close') }}
                        </button>
                    </div>

                    <div v-if="state.detailsLoading" class="filters-note">
                        {{ t('customers.loading_details') }}
                    </div>

                    <template v-else>
                        <div class="grid gap-3 text-sm md:grid-cols-3">
                            <div class="stat-card">
                                <span class="stat-label">{{
                                    t('customers.customer_id')
                                }}</span>
                                <span class="stat-value">{{
                                    state.selectedCustomer.id
                                }}</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-label">{{
                                    t('customers.parent_name')
                                }}</span>
                                <span class="stat-value">{{
                                    state.selectedCustomer.parent_name ?? '-'
                                }}</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-label">{{
                                    t('customers.parent_phone')
                                }}</span>
                                <span class="stat-value">{{
                                    state.selectedCustomer.parent_phone ?? '-'
                                }}</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-label">{{
                                    t('customers.birth_date')
                                }}</span>
                                <span class="stat-value">{{
                                    formatGermanDate(
                                        state.selectedCustomer.date_birth,
                                        '-',
                                    )
                                }}</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-label">{{
                                    t('customers.age')
                                }}</span>
                                <span class="stat-value">{{
                                    state.selectedCustomer.age ?? '-'
                                }}</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-label">{{
                                    t('customers.status')
                                }}</span>
                                <span class="stat-value">{{
                                    state.selectedCustomer.active
                                        ? t('common.active')
                                        : t('common.inactive')
                                }}</span>
                            </div>
                        </div>

                        <div class="table-shell mt-4">
                            <div class="table-head">
                                <span class="badge badge--info">{{
                                    t('customers.recent_bookings')
                                }}</span>
                            </div>
                            <div class="table-wrap">
                                <table
                                    class="table-hover table-bordered table-responsive customer-table table-stack-mobile table"
                                >
                                    <thead>
                                        <tr>
                                            <th>
                                                {{ t('customers.booking_id') }}
                                            </th>
                                            <th>{{ t('common.service') }}</th>
                                            <th>{{ t('common.dates') }}</th>
                                            <th>{{ t('common.paid') }}</th>
                                            <th>{{ t('common.remaining') }}</th>
                                            <th>{{ t('common.active') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-if="
                                                state.selectedCustomerBookings
                                                    .length === 0
                                            "
                                        >
                                            <td colspan="6" class="empty-row">
                                                {{ t('customers.no_bookings') }}
                                            </td>
                                        </tr>
                                        <tr
                                            v-for="booking in state.selectedCustomerBookings"
                                            :key="booking.id"
                                        >
                                            <td
                                                :data-label="
                                                    t('customers.booking_id')
                                                "
                                            >
                                                {{ booking.id }}
                                            </td>
                                            <td
                                                :data-label="
                                                    t('common.service')
                                                "
                                            >
                                                {{ booking.service_id ?? '-' }}
                                            </td>
                                            <td :data-label="t('common.dates')">
                                                <span
                                                    v-if="
                                                        booking.booking_dates
                                                            .length === 0
                                                    "
                                                    >-</span
                                                >
                                                <span v-else>
                                                    {{
                                                        booking.booking_dates
                                                            .map(
                                                                (row) =>
                                                                    row.slot_date,
                                                            )
                                                            .filter(
                                                                (row) =>
                                                                    row !==
                                                                    null,
                                                            )
                                                            .map((date) =>
                                                                formatGermanDate(
                                                                    date,
                                                                ),
                                                            )
                                                            .join(', ')
                                                    }}
                                                </span>
                                            </td>
                                            <td :data-label="t('common.paid')">
                                                {{
                                                    booking.paid === null
                                                        ? '—'
                                                        : formatAmount(
                                                              booking.paid,
                                                          )
                                                }}
                                            </td>
                                            <td
                                                :data-label="
                                                    t('common.remaining')
                                                "
                                            >
                                                {{
                                                    booking.due === null
                                                        ? '—'
                                                        : formatAmount(
                                                              booking.due,
                                                          )
                                                }}
                                            </td>
                                            <td
                                                :data-label="t('common.active')"
                                            >
                                                {{
                                                    booking.active
                                                        ? t('common.yes')
                                                        : t('common.no')
                                                }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </template>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
