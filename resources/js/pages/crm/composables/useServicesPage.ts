import { useToast } from '@/composables/useToast';
import { useUiI18n } from '@/composables/useUiI18n';
import { listBonus } from '@/modules/bonus/bonusService';
import type { BonusRecord } from '@/modules/bonus/types';
import { buildDateSelectionCalendarCells } from '@/modules/calendar/dateSelectionCalendar';
import {
    chunkIntoWeeks,
    firstDayOfMonth,
    formatIsoDate,
    formatMonthCursor,
    lastDayOfMonth,
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
    buildServicePanels,
    groupSlotsBySeason as groupSlotsBySeasonPresenter,
    reportsUrl,
    reportsUrlForDate,
    serviceLabelById as serviceLabelFromList,
    slotStatusClass,
    type BonusGroup,
    type ServicePanel,
    type ServicesTab,
    type ServiceStatusLabels,
    type SlotCalendarCell,
    type SlotGroup,
    type SlotMonthCalendarCell,
    type SlotViewModel,
} from '@/modules/services/serviceSchedulePresenter';
import {
    createService,
    createServiceSlot,
    deleteServiceSlot,
    listServices,
    listServiceSlots,
    updateService,
    updateServiceSlot,
} from '@/modules/services/serviceService';
import type {
    ServiceCreatePayload,
    ServiceDefinition,
    ServiceSlot,
    ServiceSlotCreatePayload,
} from '@/modules/services/types';
import { normalizeApiErrors } from '@/services/apiErrors';
import type { ApiError } from '@/services/http';
import { router } from '@inertiajs/vue3';
import { computed, onMounted, reactive, watch } from 'vue';

export type {
    BonusGroup,
    ServicePanel,
    ServicesTab,
    SlotCalendarCell,
    SlotGroup,
    SlotMonthCalendarCell,
    SlotViewModel,
} from '@/modules/services/serviceSchedulePresenter';

