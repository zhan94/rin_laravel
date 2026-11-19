import { useToast } from '@/composables/useToast';
import { useUiI18n } from '@/composables/useUiI18n';
import { listBonus } from '@/modules/bonus/bonusService';
import type { BonusRecord } from '@/modules/bonus/types';
import { buildDateSelectionCalendarCells } from '@/modules/calendar/dateSelectionCalendar';
import {
    firstDayOfMonth,
    formatIsoDate,
    formatMonthCursor,
    parseIsoDate,
    parseMonthCursor,
    uniqueSortedDateTokens,
} from '@/modules/calendar/dateUtils';
import {
    emptyTranslations,
    LOCALE_LABELS,
    normalizeTranslations,
    SUPPORTED_LOCALES,
    type SupportedLocale,
} from '@/modules/localization';
import { readFirstDeepLinkParam } from '@/modules/notifications/deepLinkQuery';
import {
    buildBonusGroups,
    serviceLabelById as serviceLabelFromList,
    type BonusGroup,
    type SlotCalendarCell,
} from '@/modules/services/serviceSchedulePresenter';
import {
    createService,
    deleteServiceSlot,
    getServiceSlot,
    listServices,
    listServiceSeasons,
    listServiceSlots,
    updateService,
    updateServiceSlot,
} from '@/modules/services/serviceService';
import type {
    PaginationMeta,
    ServiceCreatePayload,
    ServiceDefinition,
    ServiceSeason,
    ServiceSeasonFilters,
    ServiceSlot,
    ServiceSlotCreatePayload,
} from '@/modules/services/types';
import { normalizeApiErrors } from '@/services/apiErrors';
import type { ApiError } from '@/services/http';
import { useAuthStore } from '@/stores/authStore';
import { computed, onMounted, reactive, watch } from 'vue';

export type {
    BonusGroup,
    ServicesTab,
    SlotCalendarCell,
} from '@/modules/services/serviceSchedulePresenter';

