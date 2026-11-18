<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Support\Localization\LocalizedText;

trait HasLocalizedTitle
{
    /**
     * @param  array<string,mixed>  $legacy
     * @return array<string,string>
     */
    public function displayTranslations(?string $fallbackTitle = null, array $legacy = []): array
    {
        return LocalizedText::normalize(
            $this->getAttribute('title_translations'),
            $fallbackTitle ?? $this->fallbackTitle(),
            $legacy,
        );
    }

    /**
     * @param  array<string,mixed>  $legacy
     */
    public function displayTitle(?string $locale = null, ?string $fallbackTitle = null, array $legacy = []): string
    {
        $resolvedFallback = $fallbackTitle ?? $this->fallbackTitle();

        return LocalizedText::resolve(
            $this->getAttribute('title_translations'),
            $locale,
            $resolvedFallback,
            $legacy,
        );
    }

    /**
     * @param  array<string,mixed>  $legacy
     */
    public function displayTitleOrId(?string $locale = null, ?string $fallbackTitle = null, array $legacy = []): string
    {
        $title = $this->displayTitle($locale, $fallbackTitle, $legacy);

        if ($title !== '') {
            return $title;
        }

        $key = $this->getKey();

        return is_scalar($key) && (string) $key !== ''
            ? '#'.(string) $key
            : '';
    }

    private function fallbackTitle(): string
    {
        return trim((string) ($this->getAttribute('title') ?? ''));
    }
}
