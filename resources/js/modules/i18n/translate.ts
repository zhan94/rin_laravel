import {
    SUPPORTED_LOCALES,
    type SupportedLocale,
} from '@/modules/localization';
import { UI_MESSAGES } from './messages';

export type TranslationParams = Record<string, unknown>;
type MessageMap = Partial<Record<string, string>>;

export function normalizeLocale(
    value: unknown,
    fallback: SupportedLocale,
): SupportedLocale {
    if (typeof value !== 'string') {
        return fallback;
    }

    const normalized = value.trim().toLowerCase() as SupportedLocale;

    return SUPPORTED_LOCALES.includes(normalized) ? normalized : fallback;
}

export function interpolate(
    template: string,
    params: TranslationParams = {},
): string {
    return Object.entries(params).reduce((message, [key, value]) => {
        return message.replaceAll(`:${key}`, String(value));
    }, template);
}

export function translateUiMessage(
    key: string,
    currentLocale: SupportedLocale,
    fallbackLocale: SupportedLocale,
    params: TranslationParams = {},
): string {
    const currentMessages = UI_MESSAGES[currentLocale] as MessageMap;
    const fallbackMessages = UI_MESSAGES[fallbackLocale] as MessageMap;
    const englishMessages = UI_MESSAGES.en as MessageMap;

    const template =
        currentMessages[key] ??
        fallbackMessages[key] ??
        englishMessages[key] ??
        key;

    return interpolate(template, params);
}
