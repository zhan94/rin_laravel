<script setup lang="ts">
import type {
    ServicePanel,
    SlotGroup,
    SlotMonthCalendarCell,
    SlotViewModel,
} from '@/pages/crm/composables/useServicesPage';
import { Pencil, Search, Trash2 } from 'lucide-vue-next';

type Translate = (key: string, params?: Record<string, unknown>) => string;

defineProps<{
    deletingSlotId: number | null;
    groupSlotsBySeason: (slots: SlotViewModel[]) => SlotGroup[];
    isCalendarOpen: (slotId: number) => boolean;
    loading: boolean;
    moveRowCalendarMonth: (slot: SlotViewModel, delta: number) => void;
    openEditSlotModal: (serviceId: number, slot: SlotViewModel) => void;
    openReportForSlotDate: (serviceId: number, date: string) => void;
    panels: ServicePanel[];
    removeSlot: (slotId: number) => void;
    reportsUrl: (serviceId: number, slot: SlotViewModel) => string;
    rowCalendarMonthLabel: (slot: SlotViewModel) => string;
    rowCalendarWeeks: (slot: SlotViewModel) => SlotMonthCalendarCell[][];
    slotStatusClass: (status: SlotViewModel['status']) => string;
    t: Translate;
    toggleCalendar: (slot: SlotViewModel) => void;
    weekdayLabels: string[];
}>();
</script>

