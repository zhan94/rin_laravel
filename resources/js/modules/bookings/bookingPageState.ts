import type { BonusRecord } from '@/modules/bonus/types';
import {
    createDefaultBookingForm,
    type BookingFormState,
} from '@/modules/bookings/bookingForm';
import type {
    BookingRecord,
    BookingSupportBonusContext,
    BookingSupportCustomer,
    BookingSupportLocation,
    BookingSupportOption,
    BookingSupportSlot,
    BookingSupportSuggestion,
    PaginationMeta,
} from '@/modules/bookings/types';
import type { ServiceDefinition } from '@/modules/services/types';
import type { ApiError } from '@/services/http';

export interface BookingPageState {
    rows: BookingRecord[];
    services: ServiceDefinition[];
    bonusServices: BonusRecord[];
    locations: BookingSupportLocation[];
    locationGroups: {
        main: BookingSupportLocation[];
        custom: BookingSupportLocation[];
    };
    locationPickerOpen: boolean;
    locationPickerNativeMobile: boolean;
    slots: BookingSupportSlot[];
    disabledDates: string[];
    cards: BookingSupportOption[];
    equips: BookingSupportOption[];
    bonusContext: BookingSupportBonusContext;
    customerSuggestions: BookingSupportSuggestion[];
    selectedCustomer: BookingSupportCustomer | null;
    customerSearch: string;
    paymentDrafts: Record<number, string | number>;
    paymentMessages: Record<number, string>;
    applyingPaymentId: number;
    cancellingBookingId: number;
    editingBooking: BookingRecord | null;
    loading: boolean;
    loadingServices: boolean;
    loadingBonusServices: boolean;
    loadingSupport: boolean;
    loadingCustomerSuggestions: boolean;
    loadingPrefill: boolean;
    submitting: boolean;
    createStep: number;
    calendarCursor: string;
    isNewCustomer: boolean;
    createStepError: string;
    errors: ApiError[];
    query: string;
    page: number;
    perPage: number;
    pagination: PaginationMeta;
    form: BookingFormState;
}

export function createDefaultBookingPageState(): BookingPageState {
    return {
        rows: [],
        services: [],
        bonusServices: [],
        locations: [],
        locationGroups: createEmptyBookingLocationGroups(),
        locationPickerOpen: false,
        locationPickerNativeMobile: false,
        slots: [],
        disabledDates: [],
        cards: [],
        equips: [],
        bonusContext: createEmptyBookingBonusContext(),
        customerSuggestions: [],
        selectedCustomer: null,
        customerSearch: '',
        paymentDrafts: {},
        paymentMessages: {},
        applyingPaymentId: 0,
        cancellingBookingId: 0,
        editingBooking: null,
        loading: false,
        loadingServices: false,
        loadingBonusServices: false,
        loadingSupport: false,
        loadingCustomerSuggestions: false,
        loadingPrefill: false,
        submitting: false,
        createStep: 1,
        calendarCursor: '',
        isNewCustomer: false,
        createStepError: '',
        errors: [],
        query: '',
        page: 1,
        perPage: 25,
        pagination: {
            total: 0,
            per_page: 25,
            current_page: 1,
            last_page: 1,
        },
        form: createDefaultBookingForm(),
    };
}

export function createEmptyBookingLocationGroups(): BookingPageState['locationGroups'] {
    return {
        main: [],
        custom: [],
    };
}

export function createEmptyBookingBonusContext(): BookingSupportBonusContext {
    return {
        found: false,
        location: '',
        date_from: null,
        date_to: null,
    };
}

export function resetBookingServiceSupport(
    state: BookingPageState,
    options: { resetBonusContext?: boolean } = {},
): void {
    state.locations = [];
    state.locationGroups = createEmptyBookingLocationGroups();
    state.slots = [];
    state.disabledDates = [];
    state.calendarCursor = '';
    state.cards = [];
    state.equips = [];
    state.locationPickerOpen = false;

    if (options.resetBonusContext === true) {
        state.bonusContext = createEmptyBookingBonusContext();
    }
}
