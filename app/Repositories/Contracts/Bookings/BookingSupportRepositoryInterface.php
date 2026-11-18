<?php

declare(strict_types=1);

namespace App\Repositories\Contracts\Bookings;

use App\Models\Customer;

interface BookingSupportRepositoryInterface
{
    /**
     * @return list<array<string,mixed>>
     */
    public function searchCustomers(string $query, int $limit = 15): array;

    public function findCustomer(int $customerId): ?Customer;

    /**
     * @return list<array<string,mixed>>
     */
    public function listCardsByService(int $serviceId): array;

    /**
     * @return list<array<string,mixed>>
     */
    public function listEquipsByService(int $serviceId): array;

    /**
     * @return list<array<string,mixed>>
     */
    public function listServiceSlots(int $serviceId): array;

    /**
     * @return list<array<string,mixed>>
     */
    public function listServiceDates(int $serviceId, int $slotId = 0): array;

    /**
     * @return list<array<string,mixed>>
     */
    public function listLocationsByService(int $serviceId): array;

    /**
     * @return list<string>
     */
    public function listCustomerBookedDates(int $serviceId, int $customerId, int $slotId = 0): array;

    /**
     * @return array<string,mixed>|null
     */
    public function findBonusContext(int $bonusId): ?array;

    /**
     * @return array<string,mixed>|null
     */
    public function findCustomerServicePrefill(
        int $customerId,
        int $serviceId,
        string $seasonStart,
        string $seasonEnd,
    ): ?array;
}
