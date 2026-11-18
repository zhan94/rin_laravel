<?php

declare(strict_types=1);

namespace App\DTOs\Logs;

final readonly class LogFilterData
{
    /**
     * @var list<int>
     */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public function __construct(
        public int $page,
        public int $perPage,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $page = max(1, (int) ($payload['page'] ?? 1));

        $perPageRaw = (int) ($payload['per_page'] ?? 25);
        $perPage = in_array($perPageRaw, self::PER_PAGE_OPTIONS, true)
            ? $perPageRaw
            : 25;

        return new self(
            page: $page,
            perPage: $perPage,
        );
    }
}
