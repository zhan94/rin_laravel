export const SUPPORTED_LOCALES = ['en', 'de', 'es'] as const;

export type SupportedLocale = (typeof SUPPORTED_LOCALES)[number];
export type LocalizedText = Partial<Record<SupportedLocale, string>>;

export const LOCALE_LABELS: Record<SupportedLocale, string> = {
    en: 'English',
    de: 'German',
    es: 'Spanish',
};

export function emptyTranslations(): Record<SupportedLocale, string> {
    return {
        en: '',
        de: '',
        es: '',
    };
}

export function normalizeTranslations(
    value?: LocalizedText | null,
): Record<SupportedLocale, string> {
    const base = emptyTranslations();

    if (value === null || value === undefined) {
        return base;
    }

    for (const locale of SUPPORTED_LOCALES) {
        base[locale] = value[locale] ?? '';
    }

    return base;
}
