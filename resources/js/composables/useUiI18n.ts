import { formatGermanDateTime } from '@/modules/calendar/dateDisplay';
import { LOCALE_TAGS, WEEKDAY_SHORT_LABELS } from '@/modules/i18n/messages';
import {
    normalizeLocale,
    translateUiMessage,
    type TranslationParams,
} from '@/modules/i18n/translate';
import type { SupportedLocale } from '@/modules/localization';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export type { UiTranslationKey } from '@/modules/i18n/messages';

export function useUiI18n() {
    const page = usePage();

    const localeMeta = computed(() => {
        const meta = (page.props.locale ?? {}) as {
            current?: SupportedLocale;
            fallback?: SupportedLocale;
        };

        const fallback = normalizeLocale(meta.fallback, 'en');

        return {
            current: normalizeLocale(meta.current, fallback),
            fallback,
        };
    });

    const currentLocale = computed<SupportedLocale>(
        () => localeMeta.value.current,
    );
    const fallbackLocale = computed<SupportedLocale>(
        () => localeMeta.value.fallback,
    );
    const localeTag = computed<string>(() => LOCALE_TAGS[currentLocale.value]);
    const weekdayShortLabels = computed<string[]>(
        () => WEEKDAY_SHORT_LABELS[currentLocale.value],
    );

    function t(key: string, params: TranslationParams = {}): string {
        return translateUiMessage(
            key,
            currentLocale.value,
            fallbackLocale.value,
            params,
        );
    }

    function formatMonthYear(value: Date): string {
        return new Intl.DateTimeFormat(localeTag.value, {
            month: 'long',
            year: 'numeric',
        }).format(value);
    }

    function formatDateTime(value: string): string {
        return formatGermanDateTime(value, '');
    }

    return {
        currentLocale,
        fallbackLocale,
        localeTag,
        weekdayShortLabels,
        t,
        formatMonthYear,
        formatDateTime,
    };
}
