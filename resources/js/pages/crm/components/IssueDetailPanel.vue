<script setup lang="ts">
import { useIssuePageContext } from '@/pages/crm/composables/issuePageContext';

const {
    displayUser,
    formatLabel,
    formatPriorityLabel,
    formatStatusLabel,
    issueAttachmentUrl,
    state,
    submitAssigneeUpdate,
    submitComment,
    submitStatusUpdate,
    t,
    toggleAttachments,
} = useIssuePageContext();
</script>

<template>
    <section v-if="state.selectedIssue !== null" class="table-shell">
        <div class="table-head">
            <span class="badge badge--accent">{{
                t('issues.selected_report')
            }}</span>
            <span class="filters-note">{{
                t('issues.ticket_number', {
                    id: state.selectedIssue.id,
                })
            }}</span>
        </div>

        <div class="issues-detail-grid">
            <div class="issues-detail-item">
                <span class="issues-detail-label">{{
                    t('issues.module')
                }}</span>
                <span class="issues-detail-value">{{
                    formatLabel(state.selectedIssue.module_key)
                }}</span>
            </div>
            <div class="issues-detail-item">
                <span class="issues-detail-label">{{
                    t('issues.issue_type')
                }}</span>
                <span class="issues-detail-value">{{
                    formatLabel(state.selectedIssue.issue_key)
                }}</span>
            </div>
            <div class="issues-detail-item">
                <span class="issues-detail-label">{{
                    t('issues.priority')
                }}</span>
                <span class="issues-detail-value">{{
                    formatPriorityLabel(state.selectedIssue.priority)
                }}</span>
            </div>
            <div class="issues-detail-item">
                <span class="issues-detail-label">{{
                    t('issues.status')
                }}</span>
                <span class="issues-detail-value">{{
                    formatStatusLabel(state.selectedIssue.status)
                }}</span>
            </div>
            <div class="issues-detail-item">
                <span class="issues-detail-label">{{
                    t('issues.reporter')
                }}</span>
                <span class="issues-detail-value">{{
                    displayUser(
                        state.selectedIssue.reporter,
                        state.selectedIssue.created_by,
                    )
                }}</span>
            </div>
            <div class="issues-detail-item">
                <span class="issues-detail-label">{{
                    t('issues.col_assignee')
                }}</span>
                <span class="issues-detail-value">{{
                    displayUser(
                        state.selectedIssue.assignee,
                        state.selectedIssue.assigned_to,
                    )
                }}</span>
            </div>
            <div class="issues-detail-item">
                <span class="issues-detail-label">{{
                    t('issues.col_created_at')
                }}</span>
                <span class="issues-detail-value">{{
                    state.selectedIssue.created_at ?? '-'
                }}</span>
            </div>
            <div class="issues-detail-item">
                <span class="issues-detail-label">{{ t('issues.page') }}</span>
                <span class="issues-detail-value">{{
                    state.selectedIssue.page_url ?? '-'
                }}</span>
            </div>
        </div>

        <div class="issues-detail-body">
            <h3>
                {{
                    state.selectedIssue.title ||
                    `Issue #${state.selectedIssue.id}`
                }}
            </h3>
            <p>
                {{
                    state.selectedIssue.description ??
                    t('issues.no_description')
                }}
            </p>
        </div>

        <div class="issues-status-inline">
            <div class="field">
                <label for="issueDetailStatus">{{ t('issues.status') }}</label>
                <select
                    id="issueDetailStatus"
                    v-model="state.statusForm.status"
                    class="form-control"
                >
                    <option
                        v-for="status in state.allowedStatuses"
                        :key="status"
                        :value="status"
                    >
                        {{ formatStatusLabel(status) }}
                    </option>
                </select>
            </div>

            <div class="field">
                <label for="issueDetailAssignee">{{
                    t('issues.assign_to')
                }}</label>
                <select
                    id="issueDetailAssignee"
                    v-model="state.assigneeForm.assigned_to"
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
                <label for="issueStatusComment">{{
                    t('issues.status_note')
                }}</label>
                <textarea
                    id="issueStatusComment"
                    v-model="state.statusForm.comment"
                    rows="2"
                    maxlength="4000"
                    class="form-control issues-status-comment"
                    :placeholder="t('issues.status_note_placeholder')"
                />
            </div>

            <button
                type="button"
                class="btn btn-outline"
                :disabled="state.updating"
                @click="submitStatusUpdate"
            >
                {{ t('issues.save_status') }}
            </button>
            <button
                type="button"
                class="btn btn-outline"
                :disabled="state.updating"
                @click="submitAssigneeUpdate"
            >
                {{ t('issues.save_assignee') }}
            </button>
        </div>

        <div class="table-head issues-attachments-head">
            <div class="issues-attachments-meta">
                <span class="badge badge--info">{{
                    t('issues.attachments')
                }}</span>
                <span class="filters-note">
                    {{
                        state.selectedIssue.attachments.length === 1
                            ? t('issues.file_count', {
                                  count: state.selectedIssue.attachments.length,
                              })
                            : t('issues.file_count_plural', {
                                  count: state.selectedIssue.attachments.length,
                              })
                    }}
                </span>
            </div>
            <button
                type="button"
                class="btn btn-ghost btn-xs issues-attachments-toggle"
                @click="toggleAttachments"
            >
                {{
                    state.attachmentsOpen
                        ? t('issues.hide_attachments')
                        : t('issues.show_attachments')
                }}
            </button>
        </div>

        <div
            class="issues-attachments-wrap"
            :class="{ 'is-collapsed': !state.attachmentsOpen }"
        >
            <div
                v-if="state.selectedIssue.attachments.length > 0"
                class="issues-attachments-grid"
            >
                <article
                    v-for="attachment in state.selectedIssue.attachments"
                    :key="attachment.id"
                    class="issues-attachment-card"
                >
                    <a
                        :href="issueAttachmentUrl(attachment.id)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="issues-attachment-image-wrap"
                    >
                        <span
                            class="issues-attachment-image-fallback"
                            style="display: flex"
                            >{{ t('issues.preview') }}</span
                        >
                    </a>
                    <div class="issues-attachment-meta">
                        <strong>{{ attachment.original_name }}</strong>
                        <span>{{ attachment.created_at ?? '-' }}</span>
                    </div>
                    <a
                        :href="issueAttachmentUrl(attachment.id, true)"
                        class="btn btn-ghost btn-xs"
                    >
                        {{ t('common.download') }}
                    </a>
                </article>
            </div>
            <div v-else class="issues-comments">
                <div class="passkeys-empty">
                    {{ t('issues.no_attachments') }}
                </div>
            </div>
        </div>

        <div class="issues-comments">
            <article
                v-for="comment in state.selectedIssue.comments"
                :key="comment.id"
                class="issues-comment-card"
            >
                <header>
                    <strong>{{
                        displayUser(comment.user, comment.user_id)
                    }}</strong>
                    <span>{{ comment.created_at ?? '-' }}</span>
                </header>
                <div class="issues-comment-text">
                    {{ comment.comment_text }}
                </div>
            </article>
            <div
                v-if="state.selectedIssue.comments.length === 0"
                class="passkeys-empty"
            >
                {{ t('issues.no_comments') }}
            </div>
        </div>

        <div class="issues-comment-form">
            <label for="issueComment">{{ t('common.comment') }}</label>
            <textarea
                id="issueComment"
                v-model="state.commentForm.comment"
                maxlength="4000"
                rows="3"
                class="form-control"
                :placeholder="t('issues.comment_placeholder')"
            />
            <div class="issues-comment-actions">
                <button
                    type="button"
                    class="btn btn-solid"
                    :disabled="state.updating"
                    @click="submitComment"
                >
                    {{ t('issues.post_comment') }}
                </button>
            </div>
        </div>
    </section>
</template>
