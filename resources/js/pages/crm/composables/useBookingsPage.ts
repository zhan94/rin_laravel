import { useToast } from '@/composables/useToast';
import { useUiI18n } from '@/composables/useUiI18n';
import { listBonus } from '@/modules/bonus/bonusService';
import {
    buildBookingCalendarCells,
    removeDisabledSelectedDates,
    resolveBookingCalendarMonthDate,
    toggleBookingCalendarDateSelection,
    type BookingCalendarCell,
} from '@/modules/bookings/bookingCalendar';
import {
    validateBookingCreateInput,
    validateBookingStepOne,
} from '@/modules/bookings/bookingCreateValidation';
import {
    applyCardToggleToBookingForm,
    applyCustomerPrefillToBookingForm,
    applyEquipToggleToBookingForm,
    resetBookingCreateFields,
    resetBookingServiceSelection,
    trimCustomerNameFields,
} from '@/modules/bookings/bookingForm';
import {
    canToggleCustomLocationPicker,
    parseNativeLocationId,
    readCurrentLocationPickerDeviceSignals,
    shouldCloseLocationPickerOnOutsideClick,
    shouldUseNativeLocationPickerMode,
} from '@/modules/bookings/bookingLocationPicker';
import {
    buildBookingLocationGroups,
    resolveBookingLocationGroups,
    selectedBookingLocationLabel,
    type BookingLocationGroup,
} from '@/modules/bookings/bookingLocations';
import {
    createDefaultBookingPageState,
    createEmptyBookingBonusContext,
    createEmptyBookingLocationGroups,
    resetBookingServiceSupport,
} from '@/modules/bookings/bookingPageState';
import { buildBookingCreatePayload } from '@/modules/bookings/bookingPayload';
import {
    buildPaymentAppliedMessage,
    parseInlinePaymentAmount,
} from '@/modules/bookings/bookingPayment';
import {
    buildActiveBookingServiceOptions,
    buildBookingHeroCopy,
    isCardServiceBooking,
    isEquipServiceBooking,
    isServiceBookingType,
    isSkiServiceBooking,
    type BookingHeroCopy,
} from '@/modules/bookings/bookingPresenter';
import {
    applyBookingPayment,
    createBooking,
    listBookings,
} from '@/modules/bookings/bookingService';
import {
    reconcileBookingSupportSelections,
    selectedSlotIdForAvailability,
} from '@/modules/bookings/bookingSupportState';
import {
    getBonusContext,
    getCustomerServicePrefill,
    getServiceAvailability,
    getServiceOptions,
    getServiceSlots,
} from '@/modules/bookings/supportService';
import type { BookingRecord } from '@/modules/bookings/types';
import {
    chunkIntoWeeks,
    formatIsoDate,
    formatMonthCursor,
    uniqueSortedDateTokens,
} from '@/modules/calendar/dateUtils';
import { readFirstDeepLinkParam } from '@/modules/notifications/deepLinkQuery';
import { listServices } from '@/modules/services/serviceService';
import { firstApiErrorMessage, normalizeApiErrors } from '@/services/apiErrors';
import { paginationFromMeta } from '@/services/pagination';
import {
    computed,
    onBeforeUnmount,
    onMounted,
    reactive,
    ref,
    watch,
} from 'vue';
import { useBookingCustomerSearch } from './useBookingCustomerSearch';

export type CalendarCell = BookingCalendarCell;

