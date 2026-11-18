import { useUiI18n } from '@/composables/useUiI18n';
import { formatEurBgnPairFromBgn } from '@/modules/money/currency';
import { renderPrintReport, reportCsv } from '@/modules/reports/reportOutput';
import { listReports } from '@/modules/reports/reportService';
import type {
    PaginationMeta,
    ReportOverview,
    ReportRow,
    ReportSortDirection,
    ReportSortKey,
} from '@/modules/reports/types';
import { listServices } from '@/modules/services/serviceService';
import type { ServiceDefinition } from '@/modules/services/types';
import { normalizeApiErrors } from '@/services/apiErrors';
import type { ApiError } from '@/services/http';
import { paginationFromMeta } from '@/services/pagination';
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
    const { t } = useUiI18n();

    const state = reactive({
        loading: false,
        loadingServices: false,
        exportingAllCsv: false,
        exportingPageCsv: false,
        printingAll: false,
        compact: false,
        errors: [] as ApiError[],
        services: [] as ServiceDefinition[],
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

    const isDriverView = computed(() => state.report?.is_driver === true);

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
        const from = state.filters.from;
        const to = state.filters.to;

        if (from === '' || to === '') {
            return t('reports.select_period');
        }

        return `${formatDateLabel(from)} - ${formatDateLabel(to)}`;
    });

    const selectedServiceLabel = computed(() => {
        const selectedId = Number.parseInt(state.filters.service_id, 10);
        if (Number.isNaN(selectedId)) {
            return t('reports.all_services');
        }

        const service = state.services.find((item) => item.id === selectedId);

        return service?.title ?? t('reports.all_services');
    });

    const displayRows = computed<DisplayRow[]>(() => {
        return state.rows
            .map((row): DisplayRow => {
                const dueRaw = Number(row.due ?? 0);
                const paymentLabel = `${formatCurrencyPair(Number(row.paid ?? 0))} / ${formatCurrencyPair(Number(row.sum_paid ?? 0))}`;

                return {
                    bookingId: row.booking_id,
                    name: row.customer_name || '—',
                    birth: row.birth ?? '—',
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
            })
            .filter((row) => {
                const matches = (key: ColumnKey, value: string): boolean => {
                    const needle = state.tableFilters[key].trim().toLowerCase();
                    if (needle === '') {
                        return true;
                    }

                    return value.toLowerCase().includes(needle);
                };

                return (
                    matches('name', row.name) &&
                    matches('birth', row.birth) &&
                    matches('level', row.level) &&
                    matches('equip', row.equip) &&
                    matches(
                        'location',
                        `${row.location} ${row.dates.join(' ')}`,
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

    function toIsoDate(date: Date): string {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
    }

    function setTodayRange(): void {
        const today = toIsoDate(new Date());

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

        state.filters.from = toIsoDate(start);
        state.filters.to = toIsoDate(end);
        state.activePreset = 'week';
    }

    function setMonthRange(): void {
        const today = new Date();
        const start = new Date(today.getFullYear(), today.getMonth(), 1);
        const end = new Date(today.getFullYear(), today.getMonth() + 1, 0);

        state.filters.from = toIsoDate(start);
        state.filters.to = toIsoDate(end);
        state.activePreset = 'month';
    }

    function saveLastFilters(): void {
        localStorage.setItem(
            LAST_FILTERS_KEY,
            JSON.stringify({
                service_id: state.filters.service_id,
                from: state.filters.from,
                to: state.filters.to,
                per_page: state.perPage,
            }),
        );
    }

    function loadLastFilters(): void {
        const raw = localStorage.getItem(LAST_FILTERS_KEY);

        if (!raw) {
            return;
        }

        try {
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

    function formatDateLabel(isoDate: string): string {
        const parts = isoDate.split('-');
        if (parts.length !== 3) {
            return isoDate;
        }

        return `${parts[2]}-${parts[1]}-${parts[0]}`;
    }

    function formatCurrencyPair(bgnValue: number): string {
        return formatEurBgnPairFromBgn(bgnValue);
    }

    function normalizeServiceId(): number | undefined {
        const parsed = Number.parseInt(state.filters.service_id, 10);

        return Number.isNaN(parsed) ? undefined : parsed;
    }

    async function loadServices(): Promise<void> {
        state.loadingServices = true;

        try {
            const response = await listServices({
                active: true,
                per_page: 100,
                page: 1,
            });

            state.services = response.data;
        } catch {
            state.services = [];
        } finally {
            state.loadingServices = false;
        }
    }

    async function loadReport(): Promise<void> {
        state.loading = true;
        state.errors = [];

        try {
            const response = await listReports({
                service_id: normalizeServiceId(),
                from: state.filters.from,
                to: state.filters.to,
                page: state.page,
                per_page: state.perPage,
                sort: state.filters.sort,
                dir: state.filters.dir,
            });

            state.report = response.data;
            state.rows = response.data.rows;

            state.pagination = paginationFromMeta(response.meta.pagination, {
                total: state.rows.length,
                perPage: state.perPage,
                currentPage: state.page,
            });

            saveLastFilters();
        } catch (error) {
            state.errors = normalizeApiErrors(error, 'Failed to load report.');
        } finally {
            state.loading = false;
        }
    }

    function generateReport(): void {
        state.page = 1;
        void loadReport();
    }

    function goToPage(page: number): void {
        state.page = page;
        void loadReport();
    }

    function onPerPageChange(perPageValue: string): void {
        const parsed = Number.parseInt(perPageValue, 10);

        if (!Number.isNaN(parsed) && [10, 25, 50, 100].includes(parsed)) {
            state.perPage = parsed;
            state.page = 1;
            void loadReport();
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
                return `${row.location} ${row.dates.join(' ')}`;
            case 'due':
                return row.due;
            case 'payment':
                return row.payment;
            case 'parent':
                return `${row.parent} ${row.parentPhone} ${row.parentEmail}`;
        }
    }

    async function fetchAllRows(): Promise<DisplayRow[]> {
        const filters = {
            ...state.filters,
            service_id: normalizeServiceId(),
        };
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

        return rows.map((row): DisplayRow => {
            const dueRaw = Number(row.due ?? 0);

            return {
                bookingId: row.booking_id,
                name: row.customer_name || '—',
                birth: row.birth ?? '—',
                level: row.skill?.title ?? '—',
                equip: row.equip?.title ?? '—',
                location: row.location || '—',
                due:
                    dueRaw <= 0.01
                        ? t('reports.paid')
                        : `${t('reports.remaining')}: ${formatCurrencyPair(dueRaw)}`,
                dueRaw,
                payment: `${formatCurrencyPair(Number(row.paid ?? 0))} / ${formatCurrencyPair(Number(row.sum_paid ?? 0))}`,
                parent: row.parent_name ?? '—',
                parentPhone: row.parent_phone ?? '',
                parentEmail: row.email ?? '',
                dates: row.dates,
                raw: row,
            };
        });
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
        await loadServices();
        await loadReport();
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
