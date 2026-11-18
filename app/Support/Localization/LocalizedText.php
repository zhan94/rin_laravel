<?php

declare(strict_types=1);

namespace App\Support\Localization;

final class LocalizedText
{
    /**
     * @return list<string>
     */
    public static function supportedLocales(): array
    {
        $configured = config('localization.supported_locales', []);
        $locales = array_values(array_filter(
            array_map(static fn (mixed $locale): string => strtolower(trim((string) $locale)), is_array($configured) ? $configured : []),
            static fn (string $locale): bool => $locale !== '',
        ));

        if ($locales === []) {
            $fallback = strtolower(trim((string) config('app.fallback_locale', 'en')));

            return [$fallback !== '' ? $fallback : 'en'];
        }

        return array_values(array_unique($locales));
    }

    public static function fallbackLocale(): string
    {
        $fallback = strtolower(trim((string) config('app.fallback_locale', 'en')));
        $supported = self::supportedLocales();

        return in_array($fallback, $supported, true)
            ? $fallback
            : $supported[0];
    }

    public static function currentLocale(?string $preferred = null): string
    {
        $supported = self::supportedLocales();
        $preferred = strtolower(trim((string) ($preferred ?? app()->getLocale())));

        return in_array($preferred, $supported, true)
            ? $preferred
            : self::fallbackLocale();
    }

    /**
     * @param  array<string,mixed>|string|null  $translations
     * @param  array<string,mixed>  $legacy
     * @return array<string,string>
     */
    public static function normalize(array|string|null $translations, ?string $fallbackTitle = null, array $legacy = []): array
    {
        $candidate = $translations;

        if (is_string($candidate)) {
            $decoded = json_decode($candidate, true);
            $candidate = is_array($decoded) ? $decoded : [];
        }

        $normalized = [];
        $supported = self::supportedLocales();

        foreach ($supported as $locale) {
            $value = trim((string) ((is_array($candidate) ? ($candidate[$locale] ?? null) : null) ?? ''));

            if ($value !== '') {
                $normalized[$locale] = $value;
            }
        }

        foreach ($legacy as $locale => $value) {
            $key = strtolower(trim((string) $locale));

            if (! in_array($key, $supported, true) || array_key_exists($key, $normalized)) {
                continue;
            }

            $stringValue = trim((string) $value);
            if ($stringValue !== '') {
                $normalized[$key] = $stringValue;
            }
        }

        $fallbackTitle = trim((string) ($fallbackTitle ?? ''));

        if ($normalized === [] && $fallbackTitle !== '') {
            $normalized[self::fallbackLocale()] = $fallbackTitle;
        }

        if ($normalized !== [] && ! array_key_exists(self::fallbackLocale(), $normalized)) {
            $normalized[self::fallbackLocale()] = reset($normalized) ?: '';
        }

        return array_filter(
            $normalized,
            static fn (string $value): bool => trim($value) !== '',
        );
    }

    /**
     * @param  array<string,mixed>|string|null  $translations
     * @param  array<string,mixed>  $legacy
     */
    public static function resolve(
        array|string|null $translations,
        ?string $locale = null,
        ?string $fallbackTitle = null,
        array $legacy = [],
    ): string {
        $normalized = self::normalize($translations, $fallbackTitle, $legacy);

        if ($normalized === []) {
            return trim((string) ($fallbackTitle ?? ''));
        }

        $requestedLocale = self::currentLocale($locale);
        $fallbackLocale = self::fallbackLocale();

        $candidates = array_values(array_unique(array_filter([
            $requestedLocale,
            $fallbackLocale,
            ...self::supportedLocales(),
        ], static fn (string $value): bool => trim($value) !== '')));

        foreach ($candidates as $candidate) {
            $value = trim((string) ($normalized[$candidate] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return trim((string) (reset($normalized) ?: $fallbackTitle ?: ''));
    }
}
