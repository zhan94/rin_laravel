import { useUiI18n } from '@/composables/useUiI18n';
import { formatGermanDate as formatDateLabel } from '@/modules/calendar/dateDisplay';
import {
    buildDateSelectionCalendarCells,
    type DateSelectionCalendarCell,
} from '@/modules/calendar/dateSelectionCalendar';
import {
    firstDayOfMonth,
    formatIsoDate,
    formatMonthCursor,
    parseMonthCursor,
    uniqueSortedDateTokens,
} from '@/modules/calendar/dateUtils';
import { readPositiveIntDeepLinkParam } from '@/modules/notifications/deepLinkQuery';
import { serviceLabelById as serviceLabelFromList } from '@/modules/services/serviceSchedulePresenter';
import {
    createServiceSlot,
    listServices,
    listServiceSlots,
} from '@/modules/services/serviceService';
import type {
    ServiceDefinition,
    ServiceSlot,
    ServiceSlotCreatePayload,
} from '@/modules/services/types';
import { normalizeApiErrors } from '@/services/apiErrors';
import type { ApiError } from '@/services/http';
import { router } from '@inertiajs/vue3';
import { computed, onMounted, reactive, watch } from 'vue';

export type SlotCalendarCell = DateSelectionCalendarCell;

const todayIso = formatIsoDate(new Date());

export function useServicePeriodCreatePage() {
    const { t, formatMonthYear, weekdayShortLabels } = useUiI18n();
    const SLOT_WEEKDAY_LABELS = weekdayShortLabels;

    const state = reactive({
        services: [] as ServiceDefinition[],
        activeServiceSlots: [] as ServiceSlot[],
        loading: false,
        loadingSlots: false,
        submitting: false,
        errors: [] as ApiError[],
        datePickerOpen: false,
        slotForm: {
            service_id: 0,
            name: '',
            max: 0,
            season_type: 'other' as 'winter' | 'other',
            datesCsv: '',
        },
        slotCalendarCursor: '',
    });

    let slotsRequestId = 0;

    const hasServices = computed(() => state.services.length > 0);
    const slotSelectedDates = computed(() =>
        uniqueSortedDateTokens(state.slotForm.datesCsv),
    );

    const slotModalBlockedDateSet = computed(() => {
        const blocked = new Set<string>();

        for (const slot of state.activeServiceSlots) {
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

    function setSlotCalendarCursor(date: Date): void {
        state.slotCalendarCursor = formatMonthCursor(date);
    }

    function initializeSlotCalendarCursor(): void {
        setSlotCalendarCursor(new Date());
    }

    function setSlotSelectedDates(dates: string[]): void {
        state.slotForm.datesCsv = dates.join(', ');
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

    function moveSlotCalendarMonth(delta: number): void {
        const base = slotCalendarMonthDate.value;
        const next = new Date(base.getFullYear(), base.getMonth() + delta, 1);
        setSlotCalendarCursor(next);
    }

    function serviceLabelById(serviceId: number): string {
        return serviceLabelFromList(state.services, serviceId);
    }

    function applySelectedServiceDefaults(serviceId: number): void {
        const selectedService = state.services.find(
            (service) => service.id === serviceId,
        );
        if (!selectedService) {
            return;
        }

        state.slotForm.max = Math.max(Number(selectedService.max) || 0, 0);
    }

    async function loadServiceSlots(serviceId: number): Promise<void> {
        const currentRequestId = ++slotsRequestId;

        if (serviceId <= 0) {
            state.activeServiceSlots = [];
            state.loadingSlots = false;
            return;
        }

        state.loadingSlots = true;
        try {
            const slotsResponse = await listServiceSlots(serviceId, {
                per_page: 300,
            });
            if (currentRequestId !== slotsRequestId) {
                return;
            }

            state.activeServiceSlots = slotsResponse.data;
            removeBlockedSlotDates();
        } catch {
            if (currentRequestId !== slotsRequestId) {
                return;
            }

            state.activeServiceSlots = [];
        } finally {
            if (currentRequestId === slotsRequestId) {
                state.loadingSlots = false;
            }
        }
    }

    async function loadServices(): Promise<void> {
        state.loading = true;
        state.errors = [];

        try {
            const servicesResponse = await listServices({
                page: 1,
                per_page: 150,
                active: true,
            });
            state.services = servicesResponse.data;

            const queryServiceId = readPositiveIntDeepLinkParam(['service_id']);
            state.slotForm.service_id =
                queryServiceId !== null &&
                state.services.some((service) => service.id === queryServiceId)
                    ? queryServiceId
                    : 0;
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                t('services.seasons_load_error'),
            );
        } finally {
            state.loading = false;
        }
    }

    function toggleDatePicker(): void {
        if (state.slotForm.service_id <= 0 || state.submitting) {
            return;
        }

        state.datePickerOpen = !state.datePickerOpen;
        if (state.datePickerOpen) {
            initializeSlotCalendarCursor();
        }
    }

    async function submitSlotForm(): Promise<void> {
        if (state.slotForm.service_id <= 0) {
            state.errors = [
                {
                    code: 'validation',
                    message: t('services.field_select_service'),
                },
            ];
            return;
        }

        if (state.slotForm.name.trim() === '') {
            state.errors = [
                {
                    code: 'validation',
                    message: t('services.enter_season_name'),
                },
            ];
            return;
        }

        if (slotSelectedDates.value.length === 0) {
            state.errors = [
                {
                    code: 'validation',
                    message: t('services.select_period_date'),
                },
            ];
            return;
        }

        state.submitting = true;
        state.errors = [];

        try {
            const payload: ServiceSlotCreatePayload = {
                name: state.slotForm.name.trim(),
                max: Math.max(Number(state.slotForm.max) || 0, 0),
                season_type: state.slotForm.season_type,
                dates: slotSelectedDates.value,
            };

            await createServiceSlot(state.slotForm.service_id, payload);
            router.visit('/crm/services');
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                t('services.create_period_failed'),
            );
        } finally {
            state.submitting = false;
        }
    }

    watch(
        () => state.slotForm.service_id,
        async (serviceId) => {
            state.datePickerOpen = false;
            setSlotSelectedDates([]);

            if (serviceId <= 0) {
                state.activeServiceSlots = [];
                return;
            }

            applySelectedServiceDefaults(serviceId);
            await loadServiceSlots(serviceId);
        },
    );

    onMounted(async () => {
        initializeSlotCalendarCursor();
        await loadServices();
    });

    return {
        t,
        SLOT_WEEKDAY_LABELS,
        clearSlotSelectedDates,
        formatDateLabel,
        hasServices,
        moveSlotCalendarMonth,
        removeSlotSelectedDate,
        serviceLabelById,
        slotCalendarCells,
        slotCalendarMonthLabel,
        slotModalBlockedDateSet,
        slotSelectedDates,
        state,
        submitSlotForm,
        toggleDatePicker,
        toggleSlotCalendarDate,
    };
}
