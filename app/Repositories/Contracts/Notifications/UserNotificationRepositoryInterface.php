<?php

declare(strict_types=1);

namespace App\Repositories\Contracts\Notifications;

use App\DTOs\Notifications\NotificationFilterData;
use App\Models\UserNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserNotificationRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, \App\Models\UserNotification>
     */
    public function paginateForUser(int $userId, NotificationFilterData $filters): LengthAwarePaginator;

    public function unreadCount(int $userId): int;

    public function findForUser(int $userId, int $notificationId): ?UserNotification;

    public function markRead(UserNotification $notification): UserNotification;

    public function markAllRead(int $userId): int;
}
