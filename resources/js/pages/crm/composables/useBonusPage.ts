import { useToast } from '@/composables/useToast';
import { useUiI18n } from '@/composables/useUiI18n';
import {
    createBonus,
    deleteBonus,
    listBonus,
    updateBonus,
} from '@/modules/bonus/bonusService';
import type {
    BonusPayload,
    BonusPricePayload,
    BonusRecord,
    PaginationMeta,
} from '@/modules/bonus/types';
import {
    LOCALE_LABELS,
    SUPPORTED_LOCALES,
    emptyTranslations,
    normalizeTranslations,
    type SupportedLocale,
} from '@/modules/localization';
import { readFirstDeepLinkParam } from '@/modules/notifications/deepLinkQuery';
import { normalizeApiErrors } from '@/services/apiErrors';
import type { ApiError } from '@/services/http';
import { paginationFromMeta } from '@/services/pagination';
import { computed, onMounted, reactive } from 'vue';

interface PriceRowInput {
    days: string;
    price_eur: string;
}

export function useBonusPage() {
    const { info } = useToast();
    const { t } = useUiI18n();
    const translationFields = SUPPORTED_LOCALES.map((locale) => ({
        locale,
        label: LOCALE_LABELS[locale],
    }));

    const state = reactive({
        rows: [] as BonusRecord[],
        loading: false,
        submitting: false,
        deletingId: null as number | null,
        errors: [] as ApiError[],
        query: '',
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
            title: '',
            translations: emptyTranslations(),
            location_from: '',
            location_to: '',
            date_from: '',
            date_to: '',
            description: '',
            price_eur: '',
            prices: [{ days: '1', price_eur: '' }] as PriceRowInput[],
        },
    });

    const summaryLabel = computed(() => {
        if (state.pagination.total === 0) {
            return t('bonus.summary_empty');
        }

        return t('bonus.summary_total', { count: state.pagination.total });
    });

    const pricePackCount = computed(() =>
        state.rows.reduce((total, bonus) => total + bonus.prices.length, 0),
    );

    const formModalTitle = computed(() =>
        state.formMode === 'create'
            ? t('bonus.modal_add')
            : t('bonus.modal_edit'),
    );

    function resetForm(): void {
        state.form.title = '';
        state.form.translations = emptyTranslations();
        state.form.location_from = '';
        state.form.location_to = '';
        state.form.date_from = '';
        state.form.date_to = '';
        state.form.description = '';
        state.form.price_eur = '';
        state.form.prices = [{ days: '1', price_eur: '' }];
    }

    function openCreateModal(): void {
        state.formMode = 'create';
        state.editingId = null;
        state.errors = [];
        resetForm();
        state.formModalOpen = true;
    }

    function openEditModal(bonus: BonusRecord): void {
        state.formMode = 'edit';
        state.editingId = bonus.id;
        state.errors = [];

        state.form.title = bonus.title ?? '';
        state.form.translations = normalizeTranslations(bonus.translations);
        state.form.location_from = bonus.location_from;
        state.form.location_to = bonus.location_to;
        state.form.date_from = bonus.date_from ?? '';
        state.form.date_to = bonus.date_to ?? '';
        state.form.description = bonus.description ?? '';
        state.form.price_eur =
            bonus.price_eur !== null ? bonus.price_eur.toFixed(2) : '';
        state.form.prices =
            bonus.prices.length > 0
                ? bonus.prices.map((price) => ({
                      days: String(price.days),
                      price_eur: price.price_eur.toFixed(2),
                  }))
                : [{ days: '1', price_eur: '' }];

        state.formModalOpen = true;
    }

    function closeFormModal(): void {
        state.formModalOpen = false;
    }

    function normalizePriceRows(): BonusPricePayload[] {
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

    async function loadBonusRows(): Promise<void> {
        state.loading = true;
        state.errors = [];

        try {
            const response = await listBonus({
                q: state.query || undefined,
                page: state.page,
                per_page: state.perPage,
            });

            state.rows = response.data;
            state.pagination = paginationFromMeta(response.meta.pagination, {
                perPage: state.perPage,
            });
        } catch (error) {
            state.errors = normalizeApiErrors(error, t('bonus.error_load'));
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

    async function submitBonus(): Promise<void> {
        state.submitting = true;
        state.errors = [];

        try {
            const payload: BonusPayload = {
                title: state.form.title || undefined,
                translations: Object.fromEntries(
                    Object.entries(state.form.translations).filter(
                        ([, value]) => value.trim() !== '',
                    ),
                ) as Partial<Record<SupportedLocale, string>>,
                location_from: state.form.location_from,
                location_to: state.form.location_to,
                date_from: state.form.date_from || undefined,
                date_to: state.form.date_to || undefined,
                description: state.form.description || undefined,
                price_eur: state.form.price_eur
                    ? Number.parseFloat(state.form.price_eur)
                    : undefined,
                prices: normalizePriceRows(),
            };

            if (state.formMode === 'edit' && state.editingId !== null) {
                await updateBonus(state.editingId, payload);
            } else {
                await createBonus(payload);
            }

            state.formModalOpen = false;
            state.editingId = null;
            resetForm();

            await loadBonusRows();
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                state.formMode === 'edit'
                    ? t('bonus.error_update')
                    : t('bonus.error_create'),
            );
        } finally {
            state.submitting = false;
        }
    }

    async function removeBonus(bonusId: number): Promise<void> {
        if (!window.confirm(t('bonus.confirm_delete'))) {
            return;
        }

        state.deletingId = bonusId;
        state.errors = [];

        try {
            await deleteBonus(bonusId);
            await loadBonusRows();
        } catch (error) {
            state.errors = normalizeApiErrors(error, t('bonus.error_delete'));
        } finally {
            state.deletingId = null;
        }
    }

    function goToPage(page: number): void {
        state.page = page;
        void loadBonusRows();
    }

    function deepLinkedBonusQuery(): string | null {
        return readFirstDeepLinkParam(['bonus', 'q']);
    }

    onMounted(async () => {
        const deepLink = deepLinkedBonusQuery();
        if (deepLink !== null) {
            state.query = deepLink;
        }

        await loadBonusRows();

        if (deepLink !== null) {
            info(
                t('bonus.deep_link_info_title'),
                t('bonus.deep_link_info_body', { id: deepLink }),
            );
        }
    });

    return {
        t,
        translationFields,
        state,
        summaryLabel,
        pricePackCount,
        formModalTitle,
        openCreateModal,
        openEditModal,
        closeFormModal,
        loadBonusRows,
        addPriceRow,
        removePriceRow,
        submitBonus,
        removeBonus,
        goToPage,
    };
}
