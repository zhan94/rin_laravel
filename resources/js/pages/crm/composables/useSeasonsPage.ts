import { useUiI18n } from '@/composables/useUiI18n';
import { formatEurBgnPairFromEur } from '@/modules/money/currency';
import {
    formatSeasonDayMonth,
    parseSeasonDayMonth,
} from '@/modules/seasons/seasonDefaults';
import {
    createServiceSeason,
    deleteServiceSeason,
    getSeasonOverview,
    updateSeasonDefaults,
    updateServiceSeason,
} from '@/modules/seasons/seasonService';
import type {
    SeasonOverview,
    SeasonViewType,
    ServiceSeasonPayload,
} from '@/modules/seasons/types';
import { normalizeApiErrors } from '@/services/apiErrors';
import type { ApiError } from '@/services/http';
import { computed, onMounted, reactive } from 'vue';

export function useSeasonsPage() {
    const { t, localeTag } = useUiI18n();

    const state = reactive({
        loading: false,
        loadingSelectSeason: false,
        submittingCreate: false,
        submittingUpdate: false,
        submittingDefaults: false,
        deletingSeason: false,
        errors: [] as ApiError[],
        overview: null as SeasonOverview | null,
        selectedType: 'service' as SeasonViewType,
        selectedServiceId: 0,
        selectedBonusTitle: '',
        selectedSeasonId: 0,
        createForm: {
            season_name: '',
            season_start: '',
            season_end: '',
        },
        editForm: {
            season_name: '',
            season_start: '',
            season_end: '',
        },
        defaultsForm: {
            default_start_md: '01-09',
            default_end_md: '31-08',
        },
    });

    const selectedSeason = computed(
        () => state.overview?.selected_season ?? null,
    );
    const showDatesColumn = computed(() => state.selectedType === 'service');

    function formatCurrency(amount: number): string {
        return formatEurBgnPairFromEur(amount, localeTag.value);
    }

    function hydrateDefaultsForm(overview: SeasonOverview): void {
        state.defaultsForm.default_start_md = formatSeasonDayMonth(
            overview.season_defaults.start_day,
            overview.season_defaults.start_month,
        );
        state.defaultsForm.default_end_md = formatSeasonDayMonth(
            overview.season_defaults.end_day,
            overview.season_defaults.end_month,
        );
    }

    function hydrateEditForm(): void {
        if (!selectedSeason.value) {
            state.editForm.season_name = '';
            state.editForm.season_start = '';
            state.editForm.season_end = '';

            return;
        }

        state.editForm.season_name = selectedSeason.value.label;
        state.editForm.season_start = selectedSeason.value.date_start ?? '';
        state.editForm.season_end = selectedSeason.value.date_end ?? '';
    }

    async function loadOverview(
        opts: { seasonId?: number; selecting?: boolean } = {},
    ): Promise<void> {
        state.errors = [];

        if (opts.selecting) {
            state.loadingSelectSeason = true;
        } else {
            state.loading = true;
        }

        try {
            const response = await getSeasonOverview({
                type: state.selectedType,
                service:
                    state.selectedType === 'service'
                        ? state.selectedServiceId > 0
                            ? state.selectedServiceId
                            : undefined
                        : state.selectedBonusTitle || undefined,
                season: opts.seasonId,
            });

            state.overview = response.data;
            state.selectedType = response.data.type;
            state.selectedServiceId = response.data.selected_service_id ?? 0;
            state.selectedBonusTitle = response.data.selected_bonus_title ?? '';
            state.selectedSeasonId = response.data.selected_season?.id ?? 0;

            hydrateDefaultsForm(response.data);
            hydrateEditForm();
        } catch (error) {
            state.errors = normalizeApiErrors(error, 'Failed to load seasons.');
        } finally {
            state.loading = false;
            state.loadingSelectSeason = false;
        }
    }

    async function submitCreateSeason(): Promise<void> {
        if (state.selectedType !== 'service' || state.selectedServiceId <= 0) {
            return;
        }

        state.submittingCreate = true;
        state.errors = [];

        try {
            const payload: ServiceSeasonPayload = {
                service_id: state.selectedServiceId,
                season_name: state.createForm.season_name || undefined,
                season_start: state.createForm.season_start,
                season_end: state.createForm.season_end,
            };

            const response = await createServiceSeason(payload);

            state.createForm.season_name = '';
            state.createForm.season_start = '';
            state.createForm.season_end = '';

            await loadOverview({ seasonId: response.data.id });
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                'Failed to create season.',
            );
        } finally {
            state.submittingCreate = false;
        }
    }

    async function submitUpdateSeason(): Promise<void> {
        if (state.selectedType !== 'service' || state.selectedSeasonId <= 0) {
            return;
        }

        state.submittingUpdate = true;
        state.errors = [];

        try {
            const payload: ServiceSeasonPayload = {
                season_name: state.editForm.season_name || undefined,
                season_start: state.editForm.season_start,
                season_end: state.editForm.season_end,
            };

            await updateServiceSeason(state.selectedSeasonId, payload);
            await loadOverview({ seasonId: state.selectedSeasonId });
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                'Failed to update season.',
            );
        } finally {
            state.submittingUpdate = false;
        }
    }

    async function submitDeleteSeason(): Promise<void> {
        if (state.selectedType !== 'service' || state.selectedSeasonId <= 0) {
            return;
        }

        if (!window.confirm(t('seasons.confirm_delete'))) {
            return;
        }

        state.deletingSeason = true;
        state.errors = [];

        try {
            await deleteServiceSeason(state.selectedSeasonId);
            await loadOverview();
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                'Failed to delete season.',
            );
        } finally {
            state.deletingSeason = false;
        }
    }

    async function submitDefaults(): Promise<void> {
        const start = parseSeasonDayMonth(state.defaultsForm.default_start_md);
        const end = parseSeasonDayMonth(state.defaultsForm.default_end_md);

        if (start === null || end === null) {
            state.errors = [
                {
                    code: 'season_defaults_invalid',
                    message:
                        'Default bounds must be in day-month format (example: 01-09).',
                },
            ];

            return;
        }

        state.submittingDefaults = true;
        state.errors = [];

        try {
            await updateSeasonDefaults({
                default_start_month: start.month,
                default_start_day: start.day,
                default_end_month: end.month,
                default_end_day: end.day,
            });

            await loadOverview({
                seasonId:
                    state.selectedSeasonId > 0
                        ? state.selectedSeasonId
                        : undefined,
            });
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                'Failed to update season defaults.',
            );
        } finally {
            state.submittingDefaults = false;
        }
    }

    function switchType(type: SeasonViewType): void {
        if (state.selectedType === type) {
            return;
        }

        state.selectedType = type;
        state.selectedSeasonId = 0;
        void loadOverview();
    }

    function selectService(serviceId: number): void {
        state.selectedServiceId = serviceId;
        state.selectedSeasonId = 0;
        void loadOverview();
    }

    function selectBonusTitle(title: string): void {
        state.selectedBonusTitle = title;
        state.selectedSeasonId = 0;
        void loadOverview();
    }

    function selectSeason(seasonId: number): void {
        state.selectedSeasonId = seasonId;
        void loadOverview({ seasonId, selecting: true });
    }

    onMounted(() => {
        void loadOverview();
    });

    return {
        t,
        state,
        selectedSeason,
        showDatesColumn,
        formatCurrency,
        loadOverview,
        submitCreateSeason,
        submitUpdateSeason,
        submitDeleteSeason,
        submitDefaults,
        switchType,
        selectService,
        selectBonusTitle,
        selectSeason,
    };
}
