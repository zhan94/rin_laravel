export interface LocationPickerDeviceSignals {
    coarsePointer: boolean;
    noHover: boolean;
    touchPoints: number;
    narrowViewport: boolean;
}

export function shouldUseNativeLocationPickerMode(
    signals: LocationPickerDeviceSignals,
): boolean {
    return (
        (signals.coarsePointer || signals.noHover || signals.touchPoints > 0) &&
        signals.narrowViewport
    );
}

export interface LocationPickerToggleInput {
    loadingSupport: boolean;
    locationsCount: number;
    nativeMobile: boolean;
}

export interface LocationPickerOutsideClickInput {
    isOpen: boolean;
    pickerElement: Pick<HTMLElement, 'contains'> | null;
    target: EventTarget | null;
}

export function readCurrentLocationPickerDeviceSignals(): LocationPickerDeviceSignals {
    if (
        typeof window === 'undefined' ||
        typeof window.matchMedia !== 'function'
    ) {
        return {
            coarsePointer: false,
            noHover: false,
            touchPoints: 0,
            narrowViewport: false,
        };
    }

    const touchPoints =
        typeof navigator === 'undefined'
            ? 0
            : Number(navigator.maxTouchPoints ?? 0);

    return {
        coarsePointer:
            window.matchMedia('(pointer: coarse)').matches ||
            window.matchMedia('(any-pointer: coarse)').matches,
        noHover:
            window.matchMedia('(hover: none)').matches ||
            window.matchMedia('(any-hover: none)').matches,
        touchPoints: Number.isFinite(touchPoints) ? touchPoints : 0,
        narrowViewport: window.matchMedia('(max-width: 900px)').matches,
    };
}

export function canToggleCustomLocationPicker({
    loadingSupport,
    locationsCount,
    nativeMobile,
}: LocationPickerToggleInput): boolean {
    return !loadingSupport && locationsCount > 0 && !nativeMobile;
}

export function parseNativeLocationId(value: string): number {
    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : 0;
}

export function shouldCloseLocationPickerOnOutsideClick({
    isOpen,
    pickerElement,
    target,
}: LocationPickerOutsideClickInput): boolean {
    if (
        !isOpen ||
        pickerElement === null ||
        typeof Node === 'undefined' ||
        !(target instanceof Node)
    ) {
        return false;
    }

    return !pickerElement.contains(target);
}
