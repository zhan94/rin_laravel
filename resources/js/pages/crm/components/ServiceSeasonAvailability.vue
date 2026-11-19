<script setup lang="ts">
import {
    Dialog,
    DialogDescription,
    DialogHeader,
    DialogScrollContent,
    DialogTitle,
} from '@/components/ui/dialog';
import { useUiI18n } from '@/composables/useUiI18n';
import { parseIsoDate } from '@/modules/calendar/dateUtils';
import { reportsUrlForDate } from '@/modules/services/serviceSchedulePresenter';
import {
    listServiceSeasonBookings,
    listServiceSeasonDates,
} from '@/modules/services/serviceService';
import type {
    PaginationMeta,
    ServiceSeason,
    ServiceSeasonBooking,
    ServiceSeasonDate,
} from '@/modules/services/types';
import { normalizeApiErrors } from '@/services/apiErrors';
import { onMounted, ref, watch } from 'vue';

const props = defineProps<{ season: ServiceSeason; initialDate: string }>();
const emit = defineEmits<{ close: [] }>();
const { t, localeTag } = useUiI18n();

const filterDate = ref(props.initialDate);
const days = ref<ServiceSeasonDate[]>([]);
const selectedDay = ref<ServiceSeasonDate | null>(null);
const bookings = ref<ServiceSeasonBooking[]>([]);
const datePages = ref<PaginationMeta | null>(null);
const bookingPages = ref<PaginationMeta | null>(null);
const loadingDates = ref(true);
const loadingBookings = ref(false);
const dateError = ref('');
const bookingError = ref('');
const capacity = ref(props.season.capacity);
let datesRequest = 0;
let bookingsRequest = 0;

function formatDate(date: string | null): string {
    const parsed = date ? parseIsoDate(date) : null;
    return parsed?.toLocaleDateString(localeTag.value) ?? '—';
}

async function loadBookings(day: ServiceSeasonDate, page = 1): Promise<void> {
    const request = ++bookingsRequest;
    selectedDay.value = day;
    loadingBookings.value = true;
    bookingError.value = '';
    try {
        const { data, meta } = await listServiceSeasonBookings(
            props.season.id,
            day.id,
            page,
        );
        if (request !== bookingsRequest) return;
        bookings.value = data;
        bookingPages.value = meta.pagination as PaginationMeta;
    } catch (error) {
        if (request !== bookingsRequest) return;
        bookings.value = [];
        bookingPages.value = null;
        bookingError.value = normalizeApiErrors(
            error,
            t('services.bookings_load_error'),
        )[0].message;
    } finally {
        if (request === bookingsRequest) loadingBookings.value = false;
    }
}

async function loadDates(page = 1): Promise<void> {
    const request = ++datesRequest;
    ++bookingsRequest;
    selectedDay.value = null;
    loadingDates.value = true;
    dateError.value = '';
    try {
        const { data, meta } = await listServiceSeasonDates(props.season.id, {
            date: filterDate.value,
            page,
        });
        if (request !== datesRequest) return;
        days.value = data;
        datePages.value = meta.pagination as PaginationMeta;
        capacity.value = Number(meta.capacity);
        const day = data.find((date) => date.bookings_count > 0) ?? data[0];
        if (day) void loadBookings(day);
    } catch (error) {
        if (request !== datesRequest) return;
        days.value = [];
        datePages.value = null;
        dateError.value = normalizeApiErrors(
            error,
            t('services.dates_load_error'),
        )[0].message;
    } finally {
        if (request === datesRequest) loadingDates.value = false;
    }
}

watch(filterDate, () => void loadDates());
onMounted(() => void loadDates());
</script>

