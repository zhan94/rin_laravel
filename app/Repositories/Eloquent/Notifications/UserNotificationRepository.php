<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Notifications;

use App\DTOs\Notifications\NotificationFilterData;
use App\Models\UserNotification;
use App\Repositories\Contracts\Notifications\UserNotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class UserNotificationRepository implements UserNotificationRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, UserNotification>
     */
    public function paginateForUser(int $userId, NotificationFilterData $filters): LengthAwarePaginator
    {
        return UserNotification::query()
            ->with('actor:id,username,firstname,surname,lastname')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->when($filters->status !== null, fn ($query) => $query->where('status', $filters->status))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    public function unreadCount(int $userId): int
    {
        return UserNotification::query()
            ->where('user_id', $userId)
            ->where('status', 'unread')
            ->whereNull('deleted_at')
            ->count();
    }

    public function findForUser(int $userId, int $notificationId): ?UserNotification
    {
        return UserNotification::query()
            ->where('user_id', $userId)
            ->whereKey($notificationId)
            ->whereNull('deleted_at')
            ->first();
    }

    public function markRead(UserNotification $notification): UserNotification
    {
        if ($notification->status !== 'read') {
            $notification->forceFill([
                'status' => 'read',
                'read_at' => now(),
            ]);
            $notification->save();
        }

        return $notification->refresh();
    }

    public function markAllRead(int $userId): int
    {
        return UserNotification::query()
            ->where('user_id', $userId)
            ->where('status', 'unread')
            ->whereNull('deleted_at')
            ->update([
                'status' => 'read',
                'read_at' => now(),
            ]);
    }
}