export function useBookingsPage() {
    const { info } = useToast();
    const { formatMonthYear, t, weekdayShortLabels } = useUiI18n();

    const WEEKDAY_LABELS = weekdayShortLabels;
    const todayIso = formatIsoDate(new Date());

    const state = reactive(createDefaultBookingPageState());

    const locationPickerRef = ref<HTMLElement | null>(null);
    const {
        clearCustomerSelection,
        disposeCustomerSearch,
        onCustomerSearchInput,
        onToggleNewCustomer,
        selectCustomerSuggestion,
    } = useBookingCustomerSearch(state, loadCustomerPrefill);

    function setLocationPickerElement(element: Element | null): void {
        locationPickerRef.value =
            element instanceof HTMLElement ? element : null;
    }

    const summaryLabel = computed(() => {
        if (state.pagination.total === 0) {
            return t('bookings.summary_empty');
        }

        return t('bookings.summary_total', { count: state.pagination.total });
    });

    const isServiceBooking = computed(() =>
        isServiceBookingType(state.form.service_type),
    );
    const isSkiService = computed(() =>
        isSkiServiceBooking(state.form.service_type, state.form.service_id),
    );
    const isEquipService = computed(() =>
        isEquipServiceBooking(state.form.service_type, state.form.service_id),
    );
    const isCardService = computed(() =>
        isCardServiceBooking(state.form.service_type, state.form.service_id),
    );

    const activeServiceOptions = computed(() =>
        buildActiveBookingServiceOptions(
            state.form.service_type,
            state.services,
            state.bonusServices,
        ),
    );

    const heroCopy = computed<BookingHeroCopy>(() =>
        buildBookingHeroCopy(state.createStep, {
            stepOne: {
                eyebrow: t('bookings.eyebrow'),
                title: t('customers.add_customer'),
                subtitle: t('bookings.subtitle'),
            },
            stepTwo: {
                eyebrow: t('bookings.step2_eyebrow'),
                title: t('bookings.step2_title'),
                subtitle: t('bookings.step2_subtitle'),
            },
        }),
    );

    const selectedLocationLabel = computed(() =>
        selectedBookingLocationLabel(
            state.locations,
            state.form.location_id,
            t('bookings.location_placeholder'),
        ),
    );

    const groupedLocations = computed<BookingLocationGroup[]>(() =>
        buildBookingLocationGroups({
            locations: state.locations,
            locationGroups: state.locationGroups,
            labels: {
                main: t('bookings.main_locations'),
                custom: t('bookings.custom_locations'),
                placeholder: t('bookings.location_placeholder'),
            },
        }),
    );

    const selectedDates = computed(() => normalizeDateCsv());
    const selectedDateCount = computed(() => selectedDates.value.length);

    const calendarMonthDate = computed(() =>
        resolveBookingCalendarMonthDate(state.calendarCursor, new Date()),
    );

    const calendarMonthLabel = computed(() =>
        formatMonthYear(calendarMonthDate.value),
    );

    const calendarCells = computed<CalendarCell[]>(() =>
        buildBookingCalendarCells({
            monthDate: calendarMonthDate.value,
            selectedDates: selectedDates.value,
            disabledDates: state.disabledDates,
            todayIso,
        }),
    );

    const calendarWeeks = computed<CalendarCell[][]>(() =>
        chunkIntoWeeks(calendarCells.value),
    );

    async function loadServicesForForm(): Promise<void> {
        state.loadingServices = true;

        try {
            const response = await listServices({
                page: 1,
                per_page: 100,
                active: true,
            });

            state.services = response.data;
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                'Failed to load services.',
            );
        } finally {
            state.loadingServices = false;
        }
    }

    async function loadBonusServicesForForm(): Promise<void> {
        state.loadingBonusServices = true;

        try {
            const response = await listBonus({
                page: 1,
                per_page: 100,
            });

            state.bonusServices = response.data;
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                'Failed to load bonus services.',
            );
        } finally {
            state.loadingBonusServices = false;
        }
    }

    async function loadBonusServiceContext(): Promise<void> {
        if (state.form.service_type !== 2 || state.form.service_id <= 0) {
            state.bonusContext = createEmptyBookingBonusContext();

            return;
        }

        try {
            const response = await getBonusContext(state.form.service_id);
            state.bonusContext = response.data;
        } catch {
            state.bonusContext = createEmptyBookingBonusContext();
        }
    }

    async function loadServiceSupport(): Promise<void> {
        if (state.form.service_id <= 0) {
            resetBookingServiceSupport(state);

            return;
        }

        state.loadingSupport = true;

        try {
            const [optionsResponse, slotsResponse] = await Promise.all([
                getServiceOptions(state.form.service_id),
                getServiceSlots(state.form.service_id),
            ]);

            state.cards = optionsResponse.data.cards;
            state.equips = optionsResponse.data.equips;
            state.slots = slotsResponse.data;

            const availabilityResponse = await getServiceAvailability(
                state.form.service_id,
                {
                    customerId: state.form.customer_id ?? undefined,
                    slotId: selectedSlotIdForAvailability(state.form.slot_id),
                },
            );

            state.locations = availabilityResponse.data.locations;
            state.locationGroups = resolveBookingLocationGroups(
                state.locations,
                availabilityResponse.data.location_groups,
            );
            state.disabledDates = availabilityResponse.data.disabled_dates;

            const reconciliation = reconcileBookingSupportSelections(
                {
                    cards: state.cards,
                    equips: state.equips,
                    slots: state.slots,
                    locations: state.locations,
                },
                {
                    cardId: state.form.card_id,
                    equipId: state.form.equip_id,
                    slotId: state.form.slot_id,
                    locationId: state.form.location_id,
                },
            );

            state.form.card_id = reconciliation.selections.cardId;
            state.form.equip_id = reconciliation.selections.equipId;
            state.form.slot_id = reconciliation.selections.slotId;
            state.form.location_id = reconciliation.selections.locationId;

            if (reconciliation.shouldCloseLocationPicker) {
                state.locationPickerOpen = false;
            }

            removeInvalidSelectedDates();
            initializeCalendarCursor();
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                'Failed to load booking support data.',
            );
        } finally {
            state.loadingSupport = false;
        }
    }

    function normalizeDateCsv(): string[] {
        return uniqueSortedDateTokens(state.form.datesCsv);
    }

    function setSelectedDates(dates: string[]): void {
        state.form.datesCsv = dates.join(', ');
    }

    function setCalendarCursorFromDate(date: Date): void {
        state.calendarCursor = formatMonthCursor(date);
    }

    function initializeCalendarCursor(): void {
        setCalendarCursorFromDate(new Date());
    }

    function removeInvalidSelectedDates(): void {
        const filtered = removeDisabledSelectedDates(
            selectedDates.value,
            state.disabledDates,
        );

        if (filtered.length !== selectedDates.value.length) {
            setSelectedDates(filtered);
        }
    }

    function moveCalendarMonth(delta: number): void {
        const base = calendarMonthDate.value;
        const next = new Date(base.getFullYear(), base.getMonth() + delta, 1);
        setCalendarCursorFromDate(next);
    }

    function toggleCalendarDate(cell: CalendarCell): void {
        if (!cell.selectable || cell.iso === null) {
            return;
        }

        setSelectedDates(
            toggleBookingCalendarDateSelection(selectedDates.value, cell.iso),
        );
        void loadCustomerPrefill();
    }

    function shouldUseNativeLocationPicker(): boolean {
        return shouldUseNativeLocationPickerMode(
            readCurrentLocationPickerDeviceSignals(),
        );
    }

    function syncLocationPickerMode(): void {
        state.locationPickerNativeMobile = shouldUseNativeLocationPicker();

        if (state.locationPickerNativeMobile) {
            state.locationPickerOpen = false;
        }
    }

    function toggleLocationPicker(): void {
        if (
            !canToggleCustomLocationPicker({
                loadingSupport: state.loadingSupport,
                locationsCount: state.locations.length,
                nativeMobile: state.locationPickerNativeMobile,
            })
        ) {
            return;
        }

        state.locationPickerOpen = !state.locationPickerOpen;
    }

    function closeLocationPicker(): void {
        state.locationPickerOpen = false;
    }

    function selectLocation(locationId: number): void {
        state.form.location_id = locationId;
        closeLocationPicker();
    }

    function onLocationNativeChange(event: Event): void {
        const target = event.target as HTMLSelectElement | null;
        if (target === null) {
            return;
        }

        state.form.location_id = parseNativeLocationId(target.value);
        closeLocationPicker();
    }

    function onCardToggle(event: Event): void {
        const target = event.target as HTMLInputElement | null;

        applyCardToggleToBookingForm(
            state.form,
            target?.checked ?? false,
            state.cards,
        );
    }

    function onEquipToggle(event: Event): void {
        const target = event.target as HTMLInputElement | null;

        applyEquipToggleToBookingForm(
            state.form,
            target?.checked ?? false,
            state.equips,
        );
    }

    function onDocumentClick(event: MouseEvent): void {
        if (
            shouldCloseLocationPickerOnOutsideClick({
                isOpen: state.locationPickerOpen,
                pickerElement: locationPickerRef.value,
                target: event.target,
            })
        ) {
            closeLocationPicker();
        }
    }

    function onDocumentKeydown(event: KeyboardEvent): void {
        if (event.key === 'Escape') {
            closeLocationPicker();
        }
    }

    function continueToStepTwo(): void {
        state.createStepError = '';

        const validation = validateBookingStepOne({
            serviceType: state.form.service_type,
            isNewCustomer: state.isNewCustomer,
            customerId: state.form.customer_id,
        });

        if (validation !== null) {
            state.createStepError = t(validation.messageKey);

            return;
        }

        state.createStep = 2;
        trimCustomerNameFields(state.form);
    }

    function backToStepOne(): void {
        state.createStep = 1;
        state.createStepError = '';
        closeLocationPicker();
    }

    function resetCreateFlow(): void {
        state.createStep = 1;
        state.createStepError = '';
        state.isNewCustomer = false;
        clearCustomerSelection();
        resetBookingCreateFields(state.form);
        state.locationGroups = createEmptyBookingLocationGroups();
        closeLocationPicker();
        state.calendarCursor = '';
    }

    async function loadCustomerPrefill(): Promise<void> {
        if ((state.form.customer_id ?? 0) <= 0 || state.form.service_id <= 0) {
            return;
        }

        state.loadingPrefill = true;

        try {
            const selectedDate = normalizeDateCsv()[0];

            const response = await getCustomerServicePrefill(
                state.form.customer_id as number,
                state.form.service_id,
                selectedDate,
            );

            if (!response.data.found || response.data.booking === null) {
                return;
            }

            applyCustomerPrefillToBookingForm(
                state.form,
                response.data.booking,
            );
        } catch {
            // Prefill is optional; keep manual entry working.
        } finally {
            state.loadingPrefill = false;
        }
    }

    async function loadBookings(): Promise<void> {
        state.loading = true;
        state.errors = [];

        try {
            const response = await listBookings({
                q: state.query || undefined,
                page: state.page,
                per_page: state.perPage,
            });

            state.rows = response.data;
            state.pagination = paginationFromMeta(response.meta.pagination, {
                perPage: state.perPage,
            });
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                'Failed to load bookings.',
            );
        } finally {
            state.loading = false;
        }
    }

    async function createNewBooking(): Promise<void> {
        if (state.submitting) return;

        state.submitting = true;
        state.errors = [];

        try {
            const dates = normalizeDateCsv();

            const validation = validateBookingCreateInput({
                isServiceBooking: isServiceBooking.value,
                isSkiService: isSkiService.value,
                locationId: state.form.location_id,
                dates,
                skillId: state.form.skill_id,
            });

            if (validation !== null) {
                state.errors = [
                    {
                        code: validation.code,
                        message: t(validation.messageKey),
                    },
                ];

                return;
            }

            const payload = buildBookingCreatePayload(
                {
                    isNewCustomer: state.isNewCustomer,
                    isServiceBooking: isServiceBooking.value,
                    form: state.form,
                },
                dates,
            );

            await createBooking(payload);

            resetCreateFlow();

            await loadBookings();
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                'Failed to create booking.',
            );
        } finally {
            state.submitting = false;
        }
    }

    async function applyInlinePayment(booking: BookingRecord): Promise<void> {
        if (state.applyingPaymentId !== 0) return;

        const payment = parseInlinePaymentAmount(
            state.paymentDrafts[booking.id],
        );

        if (!payment.valid) {
            state.paymentMessages[booking.id] = t(
                payment.messageKey ?? 'bookings.error_payment_positive',
            );

            return;
        }

        state.applyingPaymentId = booking.id;

        try {
            const applied = await applyBookingPayment(
                booking.id,
                payment.amount,
                'increment',
            );

            state.paymentDrafts[booking.id] = '';
            const successMessage = buildPaymentAppliedMessage(
                applied.data.payment.remaining_eur,
            );
            state.paymentMessages[booking.id] = t(
                successMessage.messageKey,
                successMessage.params,
            );

            await loadBookings();
        } catch (error) {
            state.paymentMessages[booking.id] = firstApiErrorMessage(
                error,
                t('bookings.payment_failed'),
            );
        } finally {
            state.applyingPaymentId = 0;
        }
    }

    function goToPage(page: number): void {
        state.page = page;
        void loadBookings();
    }

    function deepLinkedBookingQuery(): string | null {
        return readFirstDeepLinkParam(['booking', 'q']);
    }

    watch(
        () => state.form.service_type,
        () => {
            resetBookingServiceSupport(state, { resetBonusContext: true });
            resetBookingServiceSelection(state.form);
        },
    );

    watch(
        () => state.form.service_id,
        () => {
            closeLocationPicker();

            if (state.form.service_type === 2) {
                void loadBonusServiceContext();

                return;
            }

            void loadServiceSupport();
            void loadCustomerPrefill();
        },
    );

    watch(
        () => state.form.slot_id,
        () => {
            if (state.form.service_type !== 1 || state.form.service_id <= 0) {
                return;
            }

            void loadServiceSupport();
            void loadCustomerPrefill();
        },
    );

    watch(
        () => state.form.customer_id,
        () => {
            if (state.form.service_type === 2) {
                return;
            }

            void loadServiceSupport();
            void loadCustomerPrefill();
        },
    );

    watch(
        () => state.createStep,
        (nextStep) => {
            if (nextStep !== 2) {
                closeLocationPicker();
            }
        },
    );

    onMounted(async () => {
        document.addEventListener('click', onDocumentClick);
        document.addEventListener('keydown', onDocumentKeydown);
        window.addEventListener('resize', syncLocationPickerMode);
        window.addEventListener('orientationchange', syncLocationPickerMode);
        syncLocationPickerMode();

        const deepLink = deepLinkedBookingQuery();
        if (deepLink !== null) {
            state.query = deepLink;
        }

        await loadServicesForForm();
        await loadBonusServicesForForm();
        if (state.form.service_type === 2) {
            await loadBonusServiceContext();
        } else {
            await loadServiceSupport();
        }
        await loadBookings();

        if (deepLink !== null) {
            info(
                t('bookings.deep_link_title'),
                t('bookings.deep_link_body', { id: deepLink }),
            );
        }
    });

    onBeforeUnmount(() => {
        document.removeEventListener('click', onDocumentClick);
        document.removeEventListener('keydown', onDocumentKeydown);
        window.removeEventListener('resize', syncLocationPickerMode);
        window.removeEventListener('orientationchange', syncLocationPickerMode);

        disposeCustomerSearch();
    });

    return {
        t,
        WEEKDAY_LABELS,
        state,
        setLocationPickerElement,
        summaryLabel,
        isServiceBooking,
        isSkiService,
        isEquipService,
        isCardService,
        activeServiceOptions,
        heroCopy,
        selectedLocationLabel,
        groupedLocations,
        selectedDateCount,
        calendarMonthLabel,
        calendarWeeks,
        continueToStepTwo,
        onCustomerSearchInput,
        selectCustomerSuggestion,
        onToggleNewCustomer,
        createNewBooking,
        moveCalendarMonth,
        toggleCalendarDate,
        toggleLocationPicker,
        onLocationNativeChange,
        selectLocation,
        onCardToggle,
        onEquipToggle,
        backToStepOne,
        loadBookings,
        applyInlinePayment,
        goToPage,
    };
}