<template>
    <section class="tab-panel is-active">
        <div v-if="loading" class="empty-state">
            {{ t('services.loading') }}
        </div>
        <div v-else-if="panels.length === 0" class="empty-state">
            {{ t('services.empty_tab') }}
        </div>
        <div v-else class="services-block">
            <article
                v-for="panel in panels"
                :key="panel.service.id"
                class="table-shell"
            >
                <div class="table-head">
                    <span class="badge badge--info">{{ panel.title }}</span>
                    <span class="filters-note">{{
                        t('services.note_dates_for_service')
                    }}</span>
                </div>

                <div class="table-wrap">
                    <table
                        class="table-hover table-bordered table-responsive customer-table table"
                    >
                        <thead>
                            <tr>
                                <th>{{ t('services.col_season') }}</th>
                                <th>{{ t('services.col_for_dates') }}</th>
                                <th>
                                    {{ t('services.col_registered_children') }}
                                </th>
                                <th>{{ t('services.col_status') }}</th>
                                <th
                                    class="action-col action-col--list text-center"
                                >
                                    {{ t('services.col_list') }}
                                </th>
                                <th
                                    class="action-col action-col--edit text-center"
                                >
                                    {{ t('services.col_edit') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <template
                                v-for="group in groupSlotsBySeason(panel.slots)"
                                :key="`${panel.service.id}-${group.key}`"
                            >
                                <tr
                                    class="season-group-row"
                                    :class="`season-group-row--${group.key}`"
                                >
                                    <td colspan="6">
                                        <span class="season-group-label">{{
                                            group.label
                                        }}</span>
                                    </td>
                                </tr>
                                <tr v-for="slot in group.rows" :key="slot.id">
                                    <td>
                                        <strong>{{ slot.name }}</strong>
                                    </td>
                                    <td>
                                        <div class="text-muted">
                                            {{
                                                slot.datesCount > 0
                                                    ? t(
                                                          'services.dates_count',
                                                          {
                                                              count: slot.datesCount,
                                                          },
                                                      )
                                                    : t('services.no_dates')
                                            }}
                                        </div>
                                        <button
                                            type="button"
                                            class="btn btn-outline btn-xs toggle-calendar"
                                            :class="{
                                                'is-open': isCalendarOpen(
                                                    slot.id,
                                                ),
                                            }"
                                            @click="toggleCalendar(slot)"
                                        >
                                            {{ t('services.calendar') }}
                                        </button>
                                        <div
                                            v-if="isCalendarOpen(slot.id)"
                                            class="slot-calendar"
                                        >
                                            <div
                                                v-if="
                                                    slot.dateLabels.length === 0
                                                "
                                                class="slot-calendar-empty"
                                            >
                                                {{ t('services.no_dates') }}
                                            </div>
                                            <div
                                                v-else
                                                class="ui-datepicker ui-widget ui-widget-content ui-helper-clearfix ui-corner-all"
                                            >
                                                <div
                                                    class="ui-datepicker-header ui-widget-header ui-helper-clearfix ui-corner-all"
                                                >
                                                    <button
                                                        type="button"
                                                        class="ui-datepicker-prev ui-corner-all"
                                                        @click="
                                                            moveRowCalendarMonth(
                                                                slot,
                                                                -1,
                                                            )
                                                        "
                                                    >
                                                        <span
                                                            class="ui-icon ui-icon-circle-triangle-w"
                                                            aria-hidden="true"
                                                            >{{
                                                                t(
                                                                    'common.previous',
                                                                )
                                                            }}</span
                                                        >
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="ui-datepicker-next ui-corner-all"
                                                        @click="
                                                            moveRowCalendarMonth(
                                                                slot,
                                                                1,
                                                            )
                                                        "
                                                    >
                                                        <span
                                                            class="ui-icon ui-icon-circle-triangle-e"
                                                            aria-hidden="true"
                                                            >{{
                                                                t('common.next')
                                                            }}</span
                                                        >
                                                    </button>
                                                    <div
                                                        class="ui-datepicker-title"
                                                    >
                                                        <span
                                                            class="ui-datepicker-month"
                                                        >
                                                            {{
                                                                rowCalendarMonthLabel(
                                                                    slot,
                                                                )
                                                            }}
                                                        </span>
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
                                                                :key="`slot-row-weekday-${slot.id}-${weekday}`"
                                                                scope="col"
                                                            >
                                                                <span>{{
                                                                    weekday
                                                                }}</span>
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr
                                                            v-for="(
                                                                week, weekIndex
                                                            ) in rowCalendarWeeks(
                                                                slot,
                                                            )"
                                                            :key="`slot-week-${slot.id}-${weekIndex}`"
                                                        >
                                                            <td
                                                                v-for="cell in week"
                                                                :key="cell.key"
                                                                :class="{
                                                                    'ui-datepicker-other-month':
                                                                        cell.iso ===
                                                                        null,
                                                                    'slot-date':
                                                                        cell.isServiceDate &&
                                                                        !cell.isBookedDate,
                                                                    'slot-date-booked':
                                                                        cell.isBookedDate,
                                                                    'slot-day--today':
                                                                        cell.isToday,
                                                                }"
                                                            >
                                                                <span
                                                                    v-if="
                                                                        cell.iso ===
                                                                        null
                                                                    "
                                                                    class="ui-state-default"
                                                                >
                                                                    &nbsp;
                                                                </span>
                                                                <span
                                                                    v-else-if="
                                                                        !cell.isServiceDate
                                                                    "
                                                                    class="ui-state-default"
                                                                >
                                                                    {{
                                                                        cell.dayNumber
                                                                    }}
                                                                </span>
                                                                <a
                                                                    v-else
                                                                    href="#"
                                                                    class="ui-state-default"
                                                                    :title="
                                                                        cell.isBookedDate
                                                                            ? t(
                                                                                  'services.calendar_title_booked',
                                                                              )
                                                                            : t(
                                                                                  'services.calendar_title_service',
                                                                              )
                                                                    "
                                                                    @click.prevent="
                                                                        openReportForSlotDate(
                                                                            panel
                                                                                .service
                                                                                .id,
                                                                            cell.iso,
                                                                        )
                                                                    "
                                                                >
                                                                    {{
                                                                        cell.dayNumber
                                                                    }}
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span
                                            class="pill"
                                            :class="
                                                slot.registeredCount >=
                                                    slot.max && slot.max > 0
                                                    ? 'pill--no'
                                                    : 'pill--yes'
                                            "
                                        >
                                            {{ slot.registeredLabel }}
                                        </span>
                                    </td>
                                    <td>
                                        <span
                                            class="slot-status"
                                            :class="
                                                slotStatusClass(slot.status)
                                            "
                                        >
                                            {{ slot.statusLabel }}
                                        </span>
                                    </td>
                                    <td
                                        class="action-cell action-cell--list text-center"
                                    >
                                        <a
                                            :href="
                                                reportsUrl(
                                                    panel.service.id,
                                                    slot,
                                                )
                                            "
                                            class="icon-btn icon-btn--success btn-action"
                                            :title="
                                                t(
                                                    'services.open_reports_period',
                                                )
                                            "
                                        >
                                            <Search class="h-3.5 w-3.5" />
                                        </a>
                                    </td>
                                    <td
                                        class="action-cell action-cell--edit text-center"
                                    >
                                        <button
                                            type="button"
                                            class="icon-btn icon-btn--primary btn-action"
                                            @click="
                                                openEditSlotModal(
                                                    panel.service.id,
                                                    slot,
                                                )
                                            "
                                        >
                                            <Pencil class="h-3.5 w-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            class="icon-btn icon-btn--danger btn-action"
                                            :disabled="
                                                deletingSlotId === slot.id
                                            "
                                            @click="removeSlot(slot.id)"
                                        >
                                            <Trash2
                                                v-if="
                                                    deletingSlotId !== slot.id
                                                "
                                                class="h-3.5 w-3.5"
                                            />
                                            <span v-else>...</span>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </article>
        </div>
    </section>
</template>
