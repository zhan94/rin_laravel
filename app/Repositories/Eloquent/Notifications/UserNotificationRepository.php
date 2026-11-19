<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent\Notifications;

use App\DTOs\Notifications\NotificationFilterData;
use App\Models\UserNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class UserNotificationRepository
{
    /**
     * @return LengthAwarePaginator<int, UserNotification>
     */
    public function paginateForUser(int $userId, NotificationFilterData $filters): LengthAwarePaginator
    {
        return $this->visibleTo($userId)
            ->with('actor:id,username,firstname,surname,lastname')
            ->when($filters->status !== null, fn ($query) => $query->where('status', $filters->status))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page);
    }

    public function unreadCount(int $userId): int
    {
        return $this->visibleTo($userId)
            ->where('status', 'unread')
            ->count();
    }

    public function findForUser(int $userId, int $notificationId): ?UserNotification
    {
        return $this->visibleTo($userId)->find($notificationId);
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
        return $this->visibleTo($userId)
            ->where('status', 'unread')
            ->update([
                'status' => 'read',
                'read_at' => now(),
            ]);
    }

    /** @return Builder<UserNotification> */
    private function visibleTo(int $userId): Builder
    {
        return UserNotification::query()
            ->where('user_id', $userId)
            ->whereNull('deleted_at');
    }
}
