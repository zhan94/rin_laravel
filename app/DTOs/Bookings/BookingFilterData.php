<?php

declare(strict_types=1);

namespace App\DTOs\Bookings;

final readonly class BookingFilterData
{
    public function __construct(
        public ?string $search,
        public int $page,
        public int $perPage,
        public ?int $serviceType,
        public ?int $serviceId,
        public ?int $customerId,
        public ?bool $active,
        public ?string $date,
    ) {}

    /**
     * @param  array<string,mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $page = max(1, (int) ($payload['page'] ?? 1));
        $perPage = (int) ($payload['per_page'] ?? 25);
        $perPage = min(100, max(1, $perPage));

        $search = isset($payload['q']) ? trim((string) $payload['q']) : null;
        if ($search === '') {
            $search = null;
        }

        $date = isset($payload['date']) ? trim((string) $payload['date']) : null;
        if ($date === '') {
            $date = null;
        }

        $active = null;
        if (array_key_exists('active', $payload)) {
            $active = filter_var($payload['active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        }

        $serviceType = self::normalizePositiveInt($payload['service_type'] ?? null);
        $serviceId = self::normalizePositiveInt($payload['service_id'] ?? null);
        $customerId = self::normalizePositiveInt($payload['customer_id'] ?? null);

        return new self(
            search: $search,
            page: $page,
            perPage: $perPage,
            serviceType: $serviceType,
            serviceId: $serviceId,
            customerId: $customerId,
            active: $active,
            date: $date,
        );
    }

    private static function normalizePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }
}
