<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Logs\LogFilterData;
use App\Enums\RoleKey;
use App\Exceptions\ApiException;
use App\Models\User;
use App\Repositories\Contracts\Logs\LogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class LogService
{
    public function __construct(
        private LogRepositoryInterface $repository,
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
        if (! $this->canAccessLogs($actor)) {
            throw new ApiException(
                errorCode: 'logs_forbidden',
                message: 'You are not allowed to access logs.',
                statusCode: 403,
            );
        }

        if ($this->isSuperAdmin($actor)) {
            return 0;
        }

        return (int) $actor->id;
    }

    private function canAccessLogs(User $actor): bool
    {
        $roleKey = $actor->resolvedRoleKey();

        return in_array($roleKey, [RoleKey::SUPER_ADMIN, RoleKey::ADMIN], true);
    }

    private function isSuperAdmin(User $actor): bool
    {
        return $actor->resolvedRoleKey() === RoleKey::SUPER_ADMIN;
    }
}
