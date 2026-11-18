<?php

declare(strict_types=1);

namespace App\Support\Profiles;

final class ProfileInputNormalizer
{
    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function forRegistration(array $input): array
    {
        $firstname = self::stringValue($input, 'firstname');
        $lastname = self::stringValue($input, 'lastname');

        if ($firstname !== '' && $lastname !== '') {
            return $input;
        }

        $resolvedName = self::parseName(self::stringValue($input, 'name'));
        if ($resolvedName === null) {
            return $input;
        }

        if ($firstname === '' && $resolvedName['firstname'] !== '') {
            $input['firstname'] = $resolvedName['firstname'];
        }

        if ($lastname === '' && $resolvedName['lastname'] !== '') {
            $input['lastname'] = $resolvedName['lastname'];
        }

        if (! isset($input['surname']) && $resolvedName['surname'] !== null) {
            $input['surname'] = $resolvedName['surname'];
        }

        return $input;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function forProfileUpdate(array $input, string $fallbackUsername): array
    {
        $username = self::stringValue($input, 'username');
        $firstname = self::stringValue($input, 'firstname');
        $lastname = self::stringValue($input, 'lastname');

        if ($firstname !== '' && $lastname !== '') {
            if ($username === '' && $fallbackUsername !== '') {
                $input['username'] = $fallbackUsername;
            }

            return $input;
        }

        $resolvedName = self::parseName(self::stringValue($input, 'name'));
        if ($resolvedName === null) {
            return $input;
        }

        $input['username'] = $username !== ''
            ? $username
            : $fallbackUsername;
        $input['firstname'] = $firstname !== ''
            ? $firstname
            : $resolvedName['firstname'];
        $input['lastname'] = $lastname !== ''
            ? $lastname
            : $resolvedName['lastname'];
        $input['surname'] = self::hasFilledValue($input, 'surname')
            ? $input['surname']
            : $resolvedName['surname'];

        return $input;
    }

    /**
     * @return array{firstname: string, surname: string|null, lastname: string}|null
     */
    private static function parseName(string $name): ?array
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $parts = preg_split('/\s+/', $name) ?: [];

        $firstname = trim((string) array_shift($parts));
        $lastname = trim((string) array_pop($parts));

        if ($lastname === '') {
            $lastname = $firstname;
        }

        $surname = $parts !== []
            ? trim(implode(' ', $parts))
            : '';

        return [
            'firstname' => $firstname,
            'surname' => $surname !== '' ? $surname : null,
            'lastname' => $lastname,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private static function hasFilledValue(array $input, string $key): bool
    {
        if (! array_key_exists($key, $input)) {
            return false;
        }

        $value = $input[$key];

        if ($value === null) {
            return false;
        }

        return trim((string) $value) !== '';
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private static function stringValue(array $input, string $key): string
    {
        return trim((string) ($input[$key] ?? ''));
    }
}
