import { useUiI18n } from '@/composables/useUiI18n';
import {
    dateInputToIso,
    formatGermanDate as formatDateLabel,
} from '@/modules/calendar/dateDisplay';
import { formatIsoDate } from '@/modules/calendar/dateUtils';
import { formatEurBgnPairFromBgn } from '@/modules/money/currency';
import { renderPrintReport, reportCsv } from '@/modules/reports/reportOutput';
import {
    listReports,
    listReportServices,
} from '@/modules/reports/reportService';
import type {
    PaginationMeta,
    ReportListParams,
    ReportOverview,
    ReportRow,
    ReportServiceOption,
    ReportSortDirection,
    ReportSortKey,
} from '@/modules/reports/types';
import { normalizeApiErrors } from '@/services/apiErrors';
import type { ApiError } from '@/services/http';
import { paginationFromMeta } from '@/services/pagination';
import { useAuthStore } from '@/stores/authStore';
import { computed, onMounted, reactive } from 'vue';

const LAST_FILTERS_KEY = 'crm-reports-last-filters';

type DatePreset = 'today' | 'week' | 'month' | 'last' | null;
type ColumnKey =
    | 'name'
    | 'birth'
    | 'level'
    | 'equip'
    | 'location'
    | 'due'
    | 'payment'
    | 'parent';

type DisplayRow = {
    bookingId: number;
    name: string;
    birth: string;
    level: string;
    equip: string;
    location: string;
    due: string;
    dueRaw: number;
    payment: string;
    parent: string;
    parentPhone: string;
    parentEmail: string;
    dates: string[];
    raw: ReportRow;
};

