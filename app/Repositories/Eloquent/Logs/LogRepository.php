<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Logs;

use App\DTOs\Logs\LogFilterData;
use App\Models\Log;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class LogRepository
{
    /**
     * @return LengthAwarePaginator<int, Log>
     */
    public function paginateVisible(LogFilterData $filters, int $excludedUserId): LengthAwarePaginator
    {
        return $this->visibleQuery($excludedUserId)
            ->with('userModel:id,username,firstname,surname,lastname')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    public function deleteVisibleById(int $logId, int $excludedUserId): bool
    {
        if ($logId <= 0) {
            return false;
        }

        $deleted = $this->visibleQuery($excludedUserId)
            ->whereKey($logId)
            ->delete();

        return $deleted > 0;
    }

    /** @param list<int> $ids */
    public function deleteVisibleByIds(array $ids, int $excludedUserId): int
    {
        $normalized = array_values(array_unique(array_filter(
            array_map(static fn (mixed $value): int => (int) $value, $ids),
            static fn (int $id): bool => $id > 0,
        )));

        if ($normalized === []) {
            return 0;
        }

        return $this->visibleQuery($excludedUserId)
            ->whereIn('id', $normalized)
            ->delete();
    }

    public function deleteAllVisible(int $excludedUserId): int
    {
        return $this->visibleQuery($excludedUserId)->delete();
    }

    /** @return Builder<Log> */
    private function visibleQuery(int $excludedUserId): Builder
    {
        return Log::query()->when(
            $excludedUserId > 0,
            static fn (Builder $query): Builder => $query->where('user', '<>', $excludedUserId),
        );
    }
}
