<script setup lang="ts">
import { useIssuePageContext } from '@/pages/crm/composables/issuePageContext';

const {
    applyFilters,
    displayUser,
    formatLabel,
    formatPriorityLabel,
    formatStatusLabel,
    goToPage,
    moduleOptions,
    openIssue,
    priorityBadgeClass,
    setScope,
    state,
    statusBadgeClass,
    summaryLabel,
    t,
} = useIssuePageContext();
</script>

<template>
    <section class="table-shell">
        <div class="table-head">
            <span class="badge badge--info">{{
                t('issues.reports_list')
            }}</span>
            <span class="filters-note">{{ t('issues.sorted_note') }}</span>
        </div>

        <div class="issues-scope-tabs">
            <button
                type="button"
                class="issues-scope-tab"
                :class="{
                    'is-active': state.filters.scope === 'all',
                }"
                @click="setScope('all')"
            >
                {{ t('issues.scope_all') }}
            </button>
            <button
                type="button"
                class="issues-scope-tab"
                :class="{
                    'is-active': state.filters.scope === 'assigned',
                }"
                @click="setScope('assigned')"
            >
                {{ t('issues.scope_assigned') }}
            </button>
            <button
                type="button"
                class="issues-scope-tab"
                :class="{
                    'is-active': state.filters.scope === 'created',
                }"
                @click="setScope('created')"
            >
                {{ t('issues.scope_created') }}
            </button>
        </div>

        <div class="table-controls">
            <div class="field">
                <label for="issueStatus">{{ t('issues.status') }}</label>
                <select
                    id="issueStatus"
                    v-model="state.filters.status"
                    class="form-control"
                    @change="applyFilters"
                >
                    <option value="all">
                        {{ t('issues.all_statuses') }}
                    </option>
                    <option value="open">
                        {{ t('issues.open') }}
                    </option>
                    <option value="in_progress">
                        {{ t('issues.in_progress') }}
                    </option>
                    <option value="resolved">
                        {{ t('issues.resolved') }}
                    </option>
                </select>
            </div>

            <div class="field">
                <label for="issueModuleFilter">{{ t('issues.module') }}</label>
                <select
                    id="issueModuleFilter"
                    v-model="state.filters.module"
                    class="form-control"
                    @change="applyFilters"
                >
                    <option value="all">
                        {{ t('issues.all_modules') }}
                    </option>
                    <option
                        v-for="module in moduleOptions"
                        :key="module"
                        :value="module"
                    >
                        {{ formatLabel(module) }}
                    </option>
                </select>
            </div>

            <div class="table-meta">{{ summaryLabel }}</div>

            <button
                type="button"
                class="btn btn-solid"
                :disabled="state.loading"
                @click="applyFilters"
            >
                {{ t('common.filter') }}
            </button>
        </div>

        <div class="table-wrap">
            <table
                class="table-hover table-bordered customer-table table-stack-mobile table"
            >
                <thead>
                    <tr>
                        <th>{{ t('issues.col_id') }}</th>
                        <th>{{ t('issues.col_module') }}</th>
                        <th>{{ t('issues.col_issue_type') }}</th>
                        <th>{{ t('issues.col_priority') }}</th>
                        <th>{{ t('issues.col_status') }}</th>
                        <th>{{ t('issues.col_assignee') }}</th>
                        <th>{{ t('issues.col_created_by') }}</th>
                        <th>{{ t('issues.col_created_at') }}</th>
                        <th>{{ t('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="state.loading">
                        <td colspan="9" class="empty-row">
                            {{ t('issues.loading') }}
                        </td>
                    </tr>

                    <tr
                        v-for="row in state.rows"
                        :key="row.id"
                        class="issues-row-clickable"
                        tabindex="0"
                        role="button"
                        @click="openIssue(row.id)"
                        @keydown.enter.prevent="openIssue(row.id)"
                        @keydown.space.prevent="openIssue(row.id)"
                    >
                        <td data-label="ID" data-row-hint="Open report">
                            #{{ row.id }}
                            <div v-if="row.title" class="issues-meta">
                                {{ row.title }}
                            </div>
                        </td>
                        <td data-label="Module" data-row-hint="Open report">
                            {{ formatLabel(row.module_key) }}
                        </td>
                        <td data-label="Issue type" data-row-hint="Open report">
                            {{ formatLabel(row.issue_key) }}
                        </td>
                        <td data-label="Priority" data-row-hint="Open report">
                            <span
                                class="badge"
                                :class="priorityBadgeClass(row.priority)"
                            >
                                {{ formatPriorityLabel(row.priority) }}
                            </span>
                        </td>
                        <td data-label="Status" data-row-hint="Open report">
                            <span
                                class="badge"
                                :class="statusBadgeClass(row.status)"
                            >
                                {{ formatStatusLabel(row.status) }}
                            </span>
                        </td>
                        <td data-label="Assignee">
                            {{ displayUser(row.assignee, row.assigned_to) }}
                        </td>
                        <td data-label="Created by">
                            {{ displayUser(row.reporter, row.created_by) }}
                        </td>
                        <td data-label="Created at">
                            {{ row.created_at ?? '-' }}
                        </td>
                        <td data-label="Actions">
                            <button
                                type="button"
                                class="btn btn-ghost btn-xs"
                                :disabled="state.updating"
                                @click.stop="openIssue(row.id)"
                            >
                                {{ t('common.view') }}
                            </button>
                        </td>
                    </tr>

                    <tr v-if="!state.loading && state.rows.length === 0">
                        <td colspan="9" class="empty-row">
                            {{ t('issues.no_reports') }}
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
            <span id="servicePageInfo">
                {{
                    t('common.page_of', {
                        current: state.pagination.current_page,
                        last: state.pagination.last_page,
                    })
                }}
            </span>
            <button
                type="button"
                class="btn btn-ghost"
                :disabled="
                    state.pagination.current_page >= state.pagination.last_page
                "
                @click="goToPage(state.pagination.current_page + 1)"
            >
                {{ t('common.next') }}
            </button>
        </div>
    </section>
</template>