<template>
    <Dialog :open="true" @update:open="(open) => !open && emit('close')">
        <DialogScrollContent class="sm:max-w-4xl">
            <DialogHeader>
                <DialogTitle
                    >{{ season.service_name }} ·
                    {{ season.season_name }}</DialogTitle
                >
                <DialogDescription>
                    {{ formatDate(season.date_start) }} –
                    {{ formatDate(season.date_end) }} ·
                    {{ t('services.daily_capacity') }} {{ capacity }}
                </DialogDescription>
            </DialogHeader>

            <section class="grid gap-3" :aria-busy="loadingDates">
                <div class="flex flex-wrap items-end gap-3">
                    <label class="grid gap-1 text-sm font-medium">
                        {{ t('services.slot_date') }}
                        <input
                            v-model="filterDate"
                            type="date"
                            class="rounded-md border border-input bg-background px-3 py-2"
                        />
                    </label>
                    <button
                        v-if="filterDate"
                        type="button"
                        class="rounded-md border border-input px-3 py-2 text-sm"
                        @click="filterDate = ''"
                    >
                        {{ t('services.all_dates') }}
                    </button>
                </div>

                <p v-if="loadingDates" role="status">
                    {{ t('common.loading') }}
                </p>
                <p v-else-if="dateError" role="alert" class="text-destructive">
                    {{ dateError }}
                </p>
                <p
                    v-else-if="days.length === 0"
                    role="status"
                    class="text-muted-foreground"
                >
                    {{ t('services.no_scheduled_dates') }}
                </p>
                <div
                    v-else
                    class="overflow-x-auto rounded-lg border border-input"
                >
                    <table
                        class="w-full text-sm"
                        :aria-label="t('services.daily_availability')"
                    >
                        <thead class="bg-muted/60">
                            <tr>
                                <th scope="col" class="p-2 text-left">
                                    {{ t('services.slot_date') }}
                                </th>
                                <th scope="col" class="p-2 text-right">
                                    {{ t('services.booked_places') }}
                                </th>
                                <th scope="col" class="p-2 text-right">
                                    {{ t('services.remaining') }}
                                </th>
                                <th scope="col" class="p-2 text-left">
                                    {{ t('common.actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="day in days"
                                :key="day.id"
                                class="border-t border-input"
                                :class="{
                                    'bg-muted/60': selectedDay?.id === day.id,
                                }"
                            >
                                <td class="p-2 whitespace-nowrap">
                                    {{ formatDate(day.date) }}
                                    <span
                                        v-if="!day.active"
                                        class="block text-xs text-muted-foreground"
                                        >{{ t('services.inactive_date') }}</span
                                    >
                                </td>
                                <td class="p-2 text-right tabular-nums">
                                    {{ day.booked }}
                                </td>
                                <td class="p-2 text-right tabular-nums">
                                    {{ day.remaining }}
                                </td>
                                <td class="p-2">
                                    <button
                                        type="button"
                                        class="rounded-md border border-input px-2 py-1 font-medium"
                                        :aria-pressed="
                                            selectedDay?.id === day.id
                                        "
                                        :aria-label="`${t('services.view_bookings')} · ${formatDate(day.date)}`"
                                        @click="loadBookings(day)"
                                    >
                                        {{ t('services.view_bookings') }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="datePages && datePages.last_page > 1"
                    class="flex items-center justify-center gap-4 text-sm"
                >
                    <button
                        type="button"
                        class="rounded-md border border-input px-2 py-1"
                        :disabled="loadingDates || datePages.current_page <= 1"
                        @click="loadDates(datePages.current_page - 1)"
                    >
                        {{ t('common.previous') }}
                    </button>
                    <span>{{
                        t('common.page_of', {
                            current: datePages.current_page,
                            last: datePages.last_page,
                        })
                    }}</span>
                    <button
                        type="button"
                        class="rounded-md border border-input px-2 py-1"
                        :disabled="
                            loadingDates ||
                            datePages.current_page >= datePages.last_page
                        "
                        @click="loadDates(datePages.current_page + 1)"
                    >
                        {{ t('common.next') }}
                    </button>
                </div>
            </section>

            <section
                v-if="selectedDay"
                class="grid gap-2 border-t border-input pt-3"
                :aria-busy="loadingBookings"
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3 class="font-semibold">
                        {{ t('services.view_bookings') }} ·
                        {{ formatDate(selectedDay.date) }}
                    </h3>
                    <a
                        v-if="selectedDay.date"
                        :href="
                            reportsUrlForDate(
                                season.service_id,
                                selectedDay.date,
                            )
                        "
                        class="text-sm font-medium underline"
                    >
                        {{ t('common.reports') }}
                    </a>
                </div>
                <p v-if="loadingBookings" role="status">
                    {{ t('common.loading') }}
                </p>
                <p
                    v-else-if="bookingError"
                    role="alert"
                    class="text-destructive"
                >
                    {{ bookingError }}
                </p>
                <p
                    v-else-if="bookings.length === 0"
                    role="status"
                    class="text-muted-foreground"
                >
                    {{ t('services.no_date_bookings') }}
                </p>
                <ul
                    v-else
                    class="divide-y divide-border rounded-lg border border-input"
                >
                    <li
                        v-for="booking in bookings"
                        :key="booking.id"
                        class="flex flex-wrap items-center justify-between gap-2 px-3 py-2 text-sm"
                    >
                        <span>{{ booking.customer || `#${booking.id}` }}</span>
                        <span>{{
                            t(
                                booking.status === 'active'
                                    ? 'common.active'
                                    : 'bookings.cancelled',
                            )
                        }}</span>
                    </li>
                </ul>

                <div
                    v-if="bookingPages && bookingPages.last_page > 1"
                    class="flex items-center justify-center gap-4 text-sm"
                >
                    <button
                        type="button"
                        class="rounded-md border border-input px-2 py-1"
                        :disabled="
                            loadingBookings || bookingPages.current_page <= 1
                        "
                        @click="
                            loadBookings(
                                selectedDay,
                                bookingPages.current_page - 1,
                            )
                        "
                    >
                        {{ t('common.previous') }}
                    </button>
                    <span>{{
                        t('common.page_of', {
                            current: bookingPages.current_page,
                            last: bookingPages.last_page,
                        })
                    }}</span>
                    <button
                        type="button"
                        class="rounded-md border border-input px-2 py-1"
                        :disabled="
                            loadingBookings ||
                            bookingPages.current_page >= bookingPages.last_page
                        "
                        @click="
                            loadBookings(
                                selectedDay,
                                bookingPages.current_page + 1,
                            )
                        "
                    >
                        {{ t('common.next') }}
                    </button>
                </div>
            </section>
        </DialogScrollContent>
    </Dialog>
</template>
