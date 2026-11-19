import type { BonusRecord } from '@/modules/bonus/types';
import {
    buildBonusGroups,
    buildServicePanels,
    buildSlotViewModel,
    groupSlotsBySeason,
    reportsUrl,
    reportsUrlForDate,
    serviceLabelById,
    slotStatusClass,
    type ServiceStatusLabels,
} from '@/modules/services/serviceSchedulePresenter';
import type { ServiceDefinition, ServiceSlot } from '@/modules/services/types';
import { describe, expect, it } from 'vitest';

const statusLabels: ServiceStatusLabels = {
    active: 'Active',
    empty: 'Empty',
    full: 'Full',
    past: 'Past',
};

function makeSlot(overrides: Partial<ServiceSlot> = {}): ServiceSlot {
    return {
        id: 10,
        service_id: 3,
        name: 'Season 2025/2026',
        date_start: null,
        date_end: null,
        max: 2,
        season_type: 'winter',
        dates: [
            {
                id: 1,
                slot_id: 10,
                slot_date: '2026-02-01',
                is_active: true,
            },
        ],
        created_at: null,
        ...overrides,
    };
}

function makeService(
    overrides: Partial<ServiceDefinition> = {},
): ServiceDefinition {
    return {
        id: 3,
        title: 'Ski school',
        translations: { en: 'Ski school', de: '', es: '' },
        title_key: null,
        max: 20,
        active: true,
        slots_count: null,
        ...overrides,
    };
}

describe('serviceSchedulePresenter', () => {
    it('builds slot view models with dates, bookings, status and labels', () => {
        const slot = makeSlot({
            dates: [
                {
                    id: 1,
                    slot_id: 10,
                    slot_date: '2026-02-01',
                    is_active: true,
                    has_bookings: true,
                },
                {
                    id: 2,
                    slot_id: 10,
                    slot_date: '2026-02-02',
                    is_active: true,
                },
            ],
        });

        const view = buildSlotViewModel(slot, '2026-01-01', statusLabels);

        expect(view.datesIso).toEqual(['2026-02-01', '2026-02-02']);
        expect(view.dateLabels).toEqual(['01.02.2026', '02.02.2026']);
        expect(view.bookedDateSet.has('2026-02-01')).toBe(true);
        expect(view.status).toBe('active');
        expect(view.statusLabel).toBe('Active');
        expect(view.registeredLabel).toBe('0/2');
    });

    it('filters service panels by current and previous tabs', () => {
        const service = makeService();
        const futureSlot = makeSlot({ id: 11, date_end: '2026-02-01' });
        const pastSlot = makeSlot({ id: 12, date_end: '2025-02-01' });

        expect(
            buildServicePanels(
                [service],
                { 3: [futureSlot, pastSlot] },
                'main',
                '2026-01-01',
                statusLabels,
            )[0]?.slots.map((slot) => slot.id),
        ).toEqual([11]);

        expect(
            buildServicePanels(
                [service],
                { 3: [futureSlot, pastSlot] },
                'previous',
                '2026-01-01',
                statusLabels,
            )[0]?.slots.map((slot) => slot.id),
        ).toEqual([12]);
    });

    it('groups bonus rows and service slots for UI panels', () => {
        const bonusRows = [
            { id: 1, title: 'Camp' },
            { id: 2, title: 'Camp' },
            { id: 3, title: 'Transport' },
        ] as BonusRecord[];

        expect(buildBonusGroups(bonusRows)).toEqual([
            { title: 'Camp', rows: [bonusRows[0], bonusRows[1]] },
            { title: 'Transport', rows: [bonusRows[2]] },
        ]);

        const winter = buildSlotViewModel(
            makeSlot({ id: 1 }),
            '2026-01-01',
            statusLabels,
        );
        const other = buildSlotViewModel(
            makeSlot({ id: 2, season_type: 'summer' }),
            '2026-01-01',
            statusLabels,
        );

        expect(
            groupSlotsBySeason([winter, other], {
                winter: 'Winter periods',
                other: 'Other periods',
            }).map((group) => [group.key, group.label, group.rows.length]),
        ).toEqual([
            ['winter', 'Winter periods', 1],
            ['other', 'Other periods', 1],
        ]);
    });

    it('builds labels, report urls and status class names', () => {
        const slot = buildSlotViewModel(makeSlot(), '2026-01-01', statusLabels);

        expect(serviceLabelById([makeService()], 3)).toBe('Ski school');
        expect(serviceLabelById([], 3)).toBe('#3');
        expect(reportsUrl(3, slot)).toBe(
            '/crm/reports?service_id=3&from=2026-02-01&to=2026-02-01',
        );
        expect(reportsUrlForDate(3, '2026-02-02')).toBe(
            '/crm/reports?service_id=3&from=2026-02-02&to=2026-02-02',
        );
        expect(slotStatusClass('full')).toBe('slot-status--full');
    });
});