export function useServicesPage() {
    const { info } = useToast();
    const auth = useAuthStore();
    const canManage = computed(() => auth.can('manageBookings'));
    let seasonRequest = 0;
    const { formatMonthYear, t, weekdayShortLabels } = useUiI18n();
    const todayIso = formatIsoDate(new Date());
    const SLOT_WEEKDAY_LABELS = weekdayShortLabels;
    const translationFields = SUPPORTED_LOCALES.map((locale) => ({
        locale,
        label: LOCALE_LABELS[locale],
    }));

    const state = reactive({
        services: [] as ServiceDefinition[],
        seasons: [] as ServiceSeason[],
        seasonFilters: {
            q: '',
            service_id: 0,
            date: '',
            status: '',
            sort: 'service',
            direction: 'asc',
        } as ServiceSeasonFilters,
        seasonPagination: null as PaginationMeta | null,
        counts: { services: 0, seasons: 0, dates: 0 },
        slotEditNeighbours: [] as ServiceSlot[],
        loadingSlotEdit: false,
        bonusRows: [] as BonusRecord[],
        loadingOverview: false,
        loadingBonus: false,
        submittingService: false,
        submittingSlot: false,
        deletingSlotId: null as number | null,
        errors: [] as ApiError[],
        activeTab: 'main' as 'main' | 'bonus',
        serviceModalOpen: false,
        serviceFormMode: 'create' as 'create' | 'edit',
        editingServiceId: null as number | null,
        serviceForm: {
            translations: emptyTranslations(),
            max: 25,
            active: true,
        },
        slotModalOpen: false,
        editingSlotId: null as number | null,
        slotForm: {
            service_id: 0,
            name: '',
            max: 20,
            season_type: 'other' as 'winter' | 'summer' | 'other',
            datesCsv: '',
        },
        slotCalendarCursor: '',
    });

    const serviceModalTitle = computed(() =>
        state.serviceFormMode === 'create'
            ? t('services.modal_title_create_name')
            : t('services.modal_title_edit_name'),
    );

    const slotModalTitle = computed(() =>
        t('services.modal_title_edit_period'),
    );

    const slotSelectedDates = computed(() =>
        uniqueSortedDateTokens(state.slotForm.datesCsv),
    );
    const slotModalBlockedDateSet = computed(() => {
        const blocked = new Set<string>();
        const slots = state.slotEditNeighbours;

        for (const slot of slots) {
            if (slot.id === state.editingSlotId) {
                continue;
            }

            for (const date of slot.dates) {
                if (date.slot_date !== null && date.slot_date.trim() !== '') {
                    blocked.add(date.slot_date);
                }
            }
        }

        return blocked;
    });

    const slotCalendarMonthDate = computed(() => {
        const parsed = parseMonthCursor(state.slotCalendarCursor);
        if (parsed !== null) {
            return parsed;
        }

        return firstDayOfMonth(new Date());
    });

    const slotCalendarMonthLabel = computed(() =>
        formatMonthYear(slotCalendarMonthDate.value),
    );

    const slotCalendarCells = computed<SlotCalendarCell[]>(() => {
        return buildDateSelectionCalendarCells({
            monthDate: slotCalendarMonthDate.value,
            selectedDates: slotSelectedDates.value,
            disabledDates: slotModalBlockedDateSet.value,
            todayIso,
        });
    });

    const bonusGroups = computed<BonusGroup[]>(() =>
        buildBonusGroups(state.bonusRows),
    );

    function serviceLabelById(serviceId: number): string {
        return serviceLabelFromList(state.services, serviceId);
    }

    function setSlotCalendarCursor(date: Date): void {
        state.slotCalendarCursor = formatMonthCursor(date);
    }

    function moveSlotCalendarMonth(delta: number): void {
        const base = slotCalendarMonthDate.value;
        const next = new Date(base.getFullYear(), base.getMonth() + delta, 1);
        setSlotCalendarCursor(next);
    }

    function setSlotSelectedDates(dates: string[]): void {
        state.slotForm.datesCsv = dates.join(', ');
    }

    async function loadSeasons(page = 1): Promise<void> {
        const request = ++seasonRequest;
        state.loadingOverview = true;
        state.errors = [];

        try {
            const { data, meta } = await listServiceSeasons({
                ...state.seasonFilters,
                service_id: state.seasonFilters.service_id || undefined,
                page,
            });

            if (request !== seasonRequest) return;

            state.seasons = data;
            state.seasonPagination = meta.pagination as PaginationMeta;
            state.counts = meta.counts as typeof state.counts;
        } catch (error) {
            if (request !== seasonRequest) return;

            state.seasons = [];
            state.seasonPagination = null;
            state.errors = normalizeApiErrors(
                error,
                t('services.seasons_load_error'),
            );
        } finally {
            if (request === seasonRequest) state.loadingOverview = false;
        }
    }

    async function loadServiceNames(): Promise<void> {
        try {
            const services: ServiceDefinition[] = [];
            let page = 1;
            let lastPage = 1;
            do {
                const { data, meta } = await listServices({
                    page,
                    per_page: 100,
                });
                services.push(...data);
                lastPage = (meta.pagination as PaginationMeta).last_page;
                page++;
            } while (page <= lastPage);

            state.services = services;
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                t('services.seasons_load_error'),
            );
        }
    }

    async function loadBonusCatalog(): Promise<void> {
        if (!canManage.value) return;

        state.loadingBonus = true;

        try {
            const response = await listBonus({ page: 1, per_page: 150 });
            state.bonusRows = response.data;
        } catch {
            state.bonusRows = [];
        } finally {
            state.loadingBonus = false;
        }
    }

    function resetServiceForm(): void {
        state.serviceForm.translations = emptyTranslations();
        state.serviceForm.max = 25;
        state.serviceForm.active = true;
    }

    function openServiceNamesModal(): void {
        state.serviceFormMode = 'create';
        state.editingServiceId = null;
        state.errors = [];
        resetServiceForm();
        state.serviceModalOpen = true;
    }

    function openEditServiceModal(service: ServiceDefinition): void {
        state.serviceFormMode = 'edit';
        state.editingServiceId = service.id;
        state.errors = [];

        state.serviceForm.translations = normalizeTranslations(
            service.translations,
        );
        state.serviceForm.max = service.max;
        state.serviceForm.active = service.active;

        state.serviceModalOpen = true;
    }

    async function submitServiceForm(): Promise<void> {
        state.submittingService = true;
        state.errors = [];

        try {
            const payload: ServiceCreatePayload = {
                translations: Object.fromEntries(
                    Object.entries(state.serviceForm.translations).filter(
                        ([, value]) => value.trim() !== '',
                    ),
                ) as Partial<Record<SupportedLocale, string>>,
                max: state.serviceForm.max,
                active: state.serviceForm.active,
            };

            if (
                state.serviceFormMode === 'edit' &&
                state.editingServiceId !== null
            ) {
                await updateService(state.editingServiceId, payload);
            } else {
                await createService(payload);
            }

            state.serviceModalOpen = false;
            resetServiceForm();
            state.editingServiceId = null;

            await Promise.all([loadServiceNames(), loadSeasons()]);
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                state.serviceFormMode === 'edit'
                    ? 'Failed to update service name.'
                    : 'Failed to create service name.',
            );
        } finally {
            state.submittingService = false;
        }
    }

    function initializeSlotCalendarCursor(): void {
        const selected = slotSelectedDates.value;
        if (selected.length > 0) {
            const parsed = parseIsoDate(selected[0]);
            if (parsed !== null) {
                setSlotCalendarCursor(parsed);

                return;
            }
        }

        setSlotCalendarCursor(new Date());
    }

    function toggleSlotCalendarDate(cell: SlotCalendarCell): void {
        if (cell.iso === null || cell.disabled) {
            return;
        }

        const nextDates = [...slotSelectedDates.value];
        const index = nextDates.indexOf(cell.iso);
        if (index >= 0) {
            nextDates.splice(index, 1);
        } else {
            nextDates.push(cell.iso);
        }

        setSlotSelectedDates(
            nextDates.sort((left, right) => left.localeCompare(right)),
        );
    }

    function removeSlotSelectedDate(date: string): void {
        const filtered = slotSelectedDates.value.filter(
            (entry) => entry !== date,
        );
        setSlotSelectedDates(filtered);
    }

    function clearSlotSelectedDates(): void {
        setSlotSelectedDates([]);
    }

    async function openEditSlotModal(season: ServiceSeason): Promise<void> {
        if (state.loadingSlotEdit) return;

        state.loadingSlotEdit = true;
        state.errors = [];
        try {
            const [selected, neighbours] = await Promise.all([
                getServiceSlot(season.id),
                listServiceSlots(season.service_id, { per_page: 200 }),
            ]);
            const slot = selected.data;
            state.slotEditNeighbours = neighbours.data;
            state.editingSlotId = slot.id;
            state.slotForm.service_id = slot.service_id;
            state.slotForm.name = slot.name;
            state.slotForm.max = slot.max;
            state.slotForm.season_type =
                slot.season_type === 'winter' || slot.season_type === 'summer'
                    ? slot.season_type
                    : 'other';
            state.slotForm.datesCsv = slot.dates
                .flatMap((date) => (date.slot_date ? [date.slot_date] : []))
                .join(', ');
            initializeSlotCalendarCursor();
            state.slotModalOpen = true;
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                t('services.seasons_load_error'),
            );
        } finally {
            state.loadingSlotEdit = false;
        }
    }

    async function submitSlotForm(): Promise<void> {
        if (state.slotForm.service_id <= 0) {
            return;
        }

        if (slotSelectedDates.value.length === 0) {
            state.errors = [
                {
                    code: 'validation',
                    message: 'Select at least one date for the service period.',
                },
            ];

            return;
        }

        state.submittingSlot = true;
        state.errors = [];

        try {
            const payload: ServiceSlotCreatePayload = {
                name: state.slotForm.name,
                max: state.slotForm.max,
                season_type: state.slotForm.season_type,
                dates: uniqueSortedDateTokens(state.slotForm.datesCsv),
            };

            if (state.editingSlotId === null) return;

            await updateServiceSlot(state.editingSlotId, payload);

            state.slotModalOpen = false;
            state.editingSlotId = null;

            await loadSeasons(state.seasonPagination?.current_page);
        } catch (error) {
            state.errors = normalizeApiErrors(error, 'Failed to update slot.');
        } finally {
            state.submittingSlot = false;
        }
    }

    async function removeSlot(slotId: number): Promise<void> {
        if (!window.confirm(t('services.confirm_delete_period'))) {
            return;
        }

        state.deletingSlotId = slotId;
        state.errors = [];

        try {
            await deleteServiceSlot(slotId);
            const pagination = state.seasonPagination;
            const page = pagination?.current_page ?? 1;
            await loadSeasons(
                state.seasons.length === 1 ? Math.max(1, page - 1) : page,
            );
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                'Failed to delete service period.',
            );
        } finally {
            state.deletingSlotId = null;
        }
    }

    function deepLinkedServiceQuery(): string | null {
        return readFirstDeepLinkParam(['service', 'q']);
    }

    watch(
        () => state.slotModalOpen,
        (open) => {
            if (!open) {
                state.slotCalendarCursor = '';
            }
        },
    );

    onMounted(async () => {
        const deepLink = deepLinkedServiceQuery();

        if (deepLink !== null) {
            if (/^\d+$/.test(deepLink))
                state.seasonFilters.service_id = Number(deepLink);
            else state.seasonFilters.q = deepLink;

            info(
                'Service Filter Applied',
                'Opened the service filter from the notification.',
            );
        }

        watch(
            () => ({ ...state.seasonFilters }),
            (next, previous, onCleanup) => {
                if (next.q === previous.q) {
                    void loadSeasons();
                    return;
                }

                const timer = setTimeout(() => void loadSeasons(), 250);
                onCleanup(() => clearTimeout(timer));
            },
        );

        await Promise.all([
            loadSeasons(),
            loadServiceNames(),
            loadBonusCatalog(),
        ]);
    });

    return {
        t,
        SLOT_WEEKDAY_LABELS,
        translationFields,
        state,
        canManage,
        loadSeasons,
        serviceModalTitle,
        slotModalTitle,
        slotSelectedDates,
        slotModalBlockedDateSet,
        slotCalendarMonthLabel,
        slotCalendarCells,
        bonusGroups,
        serviceLabelById,
        openServiceNamesModal,
        openEditServiceModal,
        submitServiceForm,
        moveSlotCalendarMonth,
        toggleSlotCalendarDate,
        removeSlotSelectedDate,
        clearSlotSelectedDates,
        openEditSlotModal,
        submitSlotForm,
        removeSlot,
    };
}
