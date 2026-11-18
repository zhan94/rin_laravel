<?php

declare(strict_types=1);

namespace App\DTOs\Services;

final readonly class ServiceSlotFilterData
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 25,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            page: max(1, (int) ($payload['page'] ?? 1)),
            perPage: min(max(1, (int) ($payload['per_page'] ?? 25)), 100),
        );
    }
}
