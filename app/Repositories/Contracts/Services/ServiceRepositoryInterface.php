<?php

declare(strict_types=1);

namespace App\Repositories\Contracts\Services;

use App\DTOs\Services\ServiceData;
use App\DTOs\Services\ServiceFilterData;
use App\Models\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ServiceRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, \App\Models\Service>
     */
    public function paginate(ServiceFilterData $filters): LengthAwarePaginator;

    public function create(ServiceData $data): Service;

    public function update(Service $service, ServiceData $data): Service;

    public function deactivate(Service $service): Service;
}
