import {
    applyCustomerToBookingForm,
    clearCustomerNameFields,
    clearSelectedCustomerFields,
} from '@/modules/bookings/bookingForm';
import type { BookingPageState } from '@/modules/bookings/bookingPageState';
import {
    autocompleteCustomers,
    getCustomerById,
} from '@/modules/bookings/supportService';
import type { BookingSupportSuggestion } from '@/modules/bookings/types';

export function useBookingCustomerSearch(state: BookingPageState) {
    let customerSearchTimer: ReturnType<typeof setTimeout> | null = null;

    async function loadCustomerSuggestions(query: string): Promise<void> {
        if (query.length < 2) {
            state.customerSuggestions = [];

            return;
        }

        state.loadingCustomerSuggestions = true;

        try {
            const response = await autocompleteCustomers(query);
            state.customerSuggestions = response.data;
        } catch {
            state.customerSuggestions = [];
        } finally {
            state.loadingCustomerSuggestions = false;
        }
    }

    function onCustomerSearchInput(): void {
        if (state.isNewCustomer) {
            state.customerSuggestions = [];

            return;
        }

        const query = state.customerSearch.trim();

        if (customerSearchTimer !== null) {
            clearTimeout(customerSearchTimer);
        }

        if (query.length < 2) {
            state.customerSuggestions = [];

            return;
        }

        customerSearchTimer = setTimeout(() => {
            customerSearchTimer = null;
            void loadCustomerSuggestions(query);
        }, 250);
    }

    async function selectCustomerSuggestion(
        suggestion: BookingSupportSuggestion,
    ): Promise<void> {
        state.customerSearch = suggestion.label;
        state.customerSuggestions = [];
        state.isNewCustomer = false;
        state.form.customer_id = suggestion.customer_id;

        try {
            const response = await getCustomerById(suggestion.customer_id);
            state.selectedCustomer = response.data;

            applyCustomerToBookingForm(state.form, response.data);
        } catch {
            state.selectedCustomer = null;
        }
    }

    function clearCustomerSelection(): void {
        state.customerSearch = '';
        state.customerSuggestions = [];
        state.selectedCustomer = null;
        clearSelectedCustomerFields(state.form);
    }

    function onToggleNewCustomer(): void {
        if (state.isNewCustomer) {
            clearCustomerSelection();
            clearCustomerNameFields(state.form);
        }
    }

    function disposeCustomerSearch(): void {
        if (customerSearchTimer !== null) {
            clearTimeout(customerSearchTimer);
            customerSearchTimer = null;
        }
    }

    return {
        clearCustomerSelection,
        disposeCustomerSearch,
        onCustomerSearchInput,
        onToggleNewCustomer,
        selectCustomerSuggestion,
    };
}
