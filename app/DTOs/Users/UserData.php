<?php

declare(strict_types=1);

namespace App\DTOs\Users;

use App\Support\Names\PersonName;

final readonly class UserData
{
    public function __construct(
        public string $username,
        public ?string $firstname,
        public ?string $surname,
        public ?string $lastname,
        public ?string $email,
        public ?string $password,
        public ?int $roleId,
        public ?bool $isActive,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            username: trim((string) ($payload['username'] ?? '')),
            firstname: self::nullableString($payload['firstname'] ?? null),
            surname: self::nullableString($payload['surname'] ?? null),
            lastname: self::nullableString($payload['lastname'] ?? null),
            email: self::nullableString($payload['email'] ?? null),
            password: isset($payload['password']) ? (string) $payload['password'] : null,
            roleId: isset($payload['role_id']) ? (int) $payload['role_id'] : null,
            isActive: isset($payload['is_active']) ? (bool) $payload['is_active'] : null,
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function toPersistence(bool $includePassword = true): array
    {
        $data = [
            'username' => $this->username,
            'firstname' => $this->firstname,
            'surname' => $this->surname,
            'lastname' => $this->lastname,
            'email' => $this->email,
            'role_id' => $this->roleId,
        ];

        if ($this->isActive !== null) {
            $data['is_active'] = $this->isActive;
        }

        if ($includePassword && $this->password !== null) {
            $data['password'] = $this->password;
        }

        return $data;
    }

    public function displayName(): string
    {
        return PersonName::fromParts([
            $this->firstname,
            $this->surname,
            $this->lastname,
        ], $this->username);
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
