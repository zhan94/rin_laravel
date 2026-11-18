<?php

declare(strict_types=1);

namespace App\DTOs\Services;

use App\Support\Localization\LocalizedText;

final readonly class ServiceData
{
    public function __construct(
        public string $title,
        /** @var array<string,string> */
        public array $translations,
        public ?int $titleKey,
        public int $max,
        public bool $active,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $title = trim((string) ($payload['title'] ?? ''));
        $translations = LocalizedText::normalize(
            $payload['translations'] ?? null,
            $title,
        );

        return new self(
            title: LocalizedText::resolve($translations, 'en', $title),
            translations: $translations,
            titleKey: isset($payload['title_key']) ? (int) $payload['title_key'] : null,
            max: max(0, (int) ($payload['max'] ?? 0)),
            active: (bool) ($payload['active'] ?? true),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function toPersistence(): array
    {
        $fallbackTitle = LocalizedText::resolve($this->translations, 'en', $this->title);

        return [
            'title' => $fallbackTitle,
            'title_translations' => $this->translations,
            'title_key' => $this->titleKey,
            'max' => $this->max,
            'active' => $this->active,
        ];
    }
}
