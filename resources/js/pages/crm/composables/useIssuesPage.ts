import { useToast } from '@/composables/useToast';
import { useUiI18n } from '@/composables/useUiI18n';
import {
    addIssueComment,
    createIssue,
    getIssue,
    issueAttachmentUrl,
    listIssues,
    updateIssueAssignee,
    updateIssueStatus,
} from '@/modules/issues/issueService';
import type {
    IssueOverview,
    IssuePriority,
    IssueRecord,
    IssueScopeFilter,
    IssueStatus,
    IssueStatusFilter,
    IssueUserSummary,
    PaginationMeta,
} from '@/modules/issues/types';
import { readPositiveIntDeepLinkParam } from '@/modules/notifications/deepLinkQuery';
import { normalizeApiErrors } from '@/services/apiErrors';
import type { ApiError } from '@/services/http';
import { paginationFromMeta } from '@/services/pagination';
import { formatPersonName } from '@/utils/personName';
import { computed, onMounted, reactive } from 'vue';

export function useIssuesPage() {
    const { info } = useToast();
    const { t } = useUiI18n();

    const defaultSummary = {
        total_count: 0,
        open_count: 0,
        in_progress_count: 0,
        resolved_count: 0,
        urgent_count: 0,
    };

    const state = reactive({
        loading: false,
        submitting: false,
        updating: false,
        errors: [] as ApiError[],
        rows: [] as IssueRecord[],
        summary: { ...defaultSummary },
        pagination: {
            total: 0,
            per_page: 25,
            current_page: 1,
            last_page: 1,
        } as PaginationMeta,
        page: 1,
        perPage: 25,
        filters: {
            scope: 'all' as IssueScopeFilter,
            status: 'all' as IssueStatusFilter,
            module: 'all',
        },
        moduleIssueMap: {} as Record<string, string[]>,
        assignableAdmins: [] as IssueUserSummary[],
        allowedStatuses: ['open', 'in_progress', 'resolved'] as IssueStatus[],
        allowedPriorities: ['normal', 'medium', 'urgent'] as IssuePriority[],
        createFormOpen: true,
        attachmentsOpen: true,
        createForm: {
            module_key: '',
            issue_key: '',
            priority: 'normal' as IssuePriority,
            title: '',
            description: '',
            page_url: '',
            assigned_to: null as number | null,
            screenshots: [] as File[],
        },
        fileInputKey: 0,
        selectedIssueId: null as number | null,
        selectedIssue: null as IssueRecord | null,
        statusForm: {
            status: 'open' as IssueStatus,
            comment: '',
        },
        assigneeForm: {
            assigned_to: null as number | null,
        },
        commentForm: {
            comment: '',
        },
    });

    const moduleOptions = computed<string[]>(() =>
        Object.keys(state.moduleIssueMap),
    );

    const availableIssueKeys = computed<string[]>(() => {
        return state.moduleIssueMap[state.createForm.module_key] ?? [];
    });

    const summaryLabel = computed(() => {
        if (state.pagination.total === 0) {
            return t('issues.summary_empty');
        }

        return t('issues.summary_total', { count: state.pagination.total });
    });

    const scopeLabel = computed(() => {
        if (state.filters.scope === 'assigned') {
            return t('issues.scope_assigned');
        }

        if (state.filters.scope === 'created') {
            return t('issues.scope_created');
        }

        return t('issues.scope_all');
    });

    function displayUser(
        user: IssueUserSummary | null,
        fallbackId: number | null = null,
    ): string {
        return formatPersonName(
            user,
            fallbackId !== null ? `#${fallbackId}` : '-',
        );
    }

    function formatLabel(value: string): string {
        if (value.trim() === '') {
            return '-';
        }

        return value
            .replace(/[_-]+/g, ' ')
            .replace(/\b\w/g, (char) => char.toUpperCase());
    }

    function formatPriorityLabel(value: IssuePriority): string {
        return formatLabel(value);
    }

    function formatStatusLabel(value: IssueStatus | IssueStatusFilter): string {
        return formatLabel(value);
    }

    function priorityBadgeClass(value: IssuePriority): string {
        if (value === 'urgent') {
            return 'badge--warn';
        }

        if (value === 'medium') {
            return 'badge--accent';
        }

        return 'badge--muted';
    }

    function statusBadgeClass(value: IssueStatus): string {
        if (value === 'resolved') {
            return 'badge--success';
        }

        if (value === 'in_progress') {
            return 'badge--accent';
        }

        return 'badge--warn';
    }

    function clearErrors(): void {
        state.errors = [];
    }

    function syncCreateIssueKey(): void {
        const options = availableIssueKeys.value;

        if (options.length === 0) {
            state.createForm.issue_key = '';

            return;
        }

        if (!options.includes(state.createForm.issue_key)) {
            state.createForm.issue_key = options[0];
        }
    }

    function resetCreateForm(): void {
        const initialModule = moduleOptions.value[0] ?? '';

        state.createForm.module_key = initialModule;
        state.createForm.issue_key = '';
        state.createForm.priority = 'normal';
        state.createForm.title = '';
        state.createForm.description = '';
        state.createForm.page_url = '';
        state.createForm.assigned_to = null;
        state.createForm.screenshots = [];
        state.fileInputKey += 1;

        syncCreateIssueKey();
    }

    function toggleCreateForm(): void {
        state.createFormOpen = !state.createFormOpen;
    }

    function toggleAttachments(): void {
        state.attachmentsOpen = !state.attachmentsOpen;
    }

    function applyOverview(
        data: IssueOverview,
        pagination: PaginationMeta,
    ): void {
        state.rows = data.rows ?? [];
        state.summary = data.summary ?? { ...defaultSummary };
        state.moduleIssueMap = data.module_issue_map ?? {};
        state.assignableAdmins = data.assignable_admins ?? [];
        state.allowedStatuses =
            (data.allowed_statuses?.length ?? 0) > 0
                ? data.allowed_statuses
                : ['open', 'in_progress', 'resolved'];
        state.allowedPriorities =
            (data.allowed_priorities?.length ?? 0) > 0
                ? data.allowed_priorities
                : ['normal', 'medium', 'urgent'];
        state.pagination = pagination;

        if (!moduleOptions.value.includes(state.createForm.module_key)) {
            state.createForm.module_key = moduleOptions.value[0] ?? '';
        }

        syncCreateIssueKey();
    }

    async function loadIssues(): Promise<void> {
        state.loading = true;
        clearErrors();

        try {
            const response = await listIssues({
                scope: state.filters.scope,
                status: state.filters.status,
                module: state.filters.module,
                page: state.page,
                per_page: state.perPage,
            });

            const pagination = paginationFromMeta(response.meta.pagination, {
                total: (response.data.rows ?? []).length,
                perPage: state.perPage,
                currentPage: state.page,
            });

            applyOverview(response.data, pagination);
        } catch (error) {
            state.errors = normalizeApiErrors(error, 'Failed to load issues.');
        } finally {
            state.loading = false;
        }
    }

    async function openIssue(issueId: number): Promise<void> {
        state.updating = true;
        clearErrors();

        try {
            const response = await getIssue(issueId);
            state.selectedIssueId = issueId;
            state.selectedIssue = response.data;
            state.statusForm.status = response.data.status;
            state.statusForm.comment = '';
            state.assigneeForm.assigned_to = response.data.assigned_to;
            state.commentForm.comment = '';
            state.attachmentsOpen = true;
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                'Failed to load issue details.',
            );
        } finally {
            state.updating = false;
        }
    }

    function onModuleChange(): void {
        syncCreateIssueKey();
    }

    function onScreenshotsChange(event: Event): void {
        const target = event.target as HTMLInputElement;
        const files = target.files;

        if (!files) {
            state.createForm.screenshots = [];

            return;
        }

        state.createForm.screenshots = Array.from(files).slice(0, 3);
    }

    async function submitCreateIssue(): Promise<void> {
        if (
            state.createForm.module_key === '' ||
            state.createForm.issue_key === ''
        ) {
            state.errors = [
                {
                    code: 'validation_error',
                    message: 'Select module and issue type.',
                },
            ];

            return;
        }

        state.submitting = true;
        clearErrors();

        try {
            const response = await createIssue({
                module_key: state.createForm.module_key,
                issue_key: state.createForm.issue_key,
                priority: state.createForm.priority,
                title: state.createForm.title,
                description: state.createForm.description,
                page_url: state.createForm.page_url,
                assigned_to: state.createForm.assigned_to,
                screenshots: state.createForm.screenshots,
            });

            const createdId = response.data.id;

            await loadIssues();
            await openIssue(createdId);
            resetCreateForm();
        } catch (error) {
            state.errors = normalizeApiErrors(error, 'Failed to create issue.');
        } finally {
            state.submitting = false;
        }
    }

    async function submitStatusUpdate(): Promise<void> {
        if (state.selectedIssueId === null) {
            return;
        }

        state.updating = true;
        clearErrors();

        try {
            await updateIssueStatus(state.selectedIssueId, {
                status: state.statusForm.status,
                comment: state.statusForm.comment,
            });

            await loadIssues();
            await openIssue(state.selectedIssueId);
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                'Failed to update issue status.',
            );
        } finally {
            state.updating = false;
        }
    }

    async function submitAssigneeUpdate(): Promise<void> {
        if (state.selectedIssueId === null) {
            return;
        }

        state.updating = true;
        clearErrors();

        try {
            await updateIssueAssignee(state.selectedIssueId, {
                assigned_to: state.assigneeForm.assigned_to,
            });

            await loadIssues();
            await openIssue(state.selectedIssueId);
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                'Failed to update assignee.',
            );
        } finally {
            state.updating = false;
        }
    }

    async function submitComment(): Promise<void> {
        if (state.selectedIssueId === null) {
            return;
        }

        state.updating = true;
        clearErrors();

        try {
            await addIssueComment(state.selectedIssueId, {
                comment: state.commentForm.comment,
            });

            await loadIssues();
            await openIssue(state.selectedIssueId);
        } catch (error) {
            state.errors = normalizeApiErrors(error, 'Failed to post comment.');
        } finally {
            state.updating = false;
        }
    }

    function goToPage(page: number): void {
        state.page = page;
        void loadIssues();
    }

    function applyFilters(): void {
        state.page = 1;
        void loadIssues();
    }

    function setScope(scope: IssueScopeFilter): void {
        if (state.filters.scope === scope) {
            return;
        }

        state.filters.scope = scope;
        applyFilters();
    }

    function deepLinkedIssueId(): number | null {
        return readPositiveIntDeepLinkParam(['issue']);
    }

    onMounted(async () => {
        await loadIssues();
        resetCreateForm();

        const issueId = deepLinkedIssueId();
        if (issueId !== null) {
            await openIssue(issueId);
            info(
                t('issues.deep_link_title'),
                t('issues.deep_link_body', { id: issueId }),
            );
        }
    });

    return {
        t,
        state,
        moduleOptions,
        availableIssueKeys,
        summaryLabel,
        scopeLabel,
        displayUser,
        formatLabel,
        formatPriorityLabel,
        formatStatusLabel,
        priorityBadgeClass,
        statusBadgeClass,
        toggleCreateForm,
        toggleAttachments,
        loadIssues,
        openIssue,
        onModuleChange,
        onScreenshotsChange,
        submitCreateIssue,
        submitStatusUpdate,
        submitAssigneeUpdate,
        submitComment,
        goToPage,
        applyFilters,
        setScope,
        issueAttachmentUrl,
    };
}
