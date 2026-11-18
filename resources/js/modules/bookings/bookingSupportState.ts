import type {
    BookingSupportLocation,
    BookingSupportOption,
    BookingSupportSlot,
} from '@/modules/bookings/types';

export interface BookingSupportSelections {
    cardId: number;
    equipId: number;
    slotId: number;
    locationId: number;
}

export interface BookingSupportCollections {
    cards: BookingSupportOption[];
    equips: BookingSupportOption[];
    slots: BookingSupportSlot[];
    locations: BookingSupportLocation[];
}

export interface BookingSupportReconciliation {
    selections: BookingSupportSelections;
    shouldCloseLocationPicker: boolean;
}

export function reconcileBookingSupportSelections(
    collections: BookingSupportCollections,
    selections: BookingSupportSelections,
): BookingSupportReconciliation {
    return {
        selections: {
            cardId: keepExistingIdOrReset(collections.cards, selections.cardId),
            equipId: keepExistingIdOrReset(
                collections.equips,
                selections.equipId,
            ),
            slotId: keepExistingIdOrReset(collections.slots, selections.slotId),
            locationId: keepExistingIdOrReset(
                collections.locations,
                selections.locationId,
            ),
        },
        shouldCloseLocationPicker: collections.locations.length === 0,
    };
}

export function selectedSlotIdForAvailability(
    slotId: number,
): number | undefined {
    return slotId > 0 ? slotId : undefined;
}

function keepExistingIdOrReset<T extends { id: number }>(
    items: T[],
    selectedId: number,
): number {
    if (items.length === 0) {
        return 0;
    }

    return items.some((item) => item.id === selectedId) ? selectedId : 0;
}
