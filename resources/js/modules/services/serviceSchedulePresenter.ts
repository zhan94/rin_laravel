import type { BonusRecord } from '@/modules/bonus/types';
import { formatGermanDate } from '@/modules/calendar/dateDisplay';
import type { ServiceDefinition, ServiceSlot } from '@/modules/services/types';
import { withQuery } from '@/services/queryString';

export type ServicesTab = 'main' | 'previous' | 'bonus';
export type SlotSeasonGroup = 'winter' | 'other';
export type SlotStatus = 'active' | 'full' | 'past' | 'empty';

export interface SlotViewModel {
    id: number;
    serviceId: number;
    name: string;
    max: number;
    seasonTypeRaw: 'winter' | 'summer' | 'other';
    seasonGroup: SlotSeasonGroup;
    datesIso: string[];
    dateLabels: string[];
    dateSet: Set<string>;
    bookedDateSet: Set<string>;
    datesCount: number;
    firstDate: string | null;
    lastDate: string | null;
    status: SlotStatus;
    statusLabel: string;
    registeredCount: number;
    registeredLabel: string;
}

export interface SlotGroup {
    key: SlotSeasonGroup;
    label: string;
    rows: SlotViewModel[];
}

export interface ServicePanel {
    service: ServiceDefinition;
    title: string;
    slots: SlotViewModel[];
}

export interface BonusGroup {
    title: string;
    rows: BonusRecord[];
}

export interface SlotCalendarCell {
    key: string;
    iso: string | null;
    dayNumber: number | null;
    disabled: boolean;
    selected: boolean;
    today: boolean;
}

export interface SlotMonthCalendarCell {
    key: string;
    iso: string | null;
    dayNumber: number | null;
    isServiceDate: boolean;
    isBookedDate: boolean;
    isToday: boolean;
}

export type ServiceStatusLabels = Record<SlotStatus, string>;

export interface SlotGroupLabels {
    winter: string;
    other: string;
}

export function serviceLabelById(
    services: ServiceDefinition[],
    serviceId: number,
): string {
    const service = services.find((item) => item.id === serviceId);

    if (!service) {
        return `#${serviceId}`;
    }

    return service.title || `#${serviceId}`;
}

export function buildSlotViewModel(
    slot: ServiceSlot,
    todayIso: string,
    statusLabels: ServiceStatusLabels,
): SlotViewModel {
    const datesIso = slot.dates
        .map((row) => row.slot_date)
        .filter((date): date is string => date !== null)
        .sort();
    const bookedDatesIso = slot.dates
        .filter((row) => {
            if (row.slot_date === null) {
                return false;
            }

            if (row.has_bookings === true) {
                return true;
            }

            return Number(row.bookings_count ?? 0) > 0;
        })
        .map((row) => row.slot_date)
        .filter((date): date is string => date !== null);
    const dateSet = new Set(datesIso);
    const bookedDateSet = new Set(bookedDatesIso);

    const firstDate = slot.date_start ?? datesIso[0] ?? null;
    const lastDate = slot.date_end ?? datesIso[datesIso.length - 1] ?? null;

    const seasonTypeRaw: 'winter' | 'summer' | 'other' =
        slot.season_type === 'winter' || slot.season_type === 'summer'
            ? slot.season_type
            : 'other';

    const seasonGroup: SlotSeasonGroup =
        seasonTypeRaw === 'winter' ? 'winter' : 'other';

    const rawUnique = Number(
        (slot as ServiceSlot & { unique_count?: number }).unique_count ?? 0,
    );
    const registeredCount = Number.isFinite(rawUnique)
        ? Math.max(0, rawUnique)
        : 0;
    const isEmpty = datesIso.length === 0;
    const isPast = !isEmpty && lastDate !== null && lastDate < todayIso;
    const isFull = slot.max > 0 && registeredCount >= slot.max;

    let status: SlotStatus = 'active';

    if (isEmpty) {
        status = 'empty';
    } else if (isPast) {
        status = 'past';
    } else if (isFull) {
        status = 'full';
    }

    return {
        id: slot.id,
        serviceId: slot.service_id,
        name: slot.name,
        max: slot.max,
        seasonTypeRaw,
        seasonGroup,
        datesIso,
        dateLabels: datesIso.map((date) => formatGermanDate(date)),
        dateSet,
        bookedDateSet,
        datesCount: datesIso.length,
        firstDate,
        lastDate,
        status,
        statusLabel: statusLabels[status],
        registeredCount,
        registeredLabel: `${registeredCount}/${Math.max(slot.max, 0)}`,
    };
}

export function buildServicePanels(
    services: ServiceDefinition[],
    slotsByService: Record<number, ServiceSlot[]>,
    activeTab: ServicesTab,
    todayIso: string,
    statusLabels: ServiceStatusLabels,
): ServicePanel[] {
    if (activeTab === 'bonus') {
        return [];
    }

    return services
        .map((service): ServicePanel => {
            const slots = (slotsByService[service.id] ?? [])
                .map((slot) => buildSlotViewModel(slot, todayIso, statusLabels))
                .filter((slot) => {
                    if (activeTab === 'main') {
                        return slot.status !== 'past' || slot.datesCount === 0;
                    }

                    return slot.status === 'past' && slot.datesCount > 0;
                });

            return {
                service,
                title: service.title || `Service #${service.id}`,
                slots,
            };
        })
        .filter((panel) => panel.slots.length > 0);
}

export function buildBonusGroups(rows: BonusRecord[]): BonusGroup[] {
    const grouped = new Map<string, BonusGroup>();

    for (const row of rows) {
        const title = row.title || '—';

        if (!grouped.has(title)) {
            grouped.set(title, {
                title,
                rows: [],
            });
        }

        grouped.get(title)?.rows.push(row);
    }

    return Array.from(grouped.values());
}

export function groupSlotsBySeason(
    slots: SlotViewModel[],
    labels: SlotGroupLabels = {
        winter: 'Winter periods',
        other: 'Spring / Summer / Autumn',
    },
): SlotGroup[] {
    const winterRows = slots.filter((slot) => slot.seasonGroup === 'winter');
    const otherRows = slots.filter((slot) => slot.seasonGroup === 'other');

    const groups: SlotGroup[] = [];

    if (winterRows.length > 0) {
        groups.push({
            key: 'winter',
            label: labels.winter,
            rows: winterRows,
        });
    }

    if (otherRows.length > 0) {
        groups.push({
            key: 'other',
            label: labels.other,
            rows: otherRows,
        });
    }

    return groups;
}

export function slotStatusClass(status: SlotStatus): string {
    switch (status) {
        case 'full':
            return 'slot-status--full';
        case 'past':
            return 'slot-status--past';
        case 'empty':
            return 'slot-status--empty';
        default:
            return 'slot-status--active';
    }
}

export function reportsUrl(serviceId: number, slot: SlotViewModel): string {
    return withQuery('/crm/reports', {
        service_id: serviceId,
        from: slot.firstDate,
        to: slot.lastDate,
    });
}

export function reportsUrlForDate(serviceId: number, date: string): string {
    return withQuery('/crm/reports', {
        service_id: serviceId,
        from: date,
        to: date,
    });
}
