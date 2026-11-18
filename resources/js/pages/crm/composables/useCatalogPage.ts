import { useToast } from '@/composables/useToast';
import { useUiI18n } from '@/composables/useUiI18n';
import type {
    CatalogListParams,
    CatalogPrice,
    CatalogPricePayload,
    CatalogServiceSummary,
    PaginationMeta,
} from '@/modules/catalog/types';
import {
    LOCALE_LABELS,
    SUPPORTED_LOCALES,
    emptyTranslations,
    normalizeTranslations,
    type LocalizedText,
    type SupportedLocale,
} from '@/modules/localization';
import { readFirstDeepLinkParam } from '@/modules/notifications/deepLinkQuery';
import { listServices } from '@/modules/services/serviceService';
import type { ServiceDefinition } from '@/modules/services/types';
import { normalizeApiErrors } from '@/services/apiErrors';
import type { ApiEnvelope, ApiError } from '@/services/http';
import { paginationFromMeta } from '@/services/pagination';
import { computed, onMounted, reactive } from 'vue';

interface PriceRowInput {
    days: string;
    price_eur: string;
}

export interface ServiceCatalogRecordBase {
    id: number;
    title: string;
    translations: LocalizedText;
    service_id: number;
    service: CatalogServiceSummary | null;
    prices: CatalogPrice[];
    prices_count: number | null;
}

interface ServiceCatalogPayloadBase {
    title?: string;
    translations?: LocalizedText;
    service: number;
    prices: CatalogPricePayload[];
}

interface UseCatalogPageConfig<
    TRecord extends ServiceCatalogRecordBase,
    TPayload extends ServiceCatalogPayloadBase,
> {
    messages: {
        confirmDelete: string;
        deepLinkBody: string;
        deepLinkTitle: string;
        errorCreate: string;
        errorDelete: string;
        errorLoad: string;
        errorLoadServices: string;
        errorUpdate: string;
        modalAdd: string;
        modalEdit: string;
        summaryEmpty: string;
        summaryTotal: string;
    };
    deepLinkKeys: string[];
    listRows: (params?: CatalogListParams) => Promise<ApiEnvelope<TRecord[]>>;
    createRow: (payload: TPayload) => Promise<ApiEnvelope<TRecord>>;
    updateRow: (id: number, payload: TPayload) => Promise<ApiEnvelope<TRecord>>;
    deleteRow: (id: number) => Promise<ApiEnvelope<{ deleted: boolean }>>;
}

export function useCatalogPage<
    TRecord extends ServiceCatalogRecordBase,
    TPayload extends ServiceCatalogPayloadBase,
