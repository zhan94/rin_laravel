<?php

declare(strict_types=1);

namespace App\Repositories\Contracts\Logs;

use App\DTOs\Logs\LogFilterData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LogRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, \App\Models\Log>
     */
    public function paginateVisible(LogFilterData $filters, int $excludedUserId): LengthAwarePaginator;

    public function deleteVisibleById(int $logId, int $excludedUserId): bool;

    /**
     * @param  list<int>  $ids
     */
    public function deleteVisibleByIds(array $ids, int $excludedUserId): int;

    public function deleteAllVisible(int $excludedUserId): int;
}
