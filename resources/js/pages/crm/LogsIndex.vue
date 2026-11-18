<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { useLogsPage } from './composables/useLogsPage';

const {
    applyFilters,
    fullUserName,
    goToPage,
    hasSelection,
    isSelected,
    rangeLabel,
    removeAllVisible,
    removeLog,
    removeSelected,
    state,
    t,
    toggleSelectAll,
    toggleSelection,
} = useLogsPage();
</script>

<template>
    <Head :title="t('logs.head')" />

    <AppLayout>
        <div class="dashboard customer-list customer-ui logs-ui">
            <div class="customer-shell">
                <section class="list-hero">
                    <div>
                        <div class="eyebrow">{{ t('logs.eyebrow') }}</div>
                        <h1>{{ t('logs.title') }}</h1>
                        <div class="subline">{{ t('logs.subtitle') }}</div>
                    </div>

                    <div class="hero-actions">
                        <button
                            type="button"
                            class="btn btn-danger"
                            :disabled="
                                state.deleting || state.rows.length === 0
                            "
                            @click="removeAllVisible"
                        >
                            {{ t('logs.delete_all') }}
                        </button>
                    </div>
                </section>

                <div v-if="state.errors.length > 0" class="alert alert-danger">
                    <p
                        v-for="error in state.errors"
                        :key="`${error.code}-${error.message}`"
                    >
                        {{ error.message }}
                    </p>
                </div>

                <section class="table-shell">
                    <div class="table-head">
                        <span class="badge badge--info">{{
                            t('logs.badge_filters')
                        }}</span>
                        <span class="filters-note">{{
                            t('logs.note_pagination')
                        }}</span>
                    </div>

                    <div class="table-controls">
                        <div class="field">
                            <label for="logsPageSize">{{
                                t('logs.per_page')
                            }}</label>
                            <select
                                id="logsPageSize"
                                v-model.number="state.perPage"
                                class="form-control"
                                @change="applyFilters"
                            >
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                                <option :value="200">200</option>
                            </select>
                        </div>

                        <div class="table-meta">{{ rangeLabel }}</div>

                        <button
                            type="button"
                            class="btn btn-solid"
                            :disabled="state.loading"
                            @click="applyFilters"
                        >
                            {{ t('common.filter') }}
                        </button>
                    </div>

                    <div style="margin-bottom: 12px">
                        <button
                            type="button"
                            class="btn btn-danger"
                            :disabled="!hasSelection || state.deleting"
                            @click="removeSelected"
                        >
                            {{ t('logs.delete_selected') }}
                        </button>
                    </div>

                    <div class="table-wrap">
                        <table
                            class="table-hover table-bordered customer-table table-stack-mobile table"
                        >
                            <thead>
                                <tr>
                                    <th style="width: 36px; text-align: center">
                                        <input
                                            type="checkbox"
                                            :checked="
                                                state.rows.length > 0 &&
                                                state.selectedIds.length ===
                                                    state.rows.length
                                            "
                                            @change="toggleSelectAll"
                                        />
                                    </th>
                                    <th>{{ t('logs.col_user') }}</th>
                                    <th>{{ t('logs.col_page') }}</th>
                                    <th>{{ t('logs.col_ip') }}</th>
                                    <th>{{ t('logs.col_location') }}</th>
                                    <th>{{ t('logs.col_date') }}</th>
                                    <th>{{ t('common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="state.loading">
                                    <td colspan="7" class="empty-row">
                                        {{ t('logs.loading') }}
                                    </td>
                                </tr>

                                <tr v-for="row in state.rows" :key="row.id">
                                    <td
                                        class="text-center"
                                        :data-label="
                                            t('common.select') || 'Select'
                                        "
                                    >
                                        <input
                                            :checked="isSelected(row.id)"
                                            type="checkbox"
                                            @change="toggleSelection(row.id)"
                                        />
                                    </td>
                                    <td :data-label="t('logs.col_user')">
                                        {{ fullUserName(row) }}
                                    </td>
                                    <td :data-label="t('logs.col_page')">
                                        {{ row.page || '-' }}
                                    </td>
                                    <td :data-label="t('logs.col_ip')">
                                        {{ row.ip || '-' }}
                                    </td>
                                    <td :data-label="t('logs.col_location')">
                                        {{ row.location ?? '-' }}
                                    </td>
                                    <td :data-label="t('logs.col_date')">
                                        {{ row.date ?? '-' }}
                                    </td>
                                    <td
                                        class="text-center"
                                        :data-label="t('common.actions')"
                                    >
                                        <button
                                            type="button"
                                            class="icon-btn icon-btn--danger"
                                            :disabled="state.deleting"
                                            @click="removeLog(row.id)"
                                        >
                                            <span
                                                class="glyphicon glyphicon-trash"
                                            />
                                        </button>
                                    </td>
                                </tr>

                                <tr
                                    v-if="
                                        !state.loading &&
                                        state.rows.length === 0
                                    "
                                >
                                    <td colspan="7" class="empty-row">
                                        No records.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pager">
                        <button
                            type="button"
                            class="btn btn-ghost"
                            :disabled="state.pagination.current_page <= 1"
                            @click="goToPage(state.pagination.current_page - 1)"
                        >
                            {{ t('common.previous') }}
                        </button>
                        <span id="servicePageInfo">{{
                            t('common.page_of', {
                                current: state.pagination.current_page,
                                last: state.pagination.last_page,
                            })
                        }}</span>
                        <button
                            type="button"
                            class="btn btn-ghost"
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
        </div>
    </AppLayout>
</template>
