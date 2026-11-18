export type IssueScopeFilter = 'all' | 'assigned' | 'created';
export type IssueStatusFilter = 'all' | 'open' | 'in_progress' | 'resolved';
export type IssueStatus = 'open' | 'in_progress' | 'resolved';
export type IssuePriority = 'normal' | 'medium' | 'urgent';

export interface IssueUserSummary {
    id: number;
    username: string;
    firstname: string | null;
    surname: string | null;
    lastname: string | null;
}

export interface IssueComment {
    id: number;
    issue_id: number;
    user_id: number;
    comment_text: string;
    created_at: string | null;
    user: IssueUserSummary | null;
}

export interface IssueAttachment {
    id: number;
    issue_id: number;
    original_name: string;
    mime_type: string;
    file_size: number;
    created_at: string | null;
    url: string;
    download_url: string;
}

export interface IssueRecord {
    id: number;
    created_by: number;
    module_key: string;
    issue_key: string;
    priority: IssuePriority;
    status: IssueStatus;
    title: string;
    description: string | null;
    page_url: string | null;
    resolved_by: number | null;
    assigned_to: number | null;
    assigned_at: string | null;
    resolved_at: string | null;
    created_at: string | null;
    updated_at: string | null;
    reporter: IssueUserSummary | null;
    resolver: IssueUserSummary | null;
    assignee: IssueUserSummary | null;
    comments: IssueComment[];
    attachments: IssueAttachment[];
}

export interface IssueSummary {
    total_count: number;
    open_count: number;
    in_progress_count: number;
    resolved_count: number;
    urgent_count: number;
}

export interface IssueFilters {
    scope: IssueScopeFilter;
    status: IssueStatusFilter;
    module: string;
}

export interface IssueOverview {
    rows: IssueRecord[];
    summary: IssueSummary;
    filters: IssueFilters;
    allowed_statuses: IssueStatus[];
    allowed_priorities: IssuePriority[];
    module_issue_map: Record<string, string[]>;
    assignable_admins: IssueUserSummary[];
}

export interface IssueListParams {
    scope?: IssueScopeFilter;
    status?: IssueStatusFilter;
    module?: string;
    page?: number;
    per_page?: number;
}

export interface IssueCreatePayload {
    module_key: string;
    issue_key: string;
    priority: IssuePriority;
    title?: string;
    description: string;
    page_url?: string;
    assigned_to?: number | null;
    screenshots?: File[];
}

export interface IssueStatusPayload {
    status: IssueStatus;
    comment?: string;
}

export interface IssueAssigneePayload {
    assigned_to: number | null;
}

export interface IssueCommentPayload {
    comment: string;
}

export interface PaginationMeta {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}
