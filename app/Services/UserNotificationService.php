<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Notifications\NotificationFilterData;
use App\Exceptions\ApiException;
use App\Models\User;
use App\Models\UserNotification;
use App\Repositories\Eloquent\Notifications\UserNotificationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final readonly class UserNotificationService
{
    public function __construct(
        private UserNotificationRepository $repository,
    ) {}

    /**
     * @return array{paginator:LengthAwarePaginator<int, UserNotification>,unread_count:int}
     */
    public function paginate(User $user, NotificationFilterData $filters): array
    {
        $userId = (int) $user->id;

        return [
            'paginator' => $this->repository->paginateForUser($userId, $filters),
            'unread_count' => $this->repository->unreadCount($userId),
        ];
    }

    public function markRead(User $user, int $notificationId): UserNotification
    {
        $notification = $this->repository->findForUser((int) $user->id, $notificationId);
        if ($notification === null) {
            throw new ApiException(
                errorCode: 'notification_not_found',
                message: 'Notification not found.',
                statusCode: 404,
            );
        }

        return DB::transaction(
            fn (): UserNotification => $this->repository->markRead($notification),
        );
    }

    public function markAllRead(User $user): int
    {
        return DB::transaction(
            fn (): int => $this->repository->markAllRead((int) $user->id),
        );
    }
}
