import { useToast } from '@/composables/useToast';
import { useUiI18n } from '@/composables/useUiI18n';
import {
    createLocation,
    deleteLocation,
    listLocations,
    updateLocation,
} from '@/modules/locations/locationService';
import type {
    LocationPayload,
    LocationRecord,
} from '@/modules/locations/types';
import { readFirstDeepLinkParam } from '@/modules/notifications/deepLinkQuery';
import { listServices } from '@/modules/services/serviceService';
import type { ServiceDefinition } from '@/modules/services/types';
import { normalizeApiErrors } from '@/services/apiErrors';
import type { ApiError } from '@/services/http';
import { computed, onMounted, reactive } from 'vue';

interface LocationGroup {
    serviceId: number;
    title: string;
    rows: LocationRecord[];
}

interface InlineLocationTimes {
    from: string;
    to: string;
}

export function useLocationsPage() {
    const { info } = useToast();
    const { t } = useUiI18n();

    const state = reactive({
        rows: [] as LocationRecord[],
        services: [] as ServiceDefinition[],
        loading: false,
        loadingServices: false,
        submitting: false,
        deletingId: null as number | null,
        inlineSaving: {} as Record<string, boolean>,
        inlineTimes: {} as Record<number, InlineLocationTimes>,
        errors: [] as ApiError[],
        query: '',
        formModalOpen: false,
        formMode: 'create' as 'create' | 'edit',
        editingId: null as number | null,
        form: {
            location_from: '',
            location_to: '',
            from_time: '08:00',
            to_time: '09:00',
            service_id: 0,
        },
    });

    const formModalTitle = computed(() =>
        state.formMode === 'create'
            ? t('locations.modal_add')
            : t('locations.modal_edit'),
    );

    const sortedRows = computed(() => sortLocations(state.rows));

    const mainLocations = computed(() =>
        sortedRows.value.filter((row) => row.service_id <= 0),
    );

    const customLocationGroups = computed<LocationGroup[]>(() => {
        const grouped = new Map<number, LocationRecord[]>();

        for (const row of sortedRows.value) {
            if (row.service_id <= 0) {
                continue;
            }

            const rows = grouped.get(row.service_id) ?? [];
            rows.push(row);
            grouped.set(row.service_id, rows);
        }

        return Array.from(grouped.entries())
            .map(([serviceId, rows]) => ({
                serviceId,
                title: serviceLabelById(serviceId),
                rows,
            }))
            .sort((left, right) => left.title.localeCompare(right.title));
    });

    const serviceTotal = computed(() => customLocationGroups.value.length);
    const mainLocationsTotal = computed(() => mainLocations.value.length);
    const customLocationsTotal = computed(() =>
        customLocationGroups.value.reduce(
            (total, group) => total + group.rows.length,
            0,
        ),
    );
    const locationsTotal = computed(
        () => mainLocationsTotal.value + customLocationsTotal.value,
    );

    function normalizeLocationTime(value: string): string {
        const match = /^(\d{1,2}):(\d{2})/.exec(value.trim());
        if (match === null) {
            return '00:00';
        }

        const hour = Math.min(23, Math.max(0, Number(match[1])));
        const minute = Math.min(59, Math.max(0, Number(match[2])));

        return `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}`;
    }

    function compareLocations(
        left: LocationRecord,
        right: LocationRecord,
    ): number {
        const timeCompare = normalizeLocationTime(left.from_time).localeCompare(
            normalizeLocationTime(right.from_time),
        );
        if (timeCompare !== 0) {
            return timeCompare;
        }

        const fromCompare = left.location_from.localeCompare(
            right.location_from,
        );
        if (fromCompare !== 0) {
            return fromCompare;
        }

        return left.id - right.id;
    }

    function sortLocations(rows: LocationRecord[]): LocationRecord[] {
        return [...rows].sort(compareLocations);
    }

    function serviceLabelById(serviceId: number): string {
        const service = state.services.find((item) => item.id === serviceId);

        if (!service) {
            return `Service #${serviceId}`;
        }

        return service.title || `Service #${serviceId}`;
    }

    function inlineSavingKey(locationId: number, field: 'from' | 'to'): string {
        return `${locationId}:${field}`;
    }

    function ensureInlineTimes(location: LocationRecord): InlineLocationTimes {
        if (!state.inlineTimes[location.id]) {
            state.inlineTimes[location.id] = {
                from: normalizeLocationTime(location.from_time),
                to: normalizeLocationTime(location.to_time),
            };
        }

        return state.inlineTimes[location.id];
    }

    function inlineTimeValue(
        location: LocationRecord,
        field: 'from' | 'to',
    ): string {
        const times = ensureInlineTimes(location);

        return field === 'from' ? times.from : times.to;
    }

    function onInlineTimeInput(
        location: LocationRecord,
        field: 'from' | 'to',
        event: Event,
    ): void {
        const target = event.target as HTMLInputElement | null;
        if (target === null) {
            return;
        }

        const normalized = normalizeLocationTime(target.value);
        const times = ensureInlineTimes(location);

        if (field === 'from') {
            times.from = normalized;
        } else {
            times.to = normalized;
        }
    }

    function isInlineSaving(locationId: number, field: 'from' | 'to'): boolean {
        return state.inlineSaving[inlineSavingKey(locationId, field)] === true;
    }

    function syncInlineTimesFromRows(): void {
        const next: Record<number, InlineLocationTimes> = {};

        for (const row of state.rows) {
            next[row.id] = {
                from: normalizeLocationTime(row.from_time),
                to: normalizeLocationTime(row.to_time),
            };
        }

        state.inlineTimes = next;
    }

    function resetLocationForm(): void {
        state.form.location_from = '';
        state.form.location_to = '';
        state.form.from_time = '08:00';
        state.form.to_time = '09:00';
        state.form.service_id = 0;
    }

    function openCreateModal(): void {
        state.formMode = 'create';
        state.editingId = null;
        state.errors = [];
        resetLocationForm();
        state.formModalOpen = true;
    }

    function openEditModal(location: LocationRecord): void {
        state.formMode = 'edit';
        state.editingId = location.id;
        state.errors = [];

        state.form.location_from = location.location_from;
        state.form.location_to = location.location_to;
        state.form.from_time = normalizeLocationTime(location.from_time);
        state.form.to_time = normalizeLocationTime(location.to_time);
        state.form.service_id = location.service_id;

        state.formModalOpen = true;
    }

    function closeFormModal(): void {
        state.formModalOpen = false;
    }

    async function loadServicesForForm(): Promise<void> {
        state.loadingServices = true;

        try {
            const response = await listServices({
                page: 1,
                per_page: 200,
                active: true,
            });
            state.services = response.data;
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                t('locations.error_load_services'),
            );
        } finally {
            state.loadingServices = false;
        }
    }

    async function loadLocationRows(): Promise<void> {
        state.loading = true;
        state.errors = [];

        try {
            const response = await listLocations({
                q: state.query || undefined,
                page: 1,
                per_page: 500,
            });

            state.rows = response.data;
            syncInlineTimesFromRows();
        } catch (error) {
            state.errors = normalizeApiErrors(error, t('locations.error_load'));
        } finally {
            state.loading = false;
        }
    }

    async function saveInlineTime(
        location: LocationRecord,
        field: 'from' | 'to',
    ): Promise<void> {
        const key = inlineSavingKey(location.id, field);

        if (state.inlineSaving[key]) {
            return;
        }

        const times = ensureInlineTimes(location);

        state.inlineSaving[key] = true;
        state.errors = [];

        try {
            const payload: LocationPayload = {
                location_from: location.location_from,
                location_to: location.location_to,
                from_time: normalizeLocationTime(times.from),
                to_time: normalizeLocationTime(times.to),
                service_id: location.service_id,
            };

            const response = await updateLocation(location.id, payload);

            state.rows = state.rows.map((row) =>
                row.id === location.id ? response.data : row,
            );
            syncInlineTimesFromRows();
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                t('locations.error_update_time'),
            );
        } finally {
            delete state.inlineSaving[key];
        }
    }

    async function submitLocation(): Promise<void> {
        state.submitting = true;
        state.errors = [];

        try {
            const payload: LocationPayload = {
                location_from: state.form.location_from,
                location_to: state.form.location_to,
                from_time: normalizeLocationTime(state.form.from_time),
                to_time: normalizeLocationTime(state.form.to_time),
                service_id: state.form.service_id,
            };

            if (state.formMode === 'edit' && state.editingId !== null) {
                await updateLocation(state.editingId, payload);
            } else {
                await createLocation(payload);
            }

            state.formModalOpen = false;
            state.editingId = null;
            resetLocationForm();

            await loadLocationRows();
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                state.formMode === 'edit'
                    ? t('locations.error_update')
                    : t('locations.error_create'),
            );
        } finally {
            state.submitting = false;
        }
    }

    async function removeLocation(locationId: number): Promise<void> {
        if (!window.confirm(t('locations.confirm_delete'))) {
            return;
        }

        state.deletingId = locationId;
        state.errors = [];

        try {
            await deleteLocation(locationId);
            await loadLocationRows();
        } catch (error) {
            state.errors = normalizeApiErrors(
                error,
                t('locations.error_delete'),
            );
        } finally {
            state.deletingId = null;
        }
    }

    function deepLinkedLocationQuery(): string | null {
        return readFirstDeepLinkParam(['location', 'q']);
    }

    onMounted(async () => {
        const deepLink = deepLinkedLocationQuery();
        if (deepLink !== null) {
            state.query = deepLink;
        }

        await loadServicesForForm();
        await loadLocationRows();

        if (deepLink !== null) {
            info(
                t('locations.deep_link_title'),
                t('locations.deep_link_body', { id: deepLink }),
            );
        }
    });

    return {
        t,
        state,
        formModalTitle,
        mainLocations,
        customLocationGroups,
        serviceTotal,
        mainLocationsTotal,
        customLocationsTotal,
        locationsTotal,
        serviceLabelById,
        inlineTimeValue,
        onInlineTimeInput,
        isInlineSaving,
        openCreateModal,
        openEditModal,
        closeFormModal,
        saveInlineTime,
        submitLocation,
        removeLocation,
    };
}
