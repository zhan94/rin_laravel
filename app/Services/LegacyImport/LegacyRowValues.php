<?php

declare(strict_types=1);

namespace App\Services\LegacyImport;

use DomainException;
use JsonException;
use stdClass;

final class LegacyRowValues
{
    /**
     * @param  array<string,mixed>  $row
     * @param  array<string,array<string,mixed>>  $columns
     * @return array<string,mixed>
     */
    public function normalize(array $row, array $columns): array
    {
        foreach ($row as $name => &$value) {
            $column = $columns[$name] ?? throw new DomainException($name.' is missing from the destination schema');
            if ($value === null) {
                if (! $column['nullable']) {
                    throw new DomainException($name.' is required');
                }

                continue;
            }

            $type = $column['type_name'];
            $definition = $column['type'];
            $value = match (true) {
                in_array($type, ['bigint', 'int', 'integer', 'smallint', 'mediumint', 'tinyint', 'boolean'], true) => $this->integer($value, $type, str_contains($definition, 'unsigned'), $name),
                in_array($type, ['decimal', 'numeric'], true) => $this->decimal($value, $definition, $name),
                in_array($type, ['date', 'timestamp', 'datetime'], true) => $this->temporal($value, $type, $name),
                default => $this->text($value, $definition, $name),
            };
        }

        return $row;
    }

    private function integer(mixed $value, string $type, bool $unsigned, string $name): string
    {
        $number = $this->decimal($value, 'decimal(20,0)', $name);
        [$min, $max] = match ($type) {
            'boolean' => ['0', '1'],
            'tinyint' => $unsigned ? ['0', '255'] : ['-128', '127'],
            'smallint' => $unsigned ? ['0', '65535'] : ['-32768', '32767'],
            'mediumint' => $unsigned ? ['0', '16777215'] : ['-8388608', '8388607'],
            'int' => $unsigned ? ['0', '4294967295'] : ['-2147483648', '2147483647'],
            default => $unsigned ? ['0', '18446744073709551615'] : ['-9223372036854775808', '9223372036854775807'],
        };

        // PHP compares numeric strings as numbers, losing precision near BIGINT limits.
        $compare = static function (string $left, string $right): int {
            $negative = str_starts_with($left, '-');
            if ($negative !== str_starts_with($right, '-')) {
                return $negative ? -1 : 1;
            }

            return (strlen($left) <=> strlen($right) ?: strcmp($left, $right)) * ($negative ? -1 : 1);
        };

        if ($compare($number, $min) < 0 || $compare($number, $max) > 0) {
            throw new DomainException($name.' exceeds the destination integer range');
        }

        return $number;
    }

    private function decimal(mixed $value, string $type, string $name): string
    {
        if (preg_match('/^(-?)([0-9]+)(?:\.([0-9]+))?$/D', (string) $value, $parts) !== 1) {
            throw new DomainException($name.' must be an exact number');
        }

        $whole = ltrim($parts[2], '0');
        $fraction = rtrim($parts[3] ?? '', '0');
        if (preg_match('/\((\d+),(\d+)\)/', $type, $size) === 1
            && (strlen($whole) > ((int) $size[1] - (int) $size[2]) || strlen($fraction) > (int) $size[2])) {
            throw new DomainException($name.' would lose precision or exceed the destination range');
        }

        $sign = $whole === '' && $fraction === '' ? '' : $parts[1];

        return $sign.($whole === '' ? '0' : $whole).($fraction === '' ? '' : '.'.$fraction);
    }

    private function temporal(mixed $value, string $type, string $name): string
    {
        $format = $type === 'date' ? 'Y-m-d' : 'Y-m-d H:i:s';
        $date = \DateTimeImmutable::createFromFormat('!'.$format, (string) $value, new \DateTimeZone('UTC'));
        if ($date === false || $date->format($format) !== (string) $value || (int) $date->format('Y') < 1000) {
            throw new DomainException($name.' has an invalid date or timestamp');
        }

        return (string) $value;
    }

    private function text(mixed $value, string $type, string $name): string
    {
        $text = (string) $value;
        if (! mb_check_encoding($text, 'UTF-8')) {
            throw new DomainException($name.' contains invalid UTF-8');
        }

        if (preg_match('/^(?:var)?char\((\d+)\)/', $type, $size) === 1 && mb_strlen($text, 'UTF-8') > (int) $size[1]) {
            throw new DomainException($name.' would exceed the destination character limit');
        }

        if (str_starts_with($type, 'enum(') && ! in_array($text, str_getcsv(substr($type, 5, -1), ',', "'", ''), true)) {
            throw new DomainException($name.' contains an unsupported value');
        }

        if ($type === 'json' || in_array($name, ['title_translations', 'metadata', 'result_json'], true)) {
            try {
                json_decode($text, flags: JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw new DomainException($name.' is invalid JSON');
            }
        }

        return $text;
    }

    /**
     * @param  array<string,mixed>  $left
     * @param  array<string,mixed>  $right
     */
    public function identical(array $left, array $right): bool
    {
        foreach ($left as $key => $value) {
            $other = $right[$key] ?? null;
            if ($value === $other) {
                continue;
            }
            if ($value === null || $other === null || ! in_array($key, ['title_translations', 'metadata', 'result_json'], true)
                || $this->sortedJson($value) !== $this->sortedJson($other)) {
                return false;
            }
        }

        return true;
    }

    private function sortedJson(string $value): string
    {
        $sort = static function (mixed $node) use (&$sort): mixed {
            if ($node instanceof stdClass) {
                $fields = (array) $node;
                ksort($fields, SORT_STRING);

                return (object) array_map($sort, $fields);
            }

            return is_array($node) ? array_map($sort, $node) : $node;
        };

        // The representation is used for equality only. Insert the original JSON.
        return json_encode($sort(json_decode($value, flags: JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING)), JSON_THROW_ON_ERROR);
    }
}