export function useReportsPage() {
    const { t, localeTag } = useUiI18n();
    const auth = useAuthStore();
    let reportRequest = 0;

    const state = reactive({
        loading: false,
        exportingAllCsv: false,
        exportingPageCsv: false,
        printingAll: false,
        compact: false,
        errors: [] as ApiError[],
        services: [] as ReportServiceOption[],
        serviceOptionsError: false,
        report: null as ReportOverview | null,
        rows: [] as ReportRow[],
        page: 1,
        perPage: 25,
        pagination: {
            total: 0,
            per_page: 25,
            current_page: 1,
            last_page: 1,
        } as PaginationMeta,
        activePreset: null as DatePreset,
        filters: {
            service_id: '' as string,
            from: '',
            to: '',
            sort: 'id' as ReportSortKey,
            dir: 'desc' as ReportSortDirection,
        },
        tableFilters: {
            name: '',
            birth: '',
            level: '',
            equip: '',
            location: '',
            due: '',
            payment: '',
            parent: '',
        } as Record<ColumnKey, string>,
        columns: {
            name: true,
            birth: true,
            level: true,
            equip: true,
            location: true,
            due: true,
            payment: true,
            parent: true,
        } as Record<ColumnKey, boolean>,
    });

    const isDriverView = computed(
        () => state.report?.is_driver ?? auth.user?.role_key === 'driver',
    );

    const effectiveColumns = computed<Record<ColumnKey, boolean>>(() => ({
        name: true,
        birth: !isDriverView.value && state.columns.birth,
        level: !isDriverView.value && state.columns.level,
        equip: !isDriverView.value && state.columns.equip,
        location: true,
        due: !isDriverView.value && state.columns.due,
        payment: !isDriverView.value && state.columns.payment,
        parent: true,
    }));

    const stats = computed(() => {
        const summary = state.report?.summary;

        return {
            records: summary?.total_count ?? 0,
            sum: summary?.sum_total ?? 0,
            paid: summary?.paid_total ?? 0,
            due: summary?.due_total ?? 0,
        };
    });

    const reportPeriodLabel = computed(() => {
        const result = state.report?.filters;

        if (!result?.from || !result?.to) {
            return t('reports.select_period');
        }

        return `${formatDateLabel(result.from)} - ${formatDateLabel(result.to)}`;
    });

    const selectedServiceLabel = computed(() => {
        const selectedId = state.report?.filters.service_id;
        if (!selectedId) {
            return t('reports.all_services');
        }

        const service = state.services.find((item) => item.id === selectedId);

        return service?.title ?? `#${selectedId}`;
    });

    function formatRow(row: ReportRow): DisplayRow {
        const dueRaw = Number(row.due ?? 0);
        const paymentLabel = `${formatCurrencyPair(Number(row.paid ?? 0))} / ${formatCurrencyPair(Number(row.sum_paid ?? 0))}`;

        return {
            bookingId: row.booking_id,
            name: row.customer_name || '—',
            birth: formatDateLabel(row.birth),
            level: row.skill?.title ?? '—',
            equip: row.equip?.title ?? '—',
            location: row.location || '—',
            due:
                dueRaw <= 0.01
                    ? t('reports.paid')
                    : `${t('reports.remaining')}: ${formatCurrencyPair(dueRaw)}`,
            dueRaw,
            payment: paymentLabel,
            parent: row.parent_name ?? '—',
            parentPhone: row.parent_phone ?? '',
            parentEmail: row.email ?? '',
            dates: row.dates,
            raw: row,
        };
    }

    const displayRows = computed<DisplayRow[]>(() => {
        return state.rows.map(formatRow).filter((row) => {
            const matches = (key: ColumnKey, value: string): boolean => {
                const needle = state.tableFilters[key].trim().toLowerCase();
                if (needle === '') {
                    return true;
                }

                return value.toLowerCase().includes(needle);
            };

            return (
                matches('name', row.name) &&
                (matches('birth', row.birth) ||
                    matches('birth', row.raw.birth ?? '')) &&
                matches('level', row.level) &&
                matches('equip', row.equip) &&
                matches(
                    'location',
                    `${row.location} ${row.dates.map((date) => formatDateLabel(date)).join(' ')}`,
                ) &&
                matches('due', row.due) &&
                matches('payment', row.payment) &&
                matches(
                    'parent',
                    `${row.parent} ${row.parentPhone} ${row.parentEmail}`,
                )
            );
        });
    });

    const tfootSummaryLabel = computed(() => {
        return `${t('reports.total_amount')}: ${formatCurrencyPair(stats.value.sum)} · ${t('reports.paid')}: ${formatCurrencyPair(stats.value.paid)} · ${t('reports.remaining')}: ${formatCurrencyPair(stats.value.due)}`;
    });

    const visibleColumnCount = computed(
        () => Object.values(effectiveColumns.value).filter(Boolean).length,
    );
    const canLoadPreviousPage = computed(
        () => state.pagination.current_page > 1,
    );
    const canLoadNextPage = computed(
        () => state.pagination.current_page < state.pagination.last_page,
    );

    function setTodayRange(): void {
        const today = formatIsoDate(new Date());

        state.filters.from = today;
        state.filters.to = today;
        state.activePreset = 'today';
    }

    function setWeekRange(): void {
        const today = new Date();
        const day = today.getDay() || 7;
        const start = new Date(today);

        start.setDate(today.getDate() - day + 1);

        const end = new Date(start);
        end.setDate(start.getDate() + 6);

        state.filters.from = formatIsoDate(start);
        state.filters.to = formatIsoDate(end);
        state.activePreset = 'week';
    }

    function setMonthRange(): void {
        const today = new Date();
        const start = new Date(today.getFullYear(), today.getMonth(), 1);
        const end = new Date(today.getFullYear(), today.getMonth() + 1, 0);

        state.filters.from = formatIsoDate(start);
        state.filters.to = formatIsoDate(end);
        state.activePreset = 'month';
    }

    function saveLastFilters(
        filters: ReportOverview['filters'],
        perPage: number,
    ): void {
        try {
            localStorage.setItem(
                LAST_FILTERS_KEY,
                JSON.stringify({
                    service_id:
                        filters.service_id === null
                            ? ''
                            : String(filters.service_id),
                    from: filters.from,
                    to: filters.to,
                    per_page: perPage,
                }),
            );
        } catch {
            // An unavailable storage provider does not invalidate the generated report.
        }
    }

    function loadLastFilters(): void {
        try {
            const raw = localStorage.getItem(LAST_FILTERS_KEY);
            if (!raw) return;
            const parsed = JSON.parse(raw) as {
                service_id?: string;
                from?: string;
                to?: string;
                per_page?: number;
            };

            if (typeof parsed.service_id === 'string') {
                state.filters.service_id = parsed.service_id;
            }

            if (
                typeof parsed.from === 'string' &&
                typeof parsed.to === 'string'
            ) {
                state.filters.from = parsed.from;
                state.filters.to = parsed.to;
                state.activePreset = 'last';
            }

            if (
                typeof parsed.per_page === 'number' &&
                [10, 25, 50, 100].includes(parsed.per_page)
            ) {
                state.perPage = parsed.per_page;
            }
        } catch {
            // ignore malformed local storage payload
        }
    }

    function applyPreset(preset: DatePreset): void {
        switch (preset) {
            case 'today':
                setTodayRange();
                break;
            case 'week':
                setWeekRange();
                break;
            case 'month':
                setMonthRange();
                break;
            case 'last':
                loadLastFilters();
                break;
        }
    }

    function formatCurrencyPair(bgnValue: number): string {
        return formatEurBgnPairFromBgn(bgnValue, localeTag.value);
    }

    function applyLinkedFilters(): void {
        const query = new URLSearchParams(window.location.search);
        const service = query.get('service_id');
        if (service && /^[1-9]\d*$/.test(service))
            state.filters.service_id = service;

        const from = dateInputToIso(query.get('from') ?? '');
        const to = dateInputToIso(query.get('to') ?? '');
        if (from && to && from <= to) {
            state.filters.from = from;
            state.filters.to = to;
            state.activePreset = null;
        }
    }

    function draftFilters(): ReportListParams {
        const id = Number.parseInt(state.filters.service_id, 10);
        return {
            from: state.filters.from,
            to: state.filters.to,
            service_id: Number.isNaN(id) ? undefined : id,
            sort: state.filters.sort,
            dir: state.filters.dir,
        };
    }

    function appliedFilters(): ReportListParams {
        const result = state.report?.filters;
        if (!result) return draftFilters();

        return {
            ...result,
            from: result.from ?? undefined,
            to: result.to ?? undefined,
            service_id: result.service_id ?? undefined,
        };
    }

    async function loadServices(): Promise<void> {
        state.serviceOptionsError = false;
        try {
            const response = await listReportServices();
            state.services = response.data;
        } catch {
            state.serviceOptionsError = true;
        }
    }

    async function loadReport(filters = draftFilters()): Promise<void> {
        const request = ++reportRequest;
        const { page, perPage } = state;
        state.loading = true;
        state.errors = [];

        try {
            const response = await listReports({
                ...filters,
                page,
                per_page: perPage,
            });
            if (request !== reportRequest) return;

            state.report = response.data;
            state.rows = response.data.rows;
            state.pagination = paginationFromMeta(response.meta.pagination, {
                total: state.rows.length,
                perPage,
                currentPage: page,
            });

            saveLastFilters(response.data.filters, perPage);
        } catch (error) {
            if (request === reportRequest) {
                state.errors = normalizeApiErrors(
                    error,
                    t('reports.load_failed'),
                );
            }
        } finally {
            if (request === reportRequest) state.loading = false;
        }
    }

    function generateReport(): void {
        state.page = 1;
        void loadReport();
    }

    function goToPage(page: number): void {
        state.page = page;
        void loadReport(appliedFilters());
    }

    function onPerPageChange(perPageValue: string): void {
        const parsed = Number.parseInt(perPageValue, 10);
        if ([10, 25, 50, 100].includes(parsed)) {
            state.perPage = parsed;
            state.page = 1;
            void loadReport(appliedFilters());
        }
    }

    function tableCellValue(row: DisplayRow, key: ColumnKey): string {
        switch (key) {
            case 'name':
                return row.name;
            case 'birth':
                return row.birth;
            case 'level':
                return row.level;
            case 'equip':
                return row.equip;
            case 'location':
                return `${row.location} ${row.dates.map((date) => formatDateLabel(date)).join(' ')}`;
            case 'due':
                return row.due;
            case 'payment':
                return row.payment;
            case 'parent':
                return `${row.parent} ${row.parentPhone} ${row.parentEmail}`;
        }
    }

    async function fetchAllRows(): Promise<DisplayRow[]> {
        const filters = appliedFilters();
        const rows: ReportRow[] = [];
        let page = 1;
        let lastPage = 1;

        do {
            const response = await listReports({
                ...filters,
                page,
                per_page: 100,
            });

            rows.push(...response.data.rows);

            const pagination = paginationFromMeta(response.meta.pagination, {
                total: rows.length,
                perPage: 100,
                currentPage: page,
            });
            lastPage = pagination.last_page;
            page += 1;
        } while (page <= lastPage);

        return rows.map(formatRow);
    }

    function columnHeadings(columns: ColumnKey[]): string[] {
        const titleMap: Record<ColumnKey, string> = {
            name: t('reports.name'),
            birth: t('reports.birth_date'),
            level: t('reports.level'),
            equip: t('reports.equipment'),
            location: t('reports.location'),
            due: t('reports.due'),
            payment: t('reports.payment'),
            parent: t('reports.parent'),
        };

        return columns.map((key) => titleMap[key]);
    }

    function outputRows(columns: ColumnKey[], rows: DisplayRow[]): string[][] {
        return rows.map((row) =>
            columns.map((key) => tableCellValue(row, key)),
        );
    }

    function buildCsv(columns: ColumnKey[], rows: DisplayRow[]): string {
        return reportCsv(columnHeadings(columns), outputRows(columns, rows));
    }

    function downloadCsv(content: string, fileName: string): void {
        const blob = new Blob(['\uFEFF', content], {
            type: 'text/csv;charset=utf-8;',
        });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');

        link.href = url;
        link.setAttribute('download', fileName);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        URL.revokeObjectURL(url);
    }

    async function exportPageCsv(): Promise<void> {
        state.exportingPageCsv = true;

        try {
            const columns = (
                Object.keys(effectiveColumns.value) as ColumnKey[]
            ).filter((key) => effectiveColumns.value[key]);
            downloadCsv(
                buildCsv(columns, displayRows.value),
                `report-page-${state.pagination.current_page}.csv`,
            );
        } finally {
            state.exportingPageCsv = false;
        }
    }

    async function exportAllCsv(): Promise<void> {
        state.exportingAllCsv = true;

        try {
            const columns = (
                Object.keys(effectiveColumns.value) as ColumnKey[]
            ).filter((key) => effectiveColumns.value[key]);
            downloadCsv(
                buildCsv(columns, await fetchAllRows()),
                'report-all.csv',
            );
        } catch (error) {
            state.errors = normalizeApiErrors(error, t('reports.load_failed'));
        } finally {
            state.exportingAllCsv = false;
        }
    }

    function printPage(): void {
        window.print();
    }

    async function printAll(): Promise<void> {
        const popup = window.open('', '_blank', 'width=1200,height=800');
        if (popup === null) {
            return;
        }

        popup.opener = null;
        const period = reportPeriodLabel.value;
        const columns = (
            Object.keys(effectiveColumns.value) as ColumnKey[]
        ).filter((key) => effectiveColumns.value[key]);

        state.printingAll = true;
        try {
            const rows = await fetchAllRows();
            renderPrintReport(
                popup.document,
                t('reports.title'),
                period,
                columnHeadings(columns),
                outputRows(columns, rows),
            );
            popup.focus();
            popup.print();
        } catch (error) {
            popup.close();
            state.errors = normalizeApiErrors(error, t('reports.load_failed'));
        } finally {
            state.printingAll = false;
        }
    }

    onMounted(async () => {
        setTodayRange();
        loadLastFilters();
        applyLinkedFilters();
        await Promise.all([loadServices(), loadReport()]);
    });

    return {
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
    };
}
