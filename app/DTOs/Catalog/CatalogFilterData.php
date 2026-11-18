<?php

declare(strict_types=1);

namespace App\DTOs\Catalog;

final readonly class CatalogFilterData
{
    public function __construct(
        public ?string $search,
        public ?int $serviceId,
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

        $serviceId = isset($payload['service_id']) ? (int) $payload['service_id'] : null;
        if ($serviceId !== null && $serviceId <= 0) {
            $serviceId = null;
        }

        return new self(
            search: $search,
            serviceId: $serviceId,
            page: $page,
            perPage: $perPage,
        );
    }
}
