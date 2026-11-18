<?php

declare(strict_types=1);

namespace App\DTOs\Bonus;

final readonly class BonusFilterData
{
    public function __construct(
        public ?string $search,
        public int $page,
        public int $perPage,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $page = max(1, (int) ($payload['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($payload['per_page'] ?? 25)));

        $search = isset($payload['q']) ? trim((string) $payload['q']) : null;
        if ($search === '') {
            $search = null;
        }

        return new self(
            search: $search,
            page: $page,
            perPage: $perPage,
        );
    }
}
