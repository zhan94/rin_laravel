<script setup lang="ts">
import type { BookingLocationGroup } from '@/modules/bookings/bookingLocations';
import { useBookingPageContext } from '@/pages/crm/composables/bookingPageContext';
import type { ComponentPublicInstance } from 'vue';

defineProps<{
    groupedLocations: BookingLocationGroup[];
    selectedLocationLabel: string;
}>();

const {
    onLocationNativeChange,
    selectLocation,
    setLocationPickerElement,
    state,
    t,
    toggleLocationPicker,
} = useBookingPageContext();

function assignLocationPickerRef(
    ref: Element | ComponentPublicInstance | null,
): void {
    setLocationPickerElement(ref instanceof Element ? ref : null);
}
</script>

<template>
    <div v-if="state.form.service_id > 0" id="location_div" class="form-group">
        <label class="col-sm-2 control-label">{{
            t('bookings.location')
        }}</label>
        <div class="col-sm-10">
            <div
                :ref="assignLocationPickerRef"
                class="booking-location-picker"
                :class="{
                    'is-open': state.locationPickerOpen,
                    'is-empty': state.form.location_id <= 0,
                    'is-selected': state.form.location_id > 0,
                    'is-native-mobile': state.locationPickerNativeMobile,
                }"
            >
                <div class="booking-location-select-wrap">
                    <span
                        class="glyphicon glyphicon-map-marker booking-location-icon"
                        aria-hidden="true"
                    ></span>
                    <button
                        type="button"
                        class="booking-location-trigger"
                        :class="{
                            'is-empty': state.form.location_id <= 0,
                        }"
                        :disabled="
                            state.loadingSupport || state.locations.length === 0
                        "
                        @click="toggleLocationPicker"
                    >
                        <span>{{ selectedLocationLabel }}</span>
                    </button>

                    <div
                        v-show="
                            state.locationPickerOpen &&
                            !state.locationPickerNativeMobile
                        "
                        class="booking-location-dropdown"
                        role="listbox"
                    >
                        <template v-if="groupedLocations.length > 0">
                            <div
                                v-for="group in groupedLocations"
                                :key="`location-group-${group.key}`"
                                class="booking-location-group"
                            >
                                <div
                                    v-if="group.label !== ''"
                                    class="booking-location-group-title"
                                >
                                    {{ group.label }}
                                </div>
                                <div class="booking-location-group-options">
                                    <button
                                        v-for="location in group.options"
                                        :key="`location-option-${location.id}`"
                                        type="button"
                                        class="booking-location-option"
                                        :class="{
                                            'is-selected':
                                                location.id ===
                                                state.form.location_id,
                                        }"
                                        @click="selectLocation(location.id)"
                                    >
                                        <span
                                            class="booking-location-option-label"
                                        >
                                            {{ location.label }}
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </template>
                        <div v-else class="booking-location-empty">
                            {{ t('bookings.no_locations_available') }}
                        </div>
                    </div>

                    <select
                        v-model.number="state.form.location_id"
                        class="form-control booking-location-select"
                        :disabled="
                            state.loadingSupport || state.locations.length === 0
                        "
                        required
                        @change="onLocationNativeChange"
                    >
                        <option :value="0">
                            {{ t('bookings.location_placeholder') }}
                        </option>
                        <template
                            v-for="group in groupedLocations"
                            :key="`location-native-${group.key}`"
                        >
                            <optgroup
                                v-if="group.label !== ''"
                                :label="group.label"
                            >
                                <option
                                    v-for="location in group.options"
                                    :key="`location-native-option-${location.id}`"
                                    :value="location.id"
                                >
                                    {{ location.label }}
                                </option>
                            </optgroup>
                            <template v-else>
                                <option
                                    v-for="location in group.options"
                                    :key="`location-native-option-flat-${location.id}`"
                                    :value="location.id"
                                >
                                    {{ location.label }}
                                </option>
                            </template>
                        </template>
                    </select>
                </div>
            </div>
        </div>
    </div>
</template>