>(config: UseCatalogPageConfig<TRecord, TPayload>) {
    const { info } = useToast();
    const { t } = useUiI18n();
    const translationFields = SUPPORTED_LOCALES.map((locale) => ({
        locale,
        label: LOCALE_LABELS[locale],
    }));

    const state = reactive({
        rows: [] as TRecord[],
        services: [] as ServiceDefinition[],
        loading: false,
        loadingServices: false,
        submitting: false,
        deletingId: null as number | null,
        errors: [] as ApiError[],
        query: '',
        serviceFilter: 0,
        page: 1,
        perPage: 25,
        pagination: {
            total: 0,
            per_page: 25,
            current_page: 1,
            last_page: 1,
        } as PaginationMeta,
        formModalOpen: false,
        formMode: 'create' as 'create' | 'edit',
        editingId: null as number | null,
        form: {
            translations: emptyTranslations(),
            service: 0,
            prices: [{ days: '1', price_eur: '' }] as PriceRowInput[],
        },
    });

    const summaryLabel = computed(() => {
        if (state.pagination.total === 0) {
            return t(config.messages.summaryEmpty);
        }

        return t(config.messages.summaryTotal, {
            count: state.pagination.total,
        });
    });

    const linkedServicesCount = computed(() => {
        return new Set(
            state.rows
                .map((row) => row.service?.id ?? null)
                .filter((serviceId): serviceId is number => serviceId !== null),
        ).size;
    });

    const pricePackCount = computed(() =>
        state.rows.reduce((total, row) => total + row.prices.length, 0),
    );

    const formModalTitle = computed(() =>
        state.formMode === 'create'
            ? t(config.messages.modalAdd)
            : t(config.messages.modalEdit),
    );

    function resetForm(): void {
        state.form.translations = emptyTranslations();
        state.form.service =
            state.services.length > 0 ? state.services[0].id : 0;
        state.form.prices = [{ days: '1', price_eur: '' }];
    }

    function openCreateModal(): void {
        state.formMode = 'create';
        state.editingId = null;
        state.errors = [];
        resetForm();
        state.formModalOpen = true;
    }

    function openEditModal(row: TRecord): void {
        state.formMode = 'edit';
        state.editingId = row.id;
        state.errors = [];

        state.form.translations = normalizeTranslations(row.translations);
        state.form.service = row.service_id;
        state.form.prices =
            row.prices.length > 0
                ? row.prices.map((price) => ({
                      days: String(price.days),
                      price_eur: price.price_eur.toFixed(2),
                  }))
                : [{ days: '1', price_eur: '' }];

        state.formModalOpen = true;
    }

    function closeFormModal(): void {
        state.formModalOpen = false;
    }

    function normalizePriceRows(): CatalogPricePayload[] {
        return state.form.prices
            .map((row) => ({
                days: Number.parseInt(row.days, 10),
                price_eur: Number.parseFloat(row.price_eur),
            }))
            .filter((row) => Number.isFinite(row.days) && row.days > 0)
            .filter(
                (row) => Number.isFinite(row.price_eur) && row.price_eur >= 0,
            );
    }

    async function loadServicesForForm(): Promise<void> {
        state.loadingServices = true;

        try {
            const response = await listServices({
                page: 1,
                per_page: 100,
                active: true,
            });
            state.services = response.data;

            if (state.form.service <= 0 && state.services.length > 0) {
                state.form.service = state.services[0].id;
            }
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                t(config.messages.errorLoadServices),
            );
        } finally {
            state.loadingServices = false;
        }
    }

    async function loadRows(): Promise<void> {
        state.loading = true;
        state.errors = [];

        try {
            const response = await config.listRows({
                q: state.query || undefined,
                page: state.page,
                per_page: state.perPage,
                service_id:
                    state.serviceFilter > 0 ? state.serviceFilter : undefined,
            });

            // Vue unwraps generic reactive arrays too aggressively here.
            state.rows = response.data as unknown as typeof state.rows;
            state.pagination = paginationFromMeta(response.meta.pagination, {
                perPage: state.perPage,
            });
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                t(config.messages.errorLoad),
            );
        } finally {
            state.loading = false;
        }
    }

    function addPriceRow(): void {
        state.form.prices.push({ days: '', price_eur: '' });
    }

    function removePriceRow(index: number): void {
        if (state.form.prices.length <= 1) {
            state.form.prices[0] = { days: '1', price_eur: '' };
            return;
        }

        state.form.prices.splice(index, 1);
    }

    function buildPayload(): TPayload {
        return {
            translations: Object.fromEntries(
                Object.entries(state.form.translations).filter(
                    ([, value]) => value.trim() !== '',
                ),
            ) as Partial<Record<SupportedLocale, string>>,
            service: state.form.service,
            prices: normalizePriceRows(),
        } as TPayload;
    }

    async function submitRow(): Promise<void> {
        state.submitting = true;
        state.errors = [];

        try {
            const payload = buildPayload();

            if (state.formMode === 'edit' && state.editingId !== null) {
                await config.updateRow(state.editingId, payload);
            } else {
                await config.createRow(payload);
            }

            state.formModalOpen = false;
            state.editingId = null;
            resetForm();

            await loadRows();
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                state.formMode === 'edit'
                    ? t(config.messages.errorUpdate)
                    : t(config.messages.errorCreate),
            );
        } finally {
            state.submitting = false;
        }
    }

    async function removeRow(rowId: number): Promise<void> {
        if (!window.confirm(t(config.messages.confirmDelete))) {
            return;
        }

        state.deletingId = rowId;
        state.errors = [];

        try {
            await config.deleteRow(rowId);
            await loadRows();
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                t(config.messages.errorDelete),
            );
        } finally {
            state.deletingId = null;
        }
    }

    function goToPage(page: number): void {
        state.page = page;
        void loadRows();
    }

    onMounted(async () => {
        const deepLink = readFirstDeepLinkParam(config.deepLinkKeys);
        if (deepLink !== null) {
            state.query = deepLink;
        }

        await loadServicesForForm();
        await loadRows();

        if (deepLink !== null) {
            info(
                t(config.messages.deepLinkTitle),
                t(config.messages.deepLinkBody, { id: deepLink }),
            );
        }
    });

    return {
        addPriceRow,
        closeFormModal,
        formModalTitle,
        goToPage,
        linkedServicesCount,
        loadRows,
        openCreateModal,
        openEditModal,
        pricePackCount,
        removePriceRow,
        removeRow,
        state,
        submitRow,
        summaryLabel,
        t,
        translationFields,
    };
}
