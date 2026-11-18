<?php

declare(strict_types=1);

namespace App\DTOs\Users;

final readonly class UserFilterData
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 25,
        public ?string $search = null,
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

        return new self(
            page: $page,
            perPage: $perPage,
            search: $search,
        );
    }
}
