<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Logs\LogFilterData;
use App\Enums\RoleKey;
use App\Exceptions\ApiException;
use App\Models\User;
use App\Repositories\Eloquent\Logs\LogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class LogService
{
    public function __construct(
        private LogRepository $repository,
    ) {}

    /**
     * @return LengthAwarePaginator<int, \App\Models\Log>
     */
    public function paginate(LogFilterData $filters, User $actor): LengthAwarePaginator
    {
        $excludedUserId = $this->resolveExcludedUserId($actor);

        return $this->repository->paginateVisible($filters, $excludedUserId);
    }

    public function deleteOne(int $logId, User $actor): void
    {
        $excludedUserId = $this->resolveExcludedUserId($actor);

        $deleted = $this->repository->deleteVisibleById($logId, $excludedUserId);
        if (! $deleted) {
            throw new ApiException(
                errorCode: 'log_not_found',
                message: 'Log record not found.',
                statusCode: 404,
            );
        }
    }

    /**
     * @param  list<int>  $ids
     */
    public function deleteSelected(array $ids, User $actor): int
    {
        $excludedUserId = $this->resolveExcludedUserId($actor);

        return $this->repository->deleteVisibleByIds($ids, $excludedUserId);
    }

    public function deleteAllVisible(User $actor): int
    {
        $excludedUserId = $this->resolveExcludedUserId($actor);

        return $this->repository->deleteAllVisible($excludedUserId);
    }

    private function resolveExcludedUserId(User $actor): int
    {
        return match ($actor->resolvedRoleKey()) {
            RoleKey::SUPER_ADMIN => 0,
            RoleKey::ADMIN => (int) $actor->id,
            default => throw new ApiException(
                errorCode: 'logs_forbidden',
                message: 'You are not allowed to access logs.',
                statusCode: 403,
            ),
        };
    }
}