export function useServicesPage() {
    const { info } = useToast();
    const { formatMonthYear, t, weekdayShortLabels } = useUiI18n();
    const todayIso = formatIsoDate(new Date());
    const SLOT_WEEKDAY_LABELS = weekdayShortLabels;
    const translationFields = SUPPORTED_LOCALES.map((locale) => ({
        locale,
        label: LOCALE_LABELS[locale],
    }));

    const state = reactive({
        services: [] as ServiceDefinition[],
        slotsByService: {} as Record<number, ServiceSlot[]>,
        bonusRows: [] as BonusRecord[],
        loadingOverview: false,
        loadingBonus: false,
        submittingService: false,
        submittingSlot: false,
        deletingSlotId: null as number | null,
        errors: [] as ApiError[],
        search: '',
        activeTab: 'main' as ServicesTab,
        openCalendars: {} as Record<number, boolean>,
        slotRowCalendarCursors: {} as Record<number, string>,
        serviceModalOpen: false,
        serviceFormMode: 'create' as 'create' | 'edit',
        editingServiceId: null as number | null,
        serviceForm: {
            translations: emptyTranslations(),
            max: 25,
            active: true,
        },
        slotModalOpen: false,
        slotFormMode: 'create' as 'create' | 'edit',
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

    const servicesCount = computed(() => state.services.length);

    const periodsCount = computed(() =>
        Object.values(state.slotsByService).reduce(
            (total, slots) => total + slots.length,
            0,
        ),
    );

    const datesCount = computed(() =>
        Object.values(state.slotsByService).reduce(
            (total, slots) =>
                total + slots.reduce((sum, slot) => sum + slot.dates.length, 0),
            0,
        ),
    );

    const serviceModalTitle = computed(() =>
        state.serviceFormMode === 'create'
            ? t('services.modal_title_create_name')
            : t('services.modal_title_edit_name'),
    );

    const slotModalTitle = computed(() =>
        state.slotFormMode === 'create'
            ? t('services.modal_title_create_period')
            : t('services.modal_title_edit_period'),
    );

    const slotSelectedDates = computed(() =>
        uniqueSortedDateTokens(state.slotForm.datesCsv),
    );
    const slotModalBlockedDateSet = computed(() => {
        const blocked = new Set<string>();
        const slots = state.slotsByService[state.slotForm.service_id] ?? [];

        for (const slot of slots) {
            if (
                state.slotFormMode === 'edit' &&
                state.editingSlotId !== null &&
                slot.id === state.editingSlotId
            ) {
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

    const serviceStatusLabels = computed<ServiceStatusLabels>(() => ({
        active: t('services.status_active'),
        empty: t('services.status_empty'),
        past: t('services.status_past'),
        full: t('services.status_full'),
    }));

    const filteredServicePanels = computed<ServicePanel[]>(() =>
        buildServicePanels(
            state.services,
            state.slotsByService,
            state.activeTab,
            todayIso,
            serviceStatusLabels.value,
        ),
    );

    const bonusGroups = computed<BonusGroup[]>(() =>
        buildBonusGroups(state.bonusRows),
    );

    function serviceLabelById(serviceId: number): string {
        return serviceLabelFromList(state.services, serviceId);
    }

    function groupSlotsBySeason(slots: SlotViewModel[]): SlotGroup[] {
        return groupSlotsBySeasonPresenter(slots, {
            winter: t('services.group_winter_periods'),
            other: t('services.group_other'),
        });
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

    function rowCalendarMonthDate(slot: SlotViewModel): Date {
        const cursor = state.slotRowCalendarCursors[slot.id];
        if (typeof cursor === 'string' && cursor.trim() !== '') {
            const parsed = parseMonthCursor(cursor);
            if (parsed !== null) {
                return parsed;
            }
        }

        if (slot.datesIso.length > 0) {
            const parsed = parseIsoDate(slot.datesIso[0]);
            if (parsed !== null) {
                return firstDayOfMonth(parsed);
            }
        }

        return firstDayOfMonth(new Date());
    }

    function rowCalendarMonthLabel(slot: SlotViewModel): string {
        return formatMonthYear(rowCalendarMonthDate(slot));
    }

    function setRowCalendarCursor(slotId: number, date: Date): void {
        state.slotRowCalendarCursors[slotId] = formatMonthCursor(date);
    }

    function ensureRowCalendarCursor(slot: SlotViewModel): void {
        if (
            typeof state.slotRowCalendarCursors[slot.id] === 'string' &&
            state.slotRowCalendarCursors[slot.id].trim() !== ''
        ) {
            return;
        }

        const base =
            slot.datesIso.length > 0
                ? (parseIsoDate(slot.datesIso[0]) ?? new Date())
                : new Date();
        setRowCalendarCursor(slot.id, firstDayOfMonth(base));
    }

    function moveRowCalendarMonth(slot: SlotViewModel, delta: number): void {
        const base = rowCalendarMonthDate(slot);
        const next = new Date(base.getFullYear(), base.getMonth() + delta, 1);
        setRowCalendarCursor(slot.id, next);
    }

    function rowCalendarCells(slot: SlotViewModel): SlotMonthCalendarCell[] {
        const monthDate = rowCalendarMonthDate(slot);
        const firstDay = firstDayOfMonth(monthDate);
        const daysInMonth = lastDayOfMonth(monthDate).getDate();
        const leadingBlankDays = firstDay.getDay();

        const cells: SlotMonthCalendarCell[] = [];
        for (let index = 0; index < leadingBlankDays; index += 1) {
            cells.push({
                key: `slot-${slot.id}-blank-leading-${index}`,
                iso: null,
                dayNumber: null,
                isServiceDate: false,
                isBookedDate: false,
                isToday: false,
            });
        }

        for (let day = 1; day <= daysInMonth; day += 1) {
            const iso = formatIsoDate(
                new Date(firstDay.getFullYear(), firstDay.getMonth(), day),
            );
            const isServiceDate = slot.dateSet.has(iso);
            const isBookedDate = slot.bookedDateSet.has(iso);

            cells.push({
                key: `slot-${slot.id}-${iso}`,
                iso,
                dayNumber: day,
                isServiceDate,
                isBookedDate,
                isToday: iso === todayIso,
            });
        }

        while (cells.length % 7 !== 0) {
            const index = cells.length;
            cells.push({
                key: `slot-${slot.id}-blank-trailing-${index}`,
                iso: null,
                dayNumber: null,
                isServiceDate: false,
                isBookedDate: false,
                isToday: false,
            });
        }

        return cells;
    }

    function rowCalendarWeeks(slot: SlotViewModel): SlotMonthCalendarCell[][] {
        return chunkIntoWeeks(rowCalendarCells(slot));
    }

    function openReportForSlotDate(serviceId: number, isoDate: string): void {
        router.visit(reportsUrlForDate(serviceId, isoDate));
    }

    function isCalendarOpen(slotId: number): boolean {
        return state.openCalendars[slotId] === true;
    }

    function toggleCalendar(slot: SlotViewModel): void {
        const currentlyOpen = isCalendarOpen(slot.id);
        state.openCalendars[slot.id] = !currentlyOpen;

        if (!currentlyOpen) {
            ensureRowCalendarCursor(slot);
        }
    }

    async function loadServicesAndSlots(): Promise<void> {
        state.loadingOverview = true;
        state.errors = [];

        try {
            const servicesResponse = await listServices({
                q: state.search || undefined,
                page: 1,
                per_page: 100,
            });

            state.services = servicesResponse.data;

            const slotEntries = await Promise.all(
                state.services.map(async (service) => {
                    try {
                        const slotsResponse = await listServiceSlots(
                            service.id,
                            { per_page: 200 },
                        );

                        return [service.id, slotsResponse.data] as const;
                    } catch {
                        return [service.id, [] as ServiceSlot[]] as const;
                    }
                }),
            );

            const slotsByService: Record<number, ServiceSlot[]> = {};
            for (const [serviceId, slots] of slotEntries) {
                slotsByService[serviceId] = slots;
            }

            state.slotsByService = slotsByService;

            if (
                state.slotForm.service_id <= 0 ||
                !state.services.some(
                    (service) => service.id === state.slotForm.service_id,
                )
            ) {
                state.slotForm.service_id = state.services[0]?.id ?? 0;
            }
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                'Failed to load services.',
            );
        } finally {
            state.loadingOverview = false;
        }
    }

    async function loadBonusCatalog(): Promise<void> {
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

            await loadServicesAndSlots();
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

    function resetSlotForm(): void {
        state.slotForm.service_id = state.services[0]?.id ?? 0;
        state.slotForm.name = '';
        state.slotForm.max = 20;
        state.slotForm.season_type = 'other';
        state.slotForm.datesCsv = '';
        state.slotCalendarCursor = '';
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

    function removeBlockedSlotDates(): void {
        const filtered = slotSelectedDates.value.filter(
            (date) => !slotModalBlockedDateSet.value.has(date),
        );
        if (filtered.length !== slotSelectedDates.value.length) {
            setSlotSelectedDates(filtered);
        }
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

    function openCreateSlotModal(): void {
        state.slotFormMode = 'create';
        state.editingSlotId = null;
        state.errors = [];
        resetSlotForm();
        initializeSlotCalendarCursor();
        state.slotModalOpen = true;
    }

    function openEditSlotModal(serviceId: number, slot: SlotViewModel): void {
        state.slotFormMode = 'edit';
        state.editingSlotId = slot.id;
        state.errors = [];

        state.slotForm.service_id = serviceId;
        state.slotForm.name = slot.name;
        state.slotForm.max = slot.max;
        state.slotForm.season_type = slot.seasonTypeRaw;
        state.slotForm.datesCsv = slot.datesIso.join(', ');
        initializeSlotCalendarCursor();

        state.slotModalOpen = true;
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

            if (state.slotFormMode === 'edit' && state.editingSlotId !== null) {
                await updateServiceSlot(state.editingSlotId, payload);
            } else {
                await createServiceSlot(state.slotForm.service_id, payload);
            }

            state.slotModalOpen = false;
            state.editingSlotId = null;
            resetSlotForm();

            await loadServicesAndSlots();
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                state.slotFormMode === 'edit'
                    ? 'Failed to update slot.'
                    : 'Failed to create slot.',
            );
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
            await loadServicesAndSlots();
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
        () => state.slotForm.service_id,
        () => {
            if (!state.slotModalOpen) {
                return;
            }

            removeBlockedSlotDates();

            if (state.slotCalendarCursor.trim() === '') {
                initializeSlotCalendarCursor();
            }
        },
    );

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
            state.search = deepLink;
        }

        await Promise.all([loadServicesAndSlots(), loadBonusCatalog()]);

        if (deepLink !== null) {
            info(
                'Service Filter Applied',
                `Opened notification context for service #${deepLink}.`,
            );
        }
    });

    return {
        t,
        SLOT_WEEKDAY_LABELS,
        translationFields,
        state,
        servicesCount,
        periodsCount,
        datesCount,
        serviceModalTitle,
        slotModalTitle,
        slotSelectedDates,
        slotModalBlockedDateSet,
        slotCalendarMonthLabel,
        slotCalendarCells,
        filteredServicePanels,
        bonusGroups,
        serviceLabelById,
        groupSlotsBySeason,
        slotStatusClass,
        reportsUrl,
        rowCalendarMonthLabel,
        rowCalendarWeeks,
        moveRowCalendarMonth,
        openReportForSlotDate,
        isCalendarOpen,
        toggleCalendar,
        loadServicesAndSlots,
        openServiceNamesModal,
        openEditServiceModal,
        submitServiceForm,
        moveSlotCalendarMonth,
        toggleSlotCalendarDate,
        removeSlotSelectedDate,
        clearSlotSelectedDates,
        openCreateSlotModal,
        openEditSlotModal,
        submitSlotForm,
        removeSlot,
    };
}
