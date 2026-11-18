<?php

declare(strict_types=1);

namespace App\Repositories\Contracts\Services;

use App\DTOs\Services\ServiceSlotData;
use App\DTOs\Services\ServiceSlotFilterData;
use App\Models\Service;
use App\Models\ServiceSlot;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ServiceSlotRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, \App\Models\ServiceSlot>
     */
    public function paginateByService(Service $service, ServiceSlotFilterData $filters): LengthAwarePaginator;

    public function create(Service $service, ServiceSlotData $data): ServiceSlot;

    public function update(ServiceSlot $slot, ServiceSlotData $data): ServiceSlot;

    public function delete(ServiceSlot $slot): void;
}
