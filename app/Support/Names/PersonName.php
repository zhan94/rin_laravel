<?php

declare(strict_types=1);

namespace App\Support\Names;

final class PersonName
{
    /**
     * @param  iterable<mixed>  $parts
     */
    public static function fromParts(iterable $parts, string $fallback = ''): string
    {
        $resolved = [];

        foreach ($parts as $part) {
            $value = trim((string) $part);

            if ($value !== '') {
                $resolved[] = $value;
            }
        }

        if ($resolved !== []) {
            return implode(' ', $resolved);
        }

        return trim($fallback);
    }
}
