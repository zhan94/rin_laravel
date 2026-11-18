<script setup lang="ts">
import { useIssuePageContext } from '@/pages/crm/composables/issuePageContext';

const {
    availableIssueKeys,
    displayUser,
    formatLabel,
    formatPriorityLabel,
    moduleOptions,
    onModuleChange,
    onScreenshotsChange,
    state,
    submitCreateIssue,
    t,
    toggleCreateForm,
} = useIssuePageContext();
</script>

<template>
    <section class="table-shell issues-create-shell">
        <div class="table-head issues-create-head">
            <div class="issues-create-meta">
                <span class="badge badge--accent">{{
                    t('issues.new_report')
                }}</span>
                <span class="filters-note">{{
                    t('issues.new_report_note')
                }}</span>
            </div>
            <button
                type="button"
                class="btn btn-ghost btn-xs issues-create-toggle"
                :aria-expanded="state.createFormOpen ? 'true' : 'false'"
                @click="toggleCreateForm"
            >
                {{
                    state.createFormOpen
                        ? t('issues.hide_form')
                        : t('issues.show_form')
                }}
            </button>
        </div>

        <div
            class="issues-create-wrap"
            :class="{ 'is-collapsed': !state.createFormOpen }"
        >
            <div class="issues-form-grid">
                <div class="field">
                    <label for="issueModule">{{ t('issues.module') }}</label>
                    <select
                        id="issueModule"
                        v-model="state.createForm.module_key"
                        class="form-control"
                        @change="onModuleChange"
                    >
                        <option
                            v-for="module in moduleOptions"
                            :key="module"
                            :value="module"
                        >
                            {{ formatLabel(module) }}
                        </option>
                    </select>
                </div>

                <div class="field field--wide">
                    <label for="issueType">{{ t('issues.issue_type') }}</label>
                    <select
                        id="issueType"
                        v-model="state.createForm.issue_key"
                        class="form-control"
                    >
                        <option
                            v-for="issueKey in availableIssueKeys"
                            :key="issueKey"
                            :value="issueKey"
                        >
                            {{ formatLabel(issueKey) }}
                        </option>
                    </select>
                </div>

                <div class="field field--wide issues-title-field">
                    <label for="issueTitle">{{
                        t('issues.short_title')
                    }}</label>
                    <input
                        id="issueTitle"
                        v-model="state.createForm.title"
                        type="text"
                        maxlength="190"
                        class="form-control"
                        :placeholder="t('issues.short_title')"
                    />
                </div>

                <div class="field">
                    <label for="issueCreateAssignee">{{
                        t('issues.assign_to')
                    }}</label>
                    <select
                        id="issueCreateAssignee"
                        v-model="state.createForm.assigned_to"
                        class="form-control"
                    >
                        <option :value="null">
                            {{ t('issues.unassigned') }}
                        </option>
                        <option
                            v-for="admin in state.assignableAdmins"
                            :key="admin.id"
                            :value="admin.id"
                        >
                            {{ displayUser(admin, admin.id) }}
                        </option>
                    </select>
                </div>

                <div class="field">
                    <label for="issuePriority">{{
                        t('issues.priority')
                    }}</label>
                    <select
                        id="issuePriority"
                        v-model="state.createForm.priority"
                        class="form-control"
                    >
                        <option
                            v-for="priority in state.allowedPriorities"
                            :key="priority"
                            :value="priority"
                        >
                            {{ formatPriorityLabel(priority) }}
                        </option>
                    </select>
                </div>

                <div class="field field--full issues-description-field">
                    <label for="issueDescription">{{
                        t('issues.details')
                    }}</label>
                    <textarea
                        id="issueDescription"
                        v-model="state.createForm.description"
                        maxlength="4000"
                        rows="4"
                        class="form-control"
                        :placeholder="t('issues.details_placeholder')"
                    />
                </div>

                <div class="field field--wide issues-screenshots-field">
                    <label for="issueScreenshots">{{
                        t('issues.screenshots_optional')
                    }}</label>
                    <input
                        id="issueScreenshots"
                        :key="state.fileInputKey"
                        type="file"
                        multiple
                        accept=".jpg,.jpeg,.png,.webp,.gif"
                        class="form-control issues-file-input"
                        @change="onScreenshotsChange"
                    />
                    <div class="issues-help-text">
                        {{ t('issues.screenshots_note') }}
                    </div>
                </div>

                <div class="field issues-page-url-field">
                    <label for="issuePageUrl">{{
                        t('issues.page_optional')
                    }}</label>
                    <input
                        id="issuePageUrl"
                        v-model="state.createForm.page_url"
                        type="text"
                        maxlength="255"
                        class="form-control"
                        :placeholder="t('issues.page_placeholder')"
                    />
                </div>

                <div class="field-actions">
                    <button
                        type="button"
                        class="btn btn-solid"
                        :disabled="state.submitting"
                        @click="submitCreateIssue"
                    >
                        {{
                            state.submitting
                                ? t('issues.submitting')
                                : t('issues.submit_report')
                        }}
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>
