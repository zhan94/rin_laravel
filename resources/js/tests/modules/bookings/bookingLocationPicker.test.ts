import {
    canToggleCustomLocationPicker,
    parseNativeLocationId,
    shouldCloseLocationPickerOnOutsideClick,
    shouldUseNativeLocationPickerMode,
} from '@/modules/bookings/bookingLocationPicker';
import { describe, expect, it } from 'vitest';

describe('bookingLocationPicker', () => {
    it('uses native picker for touch-style narrow screens', () => {
        expect(
            shouldUseNativeLocationPickerMode({
                coarsePointer: true,
                noHover: false,
                touchPoints: 0,
                narrowViewport: true,
            }),
        ).toBe(true);

        expect(
            shouldUseNativeLocationPickerMode({
                coarsePointer: false,
                noHover: false,
                touchPoints: 2,
                narrowViewport: true,
            }),
        ).toBe(true);
    });

    it('keeps custom picker for wide screens or non-touch devices', () => {
        expect(
            shouldUseNativeLocationPickerMode({
                coarsePointer: true,
                noHover: true,
                touchPoints: 2,
                narrowViewport: false,
            }),
        ).toBe(false);

        expect(
            shouldUseNativeLocationPickerMode({
                coarsePointer: false,
                noHover: false,
                touchPoints: 0,
                narrowViewport: true,
            }),
        ).toBe(false);
    });

    it('only toggles the custom picker when data and viewport mode allow it', () => {
        expect(
            canToggleCustomLocationPicker({
                loadingSupport: false,
                locationsCount: 1,
                nativeMobile: false,
            }),
        ).toBe(true);

        expect(
            canToggleCustomLocationPicker({
                loadingSupport: true,
                locationsCount: 1,
                nativeMobile: false,
            }),
        ).toBe(false);

        expect(
            canToggleCustomLocationPicker({
                loadingSupport: false,
                locationsCount: 0,
                nativeMobile: false,
            }),
        ).toBe(false);

        expect(
            canToggleCustomLocationPicker({
                loadingSupport: false,
                locationsCount: 1,
                nativeMobile: true,
            }),
        ).toBe(false);
    });

    it('parses native select values defensively', () => {
        expect(parseNativeLocationId('42')).toBe(42);
        expect(parseNativeLocationId('')).toBe(0);
        expect(parseNativeLocationId('abc')).toBe(0);
    });

    it('detects outside clicks for the custom picker', () => {
        const picker = document.createElement('div');
        const inside = document.createElement('button');
        const outside = document.createElement('button');
        picker.appendChild(inside);
        document.body.append(picker, outside);

        expect(
            shouldCloseLocationPickerOnOutsideClick({
                isOpen: true,
                pickerElement: picker,
                target: inside,
            }),
        ).toBe(false);

        expect(
            shouldCloseLocationPickerOnOutsideClick({
                isOpen: true,
                pickerElement: picker,
                target: outside,
            }),
        ).toBe(true);

        expect(
            shouldCloseLocationPickerOnOutsideClick({
                isOpen: false,
                pickerElement: picker,
                target: outside,
            }),
        ).toBe(false);
    });
});
