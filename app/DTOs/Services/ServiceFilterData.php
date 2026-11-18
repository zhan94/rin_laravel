<?php

declare(strict_types=1);

namespace App\DTOs\Services;

final readonly class ServiceFilterData
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 25,
        public ?string $search = null,
        public ?bool $active = null,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $page = max(1, (int) ($payload['page'] ?? 1));
        $perPage = min(max(1, (int) ($payload['per_page'] ?? 25)), 100);

        $search = null;
        if (isset($payload['q'])) {
            $candidate = trim((string) $payload['q']);
            $search = $candidate !== '' ? $candidate : null;
        }

        $active = null;
        if (array_key_exists('active', $payload) && $payload['active'] !== null && $payload['active'] !== '') {
            $active = filter_var($payload['active'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        }

        return new self(
            page: $page,
            perPage: $perPage,
            search: $search,
            active: $active,
        );
    }
}
