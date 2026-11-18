import { useUiI18n } from '@/composables/useUiI18n';
import {
    clearLogs,
    deleteLog,
    deleteSelectedLogs,
    listLogs,
} from '@/modules/logs/logService';
import type { LogRow, PaginationMeta } from '@/modules/logs/types';
import { normalizeApiErrors } from '@/services/apiErrors';
import type { ApiError } from '@/services/http';
import { paginationFromMeta } from '@/services/pagination';
import { formatPersonName } from '@/utils/personName';
import { computed, onMounted, reactive } from 'vue';

export function useLogsPage() {
    const { t } = useUiI18n();

    const state = reactive({
        loading: false,
        deleting: false,
        rows: [] as LogRow[],
        errors: [] as ApiError[],
        selectedIds: [] as number[],
        page: 1,
        perPage: 25,
        pagination: {
            total: 0,
            per_page: 25,
            current_page: 1,
            last_page: 1,
        } as PaginationMeta,
    });

    const hasSelection = computed(() => state.selectedIds.length > 0);

    const rangeLabel = computed(() => {
        if (state.pagination.total === 0 || state.rows.length === 0) {
            return t('logs.range_empty');
        }

        const start =
            (state.pagination.current_page - 1) * state.pagination.per_page + 1;
        const end = start + state.rows.length - 1;

        return t('logs.range_total', {
            start,
            end,
            total: state.pagination.total,
        });
    });

    function fullUserName(row: LogRow): string {
        return formatPersonName(row.user, `#${row.user_id}`);
    }

    function isSelected(id: number): boolean {
        return state.selectedIds.includes(id);
    }

    function toggleSelection(id: number): void {
        if (isSelected(id)) {
            state.selectedIds = state.selectedIds.filter(
                (value) => value !== id,
            );

            return;
        }

        state.selectedIds = [...state.selectedIds, id];
    }

    function toggleSelectAll(event: Event): void {
        const target = event.target as HTMLInputElement;

        if (!target.checked) {
            state.selectedIds = [];

            return;
        }

        state.selectedIds = state.rows.map((row) => row.id);
    }

    function resetSelection(): void {
        state.selectedIds = [];
    }

    async function loadLogs(): Promise<void> {
        state.loading = true;
        state.errors = [];

        try {
            const response = await listLogs({
                page: state.page,
                per_page: state.perPage,
            });

            state.rows = response.data;
            state.pagination = paginationFromMeta(response.meta.pagination, {
                total: state.rows.length,
                perPage: state.perPage,
                currentPage: state.page,
            });
            resetSelection();
        } catch (error) {
            state.errors = normalizeApiErrors(error, t('logs.error_load'));
        } finally {
            state.loading = false;
        }
    }

    async function removeLog(logId: number): Promise<void> {
        if (!window.confirm(t('logs.confirm_delete'))) {
            return;
        }

        state.deleting = true;
        state.errors = [];

        try {
            await deleteLog(logId);
            await loadLogs();
        } catch (error) {
            state.errors = normalizeApiErrors(error, t('logs.error_delete'));
        } finally {
            state.deleting = false;
        }
    }

    async function removeSelected(): Promise<void> {
        if (
            !hasSelection.value ||
            !window.confirm(t('logs.confirm_delete_selected'))
        ) {
            return;
        }

        state.deleting = true;
        state.errors = [];

        try {
            await deleteSelectedLogs(state.selectedIds);
            await loadLogs();
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                t('logs.error_delete_selected'),
            );
        } finally {
            state.deleting = false;
        }
    }

    async function removeAllVisible(): Promise<void> {
        if (!window.confirm(t('logs.confirm_delete_all'))) {
            return;
        }

        state.deleting = true;
        state.errors = [];

        try {
            await clearLogs();
            await loadLogs();
        } catch (error) {
            state.errors = normalizeApiErrors(error, t('logs.error_clear'));
        } finally {
            state.deleting = false;
        }
    }

    function goToPage(page: number): void {
        state.page = page;
        void loadLogs();
    }

    function applyFilters(): void {
        state.page = 1;
        void loadLogs();
    }

    onMounted(() => {
        void loadLogs();
    });

    return {
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
    };
}
