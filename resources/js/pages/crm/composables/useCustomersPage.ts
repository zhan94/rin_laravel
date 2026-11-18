import { useToast } from '@/composables/useToast';
import { useUiI18n } from '@/composables/useUiI18n';
import {
    applyBookingPayment,
    listBookings,
} from '@/modules/bookings/bookingService';
import type { BookingRecord } from '@/modules/bookings/types';
import {
    createCustomer,
    deleteCustomer,
    getCustomer,
    listCustomers,
    listUnpaidCustomers,
    updateCustomer,
} from '@/modules/customers/customerService';
import type {
    Customer,
    CustomerCreatePayload,
    CustomerUnpaidRow,
    CustomerUnpaidTab,
    PaginationMeta,
} from '@/modules/customers/types';
import { readFirstDeepLinkParam } from '@/modules/notifications/deepLinkQuery';
import { firstApiErrorMessage, normalizeApiErrors } from '@/services/apiErrors';
import type { ApiError } from '@/services/http';
import { paginationFromMeta } from '@/services/pagination';
import { computed, onMounted, reactive } from 'vue';

export function useCustomersPage() {
    const toast = useToast();
    const { t } = useUiI18n();

    const state = reactive({
        rows: [] as Customer[],
        unpaidRows: [] as CustomerUnpaidRow[],
        unpaidTabs: [] as CustomerUnpaidTab[],
        loading: false,
        submitting: false,
        deletingId: null as number | null,
        detailsLoading: false,
        errors: [] as ApiError[],
        unpaidMode: false,
        query: '',
        page: 1,
        perPage: 25,
        pagination: {
            total: 0,
            per_page: 25,
            current_page: 1,
            last_page: 1,
        } as PaginationMeta,
        formOpen: false,
        unpaidRecords: 0,
        unpaidTotalDue: 0,
        selectedUnpaidTabId: '',
        currentUnpaidSeasonLabel: '',
        editingId: null as number | null,
        selectedCustomer: null as Customer | null,
        selectedCustomerBookings: [] as BookingRecord[],
        applyingPaymentBookingId: 0,
        paymentDrafts: {} as Record<number, string>,
        form: {
            firstname: '',
            surname: '',
            lastname: '',
            date_birth: '',
            age: null as number | null,
            parent_name: '',
            parent_phone: '',
            email: '',
            active: true,
        },
    });

    const activeUnpaidTab = computed(() => {
        if (state.unpaidTabs.length === 0) {
            return null;
        }

        if (state.selectedUnpaidTabId !== '') {
            const selected = state.unpaidTabs.find(
                (tab) => tab.id === state.selectedUnpaidTabId,
            );
            if (selected !== undefined) {
                return selected;
            }
        }

        return state.unpaidTabs[0] ?? null;
    });

    const visibleUnpaidRows = computed((): CustomerUnpaidRow[] => {
        if (!state.unpaidMode) {
            return [];
        }

        const tab = activeUnpaidTab.value;
        if (tab === null) {
            return state.unpaidRows;
        }

        return state.unpaidRows.filter((row) => row.season_label === tab.label);
    });

    const visibleUnpaidTotalDue = computed(() => {
        return visibleUnpaidRows.value.reduce(
            (sum, row) => sum + Number(row.due ?? 0),
            0,
        );
    });

    const summaryLabel = computed(() => {
        if (state.unpaidMode) {
            if (state.unpaidRecords === 0) {
                return t('customers.summary_unpaid_empty');
            }

            const tab = activeUnpaidTab.value;
            if (tab !== null) {
                return t('customers.summary_unpaid_season', {
                    count: visibleUnpaidRows.value.length,
                    due: formatAmount(visibleUnpaidTotalDue.value),
                    season: tab.label,
                });
            }

            return t('customers.summary_unpaid_total', {
                count: state.unpaidRecords,
                due: formatAmount(state.unpaidTotalDue),
            });
        }

        if (state.pagination.total === 0) {
            return t('customers.summary_empty');
        }

        return t('customers.summary_total', { count: state.pagination.total });
    });

    const formTitle = computed(() => {
        if (state.editingId === null) {
            return t('customers.add_customer_button');
        }

        return t('customers.edit_customer', { id: state.editingId });
    });

    const searchPlaceholder = computed(() => {
        return state.unpaidMode
            ? t('customers.search_unpaid_placeholder')
            : t('customers.search_placeholder');
    });

    const customersStats = computed(() => ({
        total: state.pagination.total,
        active: state.rows.filter((customer) => customer.active).length,
        unpaid: state.unpaidRecords,
        due: formatAmount(state.unpaidTotalDue),
    }));

    function isEditing(): boolean {
        return state.editingId !== null;
    }

    function normalizeCustomerPayload(): CustomerCreatePayload {
        return {
            firstname: state.form.firstname,
            surname: state.form.surname || null,
            lastname: state.form.lastname,
            date_birth: state.form.date_birth || null,
            age: state.form.age,
            parent_name: state.form.parent_name || null,
            parent_phone: state.form.parent_phone || null,
            email: state.form.email || null,
            active: state.form.active ?? true,
        };
    }

    function resetForm(): void {
        state.editingId = null;
        state.form.firstname = '';
        state.form.surname = '';
        state.form.lastname = '';
        state.form.date_birth = '';
        state.form.age = null;
        state.form.parent_name = '';
        state.form.parent_phone = '';
        state.form.email = '';
        state.form.active = true;
    }

    function openCreateForm(): void {
        state.unpaidMode = false;
        resetForm();
        state.formOpen = true;
    }

    function closeForm(): void {
        resetForm();
        state.formOpen = false;
    }

    function formatAmount(value: number): string {
        if (!Number.isFinite(value)) {
            return '0.00';
        }

        return value.toFixed(2);
    }

    async function loadCustomers(): Promise<void> {
        state.loading = true;
        state.errors = [];

        try {
            const response = await listCustomers({
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
                'Failed to load customers.',
            );
        } finally {
            state.loading = false;
        }
    }

    async function loadUnpaidRows(): Promise<void> {
        state.loading = true;
        state.errors = [];

        try {
            const response = await listUnpaidCustomers({
                q: state.query || undefined,
            });

            state.unpaidRows = response.data;
            state.unpaidRecords = Number(
                response.meta.records ?? response.data.length,
            );
            state.unpaidTotalDue = Number(
                response.meta.total_due ??
                    response.data.reduce(
                        (sum, row) => sum + Number(row.due ?? 0),
                        0,
                    ),
            );

            const rawTabs = Array.isArray(response.meta.unpaid_tabs)
                ? response.meta.unpaid_tabs
                : [];
            state.unpaidTabs = rawTabs
                .map((tab): CustomerUnpaidTab | null => {
                    if (typeof tab !== 'object' || tab === null) {
                        return null;
                    }

                    const source = tab as Record<string, unknown>;
                    const id = String(source.id ?? '').trim();
                    const label = String(source.label ?? '').trim();

                    if (id === '' || label === '') {
                        return null;
                    }

                    return {
                        id,
                        label,
                        is_current: Boolean(source.is_current),
                        count: Number(source.count ?? 0),
                        due_total: Number(source.due_total ?? 0),
                    };
                })
                .filter((tab): tab is CustomerUnpaidTab => tab !== null);

            state.currentUnpaidSeasonLabel =
                typeof response.meta.current_unpaid_season_label === 'string'
                    ? response.meta.current_unpaid_season_label
                    : '';

            if (state.unpaidTabs.length > 0) {
                const selectedExists = state.unpaidTabs.some(
                    (tab) => tab.id === state.selectedUnpaidTabId,
                );
                if (!selectedExists) {
                    state.selectedUnpaidTabId = state.unpaidTabs[0].id;
                }
            } else {
                state.selectedUnpaidTabId = '';
            }
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                'Failed to load unpaid customers.',
            );
        } finally {
            state.loading = false;
        }
    }

    async function loadCustomerDetails(customerId: number): Promise<void> {
        state.detailsLoading = true;

        try {
            const [customerResponse, bookingsResponse] = await Promise.all([
                getCustomer(customerId),
                listBookings({
                    customer_id: customerId,
                    page: 1,
                    per_page: 10,
                }),
            ]);

            state.selectedCustomer = customerResponse.data;
            state.selectedCustomerBookings = bookingsResponse.data;
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                'Failed to load customer details.',
            );
        } finally {
            state.detailsLoading = false;
        }
    }

    function activateCustomersMode(): void {
        if (!state.unpaidMode) {
            return;
        }

        state.unpaidMode = false;
        state.formOpen = false;
        state.page = 1;
        state.unpaidRows = [];
        state.unpaidTabs = [];
        state.unpaidRecords = 0;
        state.unpaidTotalDue = 0;
        state.selectedUnpaidTabId = '';
        state.currentUnpaidSeasonLabel = '';

        void loadCustomers();
    }

    function activateUnpaidMode(): void {
        if (state.unpaidMode) {
            return;
        }

        state.unpaidMode = true;
        state.formOpen = false;
        state.selectedCustomer = null;
        state.selectedCustomerBookings = [];

        void loadUnpaidRows();
    }

    function selectUnpaidTab(tabId: string): void {
        state.selectedUnpaidTabId = tabId;
    }

    function openCustomerFromUnpaid(row: CustomerUnpaidRow): void {
        state.unpaidMode = false;
        state.formOpen = false;

        void Promise.all([
            loadCustomers(),
            loadCustomerDetails(row.customer_id),
        ]);
    }

    function startEdit(customer: Customer): void {
        state.editingId = customer.id;
        state.form.firstname = customer.firstname;
        state.form.surname = customer.surname ?? '';
        state.form.lastname = customer.lastname;
        state.form.date_birth = customer.date_birth ?? '';
        state.form.age = customer.age;
        state.form.parent_name = customer.parent_name ?? '';
        state.form.parent_phone = customer.parent_phone ?? '';
        state.form.email = customer.email ?? '';
        state.form.active = customer.active;
        state.formOpen = true;
    }

    async function submitCustomer(): Promise<void> {
        state.submitting = true;
        state.errors = [];

        try {
            if (state.editingId !== null) {
                const updated = await updateCustomer(
                    state.editingId,
                    normalizeCustomerPayload(),
                );
                toast.success(
                    'Customer Updated',
                    `Customer #${updated.data.id} updated successfully.`,
                );
            } else {
                const created = await createCustomer(
                    normalizeCustomerPayload(),
                );
                toast.success(
                    'Customer Created',
                    `Customer #${created.data.id} created successfully.`,
                );
            }

            await loadCustomers();
            closeForm();
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                'Failed to save customer.',
            );
        } finally {
            state.submitting = false;
        }
    }

    async function removeCustomer(customer: Customer): Promise<void> {
        if (
            !window.confirm(t('customers.confirm_delete', { id: customer.id }))
        ) {
            return;
        }

        state.deletingId = customer.id;
        state.errors = [];

        try {
            await deleteCustomer(customer.id);

            if (state.selectedCustomer?.id === customer.id) {
                state.selectedCustomer = null;
                state.selectedCustomerBookings = [];
            }

            if (state.editingId === customer.id) {
                resetForm();
            }

            toast.success(
                t('customers.customer_deleted'),
                t('customers.customer_deleted_message', { id: customer.id }),
            );
            await loadCustomers();
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                'Failed to delete customer.',
            );
        } finally {
            state.deletingId = null;
        }
    }

    async function applyUnpaidPayment(row: CustomerUnpaidRow): Promise<void> {
        if (state.applyingPaymentBookingId !== 0) return;

        const raw = (state.paymentDrafts[row.booking_id] ?? '').trim();
        const amount = Number.parseFloat(raw);

        if (!Number.isFinite(amount) || amount <= 0) {
            toast.error(
                t('bookings.payment'),
                t('bookings.error_payment_positive'),
            );

            return;
        }

        state.applyingPaymentBookingId = row.booking_id;

        try {
            const response = await applyBookingPayment(
                row.booking_id,
                amount,
                'increment',
            );
            const remainingEur = response.data.payment.remaining_eur;

            state.paymentDrafts[row.booking_id] = '';

            toast.success(
                t('bookings.payment_applied'),
                remainingEur !== null
                    ? t('bookings.payment_remaining', {
                          amount: remainingEur.toFixed(2),
                      })
                    : t('bookings.payment_applied'),
            );

            await loadUnpaidRows();
        } catch (error) {
            toast.error(
                t('bookings.payment_failed'),
                firstApiErrorMessage(error, t('bookings.payment_failed')),
            );
        } finally {
            state.applyingPaymentBookingId = 0;
        }
    }

    async function runSearch(): Promise<void> {
        if (state.unpaidMode) {
            await loadUnpaidRows();

            return;
        }

        await loadCustomers();
    }

    function goToPage(page: number): void {
        if (state.unpaidMode) {
            return;
        }

        state.page = page;
        void loadCustomers();
    }

    function deepLinkedCustomerQuery(): string | null {
        return readFirstDeepLinkParam(['customer', 'q']);
    }

    function isDeepLinkedUnpaidMode(): boolean {
        const raw = readFirstDeepLinkParam(['unpaid']);
        if (raw === null) {
            return false;
        }

        const normalized = raw.trim().toLowerCase();

        return (
            normalized === '1' || normalized === 'true' || normalized === 'yes'
        );
    }

    function parsePositiveInt(value: string | null): number | null {
        if (value === null) {
            return null;
        }

        const parsed = Number.parseInt(value, 10);

        return Number.isNaN(parsed) || parsed <= 0 ? null : parsed;
    }

    onMounted(async () => {
        const deepLink = deepLinkedCustomerQuery();
        const unpaidMode = isDeepLinkedUnpaidMode();

        if (deepLink !== null) {
            state.query = deepLink;
        }

        if (unpaidMode) {
            state.unpaidMode = true;
            await loadUnpaidRows();
            toast.info(
                t('customers.unpaid_fees'),
                t('customers.summary_unpaid_empty'),
            );

            return;
        }

        await loadCustomers();

        const deepLinkId = parsePositiveInt(deepLink);
        if (deepLinkId !== null) {
            await loadCustomerDetails(deepLinkId);
            toast.info(
                t('customers.notification_loaded'),
                t('customers.notification_loaded_detail', { id: deepLinkId }),
            );
        } else if (deepLink !== null) {
            toast.info(
                t('customers.notification_filter'),
                t('customers.notification_filter_detail', { id: deepLink }),
            );
        }
    });

    return {
        t,
        state,
        activeUnpaidTab,
        visibleUnpaidRows,
        visibleUnpaidTotalDue,
        summaryLabel,
        formTitle,
        searchPlaceholder,
        customersStats,
        isEditing,
        openCreateForm,
        closeForm,
        formatAmount,
        loadCustomerDetails,
        activateCustomersMode,
        activateUnpaidMode,
        selectUnpaidTab,
        openCustomerFromUnpaid,
        startEdit,
        submitCustomer,
        removeCustomer,
        applyUnpaidPayment,
        runSearch,
        goToPage,
    };
}
