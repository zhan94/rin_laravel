<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Logs;

use App\DTOs\Logs\LogFilterData;
use App\Models\Log;
use App\Repositories\Contracts\Logs\LogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class LogRepository implements LogRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Log>
     */
    public function paginateVisible(LogFilterData $filters, int $excludedUserId): LengthAwarePaginator
    {
        return Log::query()
            ->with('userModel:id,username,firstname,surname,lastname')
            ->when($excludedUserId > 0, fn ($query) => $query->where('user', '<>', $excludedUserId))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    public function deleteVisibleById(int $logId, int $excludedUserId): bool
    {
        if ($logId <= 0) {
            return false;
        }

        $deleted = Log::query()
            ->whereKey($logId)
            ->when($excludedUserId > 0, fn ($query) => $query->where('user', '<>', $excludedUserId))
            ->delete();

        return $deleted > 0;
    }

    public function deleteVisibleByIds(array $ids, int $excludedUserId): int
    {
        $normalized = array_values(array_unique(array_filter(
            array_map(static fn (mixed $value): int => (int) $value, $ids),
            static fn (int $id): bool => $id > 0,
        )));

        if ($normalized === []) {
            return 0;
        }

        return Log::query()
            ->whereIn('id', $normalized)
            ->when($excludedUserId > 0, fn ($query) => $query->where('user', '<>', $excludedUserId))
            ->delete();
    }

    public function deleteAllVisible(int $excludedUserId): int
    {
        return Log::query()
            ->when($excludedUserId > 0, fn ($query) => $query->where('user', '<>', $excludedUserId))
            ->delete();
    }
}
