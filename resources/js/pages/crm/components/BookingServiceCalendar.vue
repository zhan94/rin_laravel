<script setup lang="ts">
import type { CalendarCell } from '@/pages/crm/composables/useBookingsPage';

defineProps<{
    calendarMonthLabel: string;
    calendarWeeks: CalendarCell[][];
    disabledDatesCount: number;
    loadingSupport: boolean;
    selectedDateCount: number;
    t: (key: string, replacements?: Record<string, string | number>) => string;
    toggleCalendarDate: (cell: CalendarCell) => void;
    weekdayLabels: string[];
}>();

const emit = defineEmits<{
    moveMonth: [direction: number];
}>();
</script>

<template>
    <div id="datepicker_wrapper">
        <div id="dates_div" class="form-group">
            <label class="col-sm-3 control-label">{{
                t('bookings.service_period')
            }}</label>
            <div class="col-sm-3">
                <div class="booking-calendar-panel">
                    <div class="booking-calendar-topbar">
                        <div class="booking-calendar-copy">
                            <strong>{{
                                t('bookings.choose_service_days')
                            }}</strong>
                            <span>{{
                                t('bookings.choose_service_days_note')
                            }}</span>
                        </div>
                        <div class="booking-calendar-badges">
                            <span
                                class="booking-calendar-badge booking-calendar-badge--month"
                            >
                                {{ calendarMonthLabel }}
                            </span>
                            <span
                                class="booking-calendar-badge booking-calendar-badge--selected"
                            >
                                {{
                                    t('bookings.selected_count', {
                                        count: selectedDateCount,
                                    })
                                }}
                            </span>
                        </div>
                    </div>
                    <div
                        class="booking-datepicker ui-datepicker ui-widget ui-widget-content ui-helper-clearfix ui-corner-all"
                    >
                        <div
                            class="ui-datepicker-header ui-widget-header ui-helper-clearfix ui-corner-all"
                        >
                            <button
                                type="button"
                                class="ui-datepicker-prev ui-corner-all"
                                :disabled="loadingSupport"
                                @click="emit('moveMonth', -1)"
                            >
                                <span
                                    class="ui-icon ui-icon-circle-triangle-w"
                                    aria-hidden="true"
                                    >{{ t('common.previous') }}</span
                                >
                            </button>
                            <button
                                type="button"
                                class="ui-datepicker-next ui-corner-all"
                                :disabled="loadingSupport"
                                @click="emit('moveMonth', 1)"
                            >
                                <span
                                    class="ui-icon ui-icon-circle-triangle-e"
                                    aria-hidden="true"
                                    >{{ t('common.next') }}</span
                                >
                            </button>
                            <div class="ui-datepicker-title">
                                <span class="ui-datepicker-month">{{
                                    calendarMonthLabel
                                }}</span>
                            </div>
                        </div>

                        <table
                            class="ui-datepicker-calendar"
                            role="presentation"
                        >
                            <thead>
                                <tr>
                                    <th
                                        v-for="weekday in weekdayLabels"
                                        :key="`booking-calendar-weekday-${weekday}`"
                                        scope="col"
                                    >
                                        <span>{{ weekday }}</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(week, weekIndex) in calendarWeeks"
                                    :key="`booking-calendar-week-${weekIndex}`"
                                >
                                    <td
                                        v-for="cell in week"
                                        :key="cell.key"
                                        :class="{
                                            'ui-datepicker-other-month':
                                                cell.iso === null,
                                            'ui-datepicker-today': cell.today,
                                            'ui-datepicker-current-day':
                                                cell.selected,
                                            'ui-datepicker-unselectable ui-state-disabled':
                                                !cell.selectable,
                                        }"
                                    >
                                        <span
                                            v-if="cell.iso === null"
                                            class="ui-state-default"
                                        >
                                            &nbsp;
                                        </span>
                                        <span
                                            v-else-if="!cell.selectable"
                                            class="ui-state-default ui-state-disabled"
                                        >
                                            {{ cell.dayNumber }}
                                        </span>
                                        <a
                                            v-else
                                            href="#"
                                            class="ui-state-default"
                                            :class="{
                                                'ui-state-active':
                                                    cell.selected,
                                                'ui-state-highlight':
                                                    cell.today &&
                                                    !cell.selected,
                                            }"
                                            @click.prevent="
                                                toggleCalendarDate(cell)
                                            "
                                        >
                                            {{ cell.dayNumber }}
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="booking-calendar-legend">
                        <span class="booking-calendar-legend-item">
                            <span
                                class="booking-calendar-dot booking-calendar-dot--available"
                            ></span>
                            {{ t('bookings.legend_available') }}
                        </span>
                        <span class="booking-calendar-legend-item">
                            <span
                                class="booking-calendar-dot booking-calendar-dot--selected"
                            ></span>
                            {{ t('bookings.legend_selected') }}
                        </span>
                        <span class="booking-calendar-legend-item">
                            <span
                                class="booking-calendar-dot booking-calendar-dot--today"
                            ></span>
                            {{ t('bookings.legend_today') }}
                        </span>
                        <span
                            v-if="disabledDatesCount > 0"
                            class="booking-calendar-legend-item"
                        >
                            <span
                                class="booking-calendar-dot booking-calendar-dot--blocked"
                            ></span>
                            {{ t('bookings.legend_booked') }}
                        </span>
                    </div>
                </div>

                <p v-if="disabledDatesCount > 0" class="booking-note">
                    {{ t('bookings.disabled_dates_note') }}
                </p>
            </div>
        </div>
    </div>
</template>
