<script setup lang="ts">
import GermanDateInput from '@/components/GermanDateInput.vue';
import { useUiI18n } from '@/composables/useUiI18n';
import AppLayout from '@/layouts/AppLayout.vue';
import { useReportsPage } from '@/pages/crm/composables/useReportsPage';
import { Head } from '@inertiajs/vue3';
const {
    t,
    state,
    isDriverView,
    effectiveColumns,
    stats,
    reportPeriodLabel,
    selectedServiceLabel,
    displayRows,
    tfootSummaryLabel,
    visibleColumnCount,
    canLoadPreviousPage,
    canLoadNextPage,
    applyPreset,
    formatDateLabel,
    formatCurrencyPair,
    generateReport,
    goToPage,
    onPerPageChange,
    exportPageCsv,
    exportAllCsv,
    printPage,
    printAll,
} = useReportsPage();

const { formatNumber } = useUiI18n();
</script>

<template>
    <Head :title="t('reports.head')">
        <link rel="stylesheet" href="/css/reports.css?v=legacy-20260302" />
    </Head>

    <AppLayout>
        <div
            class="dashboard customer-ui customer-list report-ui"
            :class="{ 'is-compact': state.compact }"
        >
            <div class="customer-shell">
                <section class="report-hero list-hero">
                    <div>
                        <div class="eyebrow">{{ t('reports.eyebrow') }}</div>
                        <h1>{{ t('reports.title') }}</h1>
                        <div class="subline">{{ t('reports.subtitle') }}</div>
                    </div>
                    <div class="hero-actions no-print">
                        <button
                            type="button"
                            class="btn btn-outline"
                            :disabled="
                                state.exportingAllCsv ||
                                state.loading ||
                                !state.report
                            "
                            @click="exportAllCsv"
                        >
                            <span
                                class="glyphicon glyphicon-download"
                                aria-hidden="true"
                            />
                            {{
                                state.exportingAllCsv
                                    ? t('reports.csv_all_loading')
                                    : t('reports.csv_all')
                            }}
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline"
                            :disabled="
                                state.exportingPageCsv ||
                                state.loading ||
                                !state.report
                            "
                            @click="exportPageCsv"
                        >
                            <span
                                class="glyphicon glyphicon-download"
                                aria-hidden="true"
                            />
                            {{
                                state.exportingPageCsv
                                    ? t('reports.csv_page_loading')
                                    : t('reports.csv_page')
                            }}
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline"
                            :disabled="state.loading || !state.report"
                            @click="printPage"
                        >
                            <span
                                class="glyphicon glyphicon-print"
                                aria-hidden="true"
                            />
                            {{ t('reports.print_page') }}
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline"
                            :disabled="
                                state.printingAll ||
                                state.loading ||
                                !state.report
                            "
                            @click="printAll"
                        >
                            <span
                                class="glyphicon glyphicon-print"
                                aria-hidden="true"
                            />
                            {{
                                state.printingAll
                                    ? t('reports.print_all_loading')
                                    : t('reports.print_all')
                            }}
                        </button>
                    </div>
                </section>

                <div
                    v-if="state.errors.length > 0"
                    class="alert alert-danger no-print"
                >
                    <p
                        v-for="error in state.errors"
                        :key="`${error.code}-${error.message}`"
                    >
                        {{ error.message }}
                    </p>
                </div>

                <form
                    class="report-filters customer-form no-print"
                    @submit.prevent="generateReport"
                >
                    <div class="card-head">
                        <h3>{{ t('reports.filters') }}</h3>
                        <span class="badge badge--info">{{
                            t('reports.create_for_period')
                        }}</span>
                    </div>
                    <div class="filter-grid">
                        <div class="field">
                            <label for="report-service">{{
                                t('reports.select_service')
                            }}</label>
                            <select
                                id="report-service"
                                v-model="state.filters.service_id"
                                class="form-control"
                            >
                                <option value="">
                                    {{ t('reports.all_services') }}
                                </option>
                                <option
                                    v-for="service in state.services"
                                    :key="service.id"
                                    :value="String(service.id)"
                                >
                                    {{ service.title }}
                                </option>
                            </select>
                            <p v-if="state.serviceOptionsError" role="alert">
                                {{ t('reports.services_load_failed') }}
                            </p>
                        </div>

                        <div class="field">
                            <label for="report-date-from">{{
                                t('common.from')
                            }}</label>
                            <GermanDateInput
                                id="report-date-from"
                                v-model="state.filters.from"
                                class="form-control"
                                required
                            />
                        </div>

                        <div class="field">
                            <label for="report-date-to">{{
                                t('common.to')
                            }}</label>
                            <GermanDateInput
                                id="report-date-to"
                                v-model="state.filters.to"
                                class="form-control"
                                required
                            />
                        </div>
                    </div>

                    <div class="filter-presets">
                        <button
                            type="button"
                            class="btn btn-outline"
                            :class="{
                                'is-active': state.activePreset === 'today',
                            }"
                            @click="applyPreset('today')"
                        >
                            {{ t('reports.today') }}
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline"
                            :class="{
                                'is-active': state.activePreset === 'week',
                            }"
                            @click="applyPreset('week')"
                        >
                            {{ t('reports.this_week') }}
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline"
                            :class="{
                                'is-active': state.activePreset === 'month',
                            }"
                            @click="applyPreset('month')"
                        >
                            {{ t('reports.this_month') }}
                        </button>
                        <button
                            type="button"
                            class="btn btn-outline"
                            :class="{
                                'is-active': state.activePreset === 'last',
                            }"
                            @click="applyPreset('last')"
                        >
                            {{ t('reports.last') }}
                        </button>
                    </div>

                    <div class="filter-actions">
                        <button
                            type="submit"
                            class="btn btn-solid"
                            :disabled="state.loading"
                        >
                            {{
                                state.loading
                                    ? t('reports.generating')
                                    : t('reports.generate')
                            }}
                        </button>
                    </div>
                </form>

                <div class="stat-grid">
                    <div class="stat-card">
                        <span class="stat-label">{{
                            t('reports.records')
                        }}</span>
                        <span class="stat-value">{{
                            formatNumber(stats.records)
                        }}</span>
                    </div>
                    <div v-if="!isDriverView" class="stat-card admin-only">
                        <span class="stat-label">{{
                            t('reports.total_amount')
                        }}</span>
                        <span class="stat-value">{{
                            formatCurrencyPair(stats.sum)
                        }}</span>
                    </div>
                    <div v-if="!isDriverView" class="stat-card admin-only">
                        <span class="stat-label">{{ t('reports.paid') }}</span>
                        <span class="stat-value">{{
                            formatCurrencyPair(stats.paid)
                        }}</span>
                    </div>
                    <div v-if="!isDriverView" class="stat-card admin-only">
                        <span class="stat-label">{{
                            t('reports.remaining')
                        }}</span>
                        <span class="stat-value">{{
                            formatCurrencyPair(stats.due)
                        }}</span>
                    </div>
                </div>

                <div class="report-title">
                    <h3>
                        {{ t('reports.title_period') }}
                        <span>{{ reportPeriodLabel }}</span>
                    </h3>
                    <div class="report-sub">
                        {{
                            t('reports.service_due_line', {
                                service: selectedServiceLabel,
                                count: state.report?.summary.due_count ?? 0,
                            })
                        }}
                    </div>
                </div>

                <div class="table-shell">
                    <div class="table-head">
                        <span class="badge badge--info">{{
                            t('reports.detailed')
                        }}</span>
                        <div class="table-actions no-print">
                            <button
                                type="button"
                                class="btn btn-outline density-toggle"
                                @click="state.compact = !state.compact"
                            >
                                {{ t('reports.compact') }}
                            </button>
                            <details class="column-toggle">
                                <summary>{{ t('reports.columns') }}</summary>
                                <div class="column-panel">
                                    <label>
                                        <input
                                            v-model="state.columns.name"
                                            type="checkbox"
                                            class="col-toggle"
                                            data-col="name"
                                            disabled
                                        />
                                        {{ t('reports.name') }}
                                    </label>
                                    <label v-if="!isDriverView">
                                        <input
                                            v-model="state.columns.birth"
                                            type="checkbox"
                                            class="col-toggle"
                                            data-col="birth"
                                        />
                                        {{ t('reports.birth_date') }}
                                    </label>
                                    <label v-if="!isDriverView">
                                        <input
                                            v-model="state.columns.level"
                                            type="checkbox"
                                            class="col-toggle"
                                            data-col="level"
                                        />
                                        {{ t('reports.level') }}
                                    </label>
                                    <label v-if="!isDriverView">
                                        <input
                                            v-model="state.columns.equip"
                                            type="checkbox"
                                            class="col-toggle"
                                            data-col="equip"
                                        />
                                        {{ t('reports.equipment') }}
                                    </label>
                                    <label>
                                        <input
                                            v-model="state.columns.location"
                                            type="checkbox"
                                            class="col-toggle"
                                            data-col="location"
                                            disabled
                                        />
                                        {{ t('reports.location') }}
                                    </label>
                                    <label v-if="!isDriverView">
                                        <input
                                            v-model="state.columns.due"
                                            type="checkbox"
                                            class="col-toggle"
                                            data-col="due"
                                        />
                                        {{ t('reports.due') }}
                                    </label>
                                    <label v-if="!isDriverView">
                                        <input
                                            v-model="state.columns.payment"
                                            type="checkbox"
                                            class="col-toggle"
                                            data-col="payment"
                                        />
                                        {{ t('reports.payment') }}
                                    </label>
                                    <label>
                                        <input
                                            v-model="state.columns.parent"
                                            type="checkbox"
                                            class="col-toggle"
                                            data-col="parent"
                                        />
                                        {{ t('reports.parent') }}
                                    </label>
                                </div>
                            </details>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table
                            class="table-hover table-bordered table-responsive report-table customer-table table-stack-mobile table"
                        >
                            <thead>
                                <tr>
                                    <th
                                        v-if="effectiveColumns.name"
                                        data-col="name"
                                    >
                                        {{ t('reports.name') }}
                                    </th>
                                    <th
                                        v-if="effectiveColumns.birth"
                                        data-col="birth"
                                    >
                                        {{ t('reports.birth_date') }}
                                    </th>
                                    <th
                                        v-if="effectiveColumns.level"
                                        data-col="level"
                                    >
                                        {{ t('reports.level') }}
                                    </th>
                                    <th
                                        v-if="effectiveColumns.equip"
                                        data-col="equip"
                                    >
                                        {{ t('reports.equipment') }}
                                    </th>
                                    <th
                                        v-if="effectiveColumns.location"
                                        data-col="location"
                                    >
                                        {{ t('reports.location') }}
                                    </th>
                                    <th
                                        v-if="effectiveColumns.due"
                                        data-col="due"
                                    >
                                        {{ t('reports.due') }}
                                    </th>
                                    <th
                                        v-if="effectiveColumns.payment"
                                        data-col="payment"
                                    >
                                        {{ t('reports.payment') }}
                                    </th>
                                    <th
                                        v-if="effectiveColumns.parent"
                                        data-col="parent"
                                    >
                                        {{ t('reports.parent') }}
                                    </th>
                                </tr>
                                <tr class="tablesorter-filter-row no-print">
                                    <th
                                        v-if="effectiveColumns.name"
                                        data-col="name"
                                    >
                                        <input
                                            v-model="state.tableFilters.name"
                                            class="tablesorter-filter"
                                            type="text"
                                        />
                                    </th>
                                    <th
                                        v-if="effectiveColumns.birth"
                                        data-col="birth"
                                    >
                                        <input
                                            v-model="state.tableFilters.birth"
                                            class="tablesorter-filter"
                                            type="text"
                                        />
                                    </th>
                                    <th
                                        v-if="effectiveColumns.level"
                                        data-col="level"
                                    >
                                        <input
                                            v-model="state.tableFilters.level"
                                            class="tablesorter-filter"
                                            type="text"
                                        />
                                    </th>
                                    <th
                                        v-if="effectiveColumns.equip"
                                        data-col="equip"
                                    >
                                        <input
                                            v-model="state.tableFilters.equip"
                                            class="tablesorter-filter"
                                            type="text"
                                        />
                                    </th>
                                    <th
                                        v-if="effectiveColumns.location"
                                        data-col="location"
                                    >
                                        <input
                                            v-model="
                                                state.tableFilters.location
                                            "
                                            class="tablesorter-filter"
                                            type="text"
                                        />
                                    </th>
                                    <th
                                        v-if="effectiveColumns.due"
                                        data-col="due"
                                    >
                                        <input
                                            v-model="state.tableFilters.due"
                                            class="tablesorter-filter"
                                            type="text"
                                        />
                                    </th>
                                    <th
                                        v-if="effectiveColumns.payment"
                                        data-col="payment"
                                    >
                                        <input
                                            v-model="state.tableFilters.payment"
                                            class="tablesorter-filter"
                                            type="text"
                                        />
                                    </th>
                                    <th
                                        v-if="effectiveColumns.parent"
                                        data-col="parent"
                                    >
                                        <input
                                            v-model="state.tableFilters.parent"
                                            class="tablesorter-filter"
                                            type="text"
                                        />
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="state.loading">
                                    <td
                                        :colspan="visibleColumnCount"
                                        class="empty-row"
                                    >
                                        {{ t('reports.loading') }}
                                    </td>
                                </tr>
                                <tr
                                    v-for="row in displayRows"
                                    :key="row.bookingId"
                                >
                                    <td
                                        v-if="effectiveColumns.name"
                                        data-col="name"
                                        :data-label="t('reports.name')"
                                    >
                                        <strong>{{ row.name }}</strong>
                                    </td>
                                    <td
                                        v-if="effectiveColumns.birth"
                                        data-col="birth"
                                        :data-label="t('reports.birth_date')"
                                    >
                                        {{ row.birth }}
                                    </td>
                                    <td
                                        v-if="effectiveColumns.level"
                                        data-col="level"
                                        :data-label="t('reports.level')"
                                    >
                                        {{ row.level }}
                                    </td>
                                    <td
                                        v-if="effectiveColumns.equip"
                                        data-col="equip"
                                        :data-label="t('reports.equipment')"
                                    >
                                        {{ row.equip }}
                                    </td>
                                    <td
                                        v-if="effectiveColumns.location"
                                        data-col="location"
                                        :data-label="t('reports.location')"
                                    >
                                        {{ row.location }}
                                        <div
                                            v-if="row.dates.length > 0"
                                            class="chip-row report-dates"
                                        >
                                            <span
                                                v-for="date in row.dates"
                                                :key="`${row.bookingId}-${date}`"
                                                class="chip"
                                            >
                                                {{ formatDateLabel(date) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td
                                        v-if="effectiveColumns.due"
                                        data-col="due"
                                        :data-label="t('reports.due')"
                                    >
                                        <span
                                            class="pill"
                                            :class="
                                                row.dueRaw > 0.01
                                                    ? 'pill--no'
                                                    : 'pill--yes'
                                            "
                                            >{{ row.due }}</span
                                        >
                                    </td>
                                    <td
                                        v-if="effectiveColumns.payment"
                                        data-col="payment"
                                        :data-label="t('reports.payment')"
                                    >
                                        {{ row.payment }}
                                    </td>
                                    <td
                                        v-if="effectiveColumns.parent"
                                        data-col="parent"
                                        :data-label="t('reports.parent')"
                                    >
                                        <span>{{ row.parent }}</span>
                                        <template v-if="row.parentPhone !== ''">
                                            <br />
                                            <a
                                                class="link"
                                                :href="`tel:${row.parentPhone}`"
                                                >{{ row.parentPhone }}</a
                                            >
                                        </template>
                                        <template
                                            v-if="
                                                !isDriverView &&
                                                row.parentEmail !== ''
                                            "
                                        >
                                            <br />
                                            <span class="admin-only">{{
                                                row.parentEmail
                                            }}</span>
                                        </template>
                                    </td>
                                </tr>
                                <tr
                                    v-if="
                                        !state.loading &&
                                        displayRows.length === 0
                                    "
                                >
                                    <td
                                        :colspan="visibleColumnCount"
                                        class="empty-row"
                                    >
                                        {{ t('reports.no_results') }}
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot v-if="!isDriverView" class="admin-only">
                                <tr>
                                    <td :colspan="visibleColumnCount">
                                        <div class="tfoot-summary">
                                            {{ tfootSummaryLabel }}
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="report-pager no-print">
                    <button
                        type="button"
                        class="btn btn-outline first"
                        :disabled="!canLoadPreviousPage"
                        @click="goToPage(1)"
                    >
                        «
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline prev"
                        :disabled="!canLoadPreviousPage"
                        @click="goToPage(state.pagination.current_page - 1)"
                    >
                        ←
                    </button>
                    <span class="pagedisplay">{{
                        t('reports.page_of', {
                            current: state.pagination.current_page,
                            last: state.pagination.last_page,
                        })
                    }}</span>
                    <button
                        type="button"
                        class="btn btn-outline next"
                        :disabled="!canLoadNextPage"
                        @click="goToPage(state.pagination.current_page + 1)"
                    >
                        →
                    </button>
                    <button
                        type="button"
                        class="btn btn-outline last"
                        :disabled="!canLoadNextPage"
                        @click="goToPage(state.pagination.last_page)"
                    >
                        »
                    </button>
                    <select
                        class="pagesize form-control"
                        :value="String(state.perPage)"
                        @change="
                            onPerPageChange(
                                ($event.target as HTMLSelectElement).value,
                            )
                        "
                    >
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>

                <div class="results-meta">
                    {{
                        t('reports.results_meta', {
                            start:
                                displayRows.length > 0
                                    ? (state.pagination.current_page - 1) *
                                          state.pagination.per_page +
                                      1
                                    : 0,
                            end:
                                (state.pagination.current_page - 1) *
                                    state.pagination.per_page +
                                displayRows.length,
                            total: state.pagination.total,
                        })
                    }}
                </div>
            </div>
        </div>
    </AppLayout>
</template>
