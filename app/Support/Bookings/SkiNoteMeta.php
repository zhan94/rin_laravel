<?php

declare(strict_types=1);

namespace App\Support\Bookings;

final readonly class SkiNoteMeta
{
    private const START = '[[SKI_META]]';

    private const END = '[[/SKI_META]]';

    public function __construct(
        public ?string $note,
        public string $shoeSize,
        public string $height,
        public string $skillId,
        public string $equipEnabled,
    ) {}

    public static function fromNote(mixed $rawNote): self
    {
        $note = self::nullableString($rawNote);

        if ($note === null) {
            return self::empty();
        }

        $pattern = '/\s*'
            .preg_quote(self::START, '/')
            .'(.*?)'
            .preg_quote(self::END, '/')
            .'\s*$/s';

        if (! preg_match($pattern, $note, $matches)) {
            return new self($note, '', '', '', '');
        }

        $cleanNote = preg_replace($pattern, '', $note, 1);
        $cleanNote = self::nullableString(is_string($cleanNote) ? $cleanNote : $note);

        $decoded = json_decode(trim((string) ($matches[1] ?? '')), true);
        if (! is_array($decoded)) {
            return new self($cleanNote, '', '', '', '');
        }

        return new self(
            note: $cleanNote,
            shoeSize: trim((string) ($decoded['shoe_size'] ?? '')),
            height: trim((string) ($decoded['height'] ?? '')),
            skillId: trim((string) ($decoded['skill_id'] ?? ($decoded['skill'] ?? ''))),
            equipEnabled: trim((string) ($decoded['equip_enabled'] ?? ($decoded['equip'] ?? ''))),
        );
    }

    /**
     * @param  array<string,string>  $meta
     */
    public static function append(?string $note, array $meta): ?string
    {
        $payload = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            return self::nullableString($note);
        }

        $metaBlock = self::START."\n".$payload."\n".self::END;
        $cleanNote = self::nullableString($note);

        if ($cleanNote === null) {
            return $metaBlock;
        }

        return rtrim($cleanNote)."\n\n".$metaBlock;
    }

    public static function empty(): self
    {
        return new self(null, '', '', '', '');
    }

    public function skillIdAsInt(): int
    {
        return (int) $this->skillId;
    }

    public function equipEnabledState(): int
    {
        if ($this->equipEnabled === '') {
            return -1;
        }

        $normalized = strtolower($this->equipEnabled);

        if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
            return 1;
        }

        if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
            return 0;
        }

        return -1;
    }

    /**
     * @return array{shoe_size:string,height:string,skill_id:string,equip_enabled:string}
     */
    public function toArray(): array
    {
        return [
            'shoe_size' => $this->shoeSize,
            'height' => $this->height,
            'skill_id' => $this->skillId,
            'equip_enabled' => $this->equipEnabled,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
