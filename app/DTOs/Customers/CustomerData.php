<?php

declare(strict_types=1);

namespace App\DTOs\Customers;

final readonly class CustomerData
{
    public function __construct(
        public string $firstname,
        public ?string $surname,
        public string $lastname,
        public ?string $dateBirth,
        public ?int $age,
        public ?string $parentName,
        public ?string $parentPhone,
        public ?string $email,
        public bool $active,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            firstname: trim((string) ($payload['firstname'] ?? '')),
            surname: self::nullableString($payload['surname'] ?? null),
            lastname: trim((string) ($payload['lastname'] ?? '')),
            dateBirth: self::nullableString($payload['date_birth'] ?? null),
            age: isset($payload['age']) ? (int) $payload['age'] : null,
            parentName: self::nullableString($payload['parent_name'] ?? null),
            parentPhone: self::nullableString($payload['parent_phone'] ?? null),
            email: self::nullableString($payload['email'] ?? null),
            active: (bool) ($payload['active'] ?? true),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function toPersistence(): array
    {
        return [
            'firstname' => $this->firstname,
            'surname' => $this->surname,
            'lastname' => $this->lastname,
            'date_birth' => $this->dateBirth,
            'age' => $this->age,
            'parent_name' => $this->parentName,
            'parent_phone' => $this->parentPhone,
            'email' => $this->email,
            'active' => $this->active,
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
