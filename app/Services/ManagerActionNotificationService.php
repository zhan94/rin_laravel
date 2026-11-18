<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RoleKey;
use App\Models\User;
use App\Models\UserNotification;

final class ManagerActionNotificationService
{
    public function notifyAdminsForManagerEntityAction(
        User $actor,
        string $moduleKey,
        string $actionKey,
        string $actionText,
        string $titleEntityLabel,
        string $messageEntityLabel,
        string $label,
        string $entityType = '',
        ?int $entityId = null,
        ?string $linkPath = null,
    ): void {
        $safeActionText = trim($actionText);
        $safeTitleEntityLabel = trim($titleEntityLabel);
        $safeMessageEntityLabel = trim($messageEntityLabel);
        $safeLabel = trim($label);

        if (
            $safeActionText === ''
            || $safeTitleEntityLabel === ''
            || $safeMessageEntityLabel === ''
            || $safeLabel === ''
        ) {
            return;
        }

        $this->notifyAdminsForManagerAction(
            actor: $actor,
            moduleKey: $moduleKey,
            actionKey: $actionKey,
            title: sprintf('%s %s: %s', $safeTitleEntityLabel, $safeActionText, $safeLabel),
            message: sprintf(
                '%s %s %s %s.',
                $this->actorLabel($actor),
                $safeActionText,
                $safeMessageEntityLabel,
                $safeLabel,
            ),
            entityType: $entityType,
            entityId: $entityId,
            linkPath: $linkPath,
        );
    }

    public function notifyAdminsForManagerAction(
        User $actor,
        string $moduleKey,
        string $actionKey,
        string $title,
        string $message,
        string $entityType = '',
        ?int $entityId = null,
        ?string $linkPath = null,
    ): void {
        $actorId = (int) $actor->id;
        if ($actorId <= 0 || ! $this->isManager($actor)) {
            return;
        }

        $safeModuleKey = trim($moduleKey);
        $safeActionKey = trim($actionKey);
        $safeTitle = trim($title);
        $safeMessage = trim($message);

        if ($safeModuleKey === '' || $safeActionKey === '' || $safeTitle === '' || $safeMessage === '') {
            return;
        }

        $recipientIds = $this->adminRecipientIds($actorId);
        if ($recipientIds === []) {
            return;
        }

        $safeEntityType = trim($entityType);
        $safeEntityId = $entityId !== null && $entityId > 0 ? $entityId : null;
        $safeLinkPath = trim((string) $linkPath);
        if ($safeLinkPath !== '' && ! str_starts_with($safeLinkPath, '/')) {
            $safeLinkPath = '';
        }

        foreach ($recipientIds as $recipientId) {
            UserNotification::query()->create([
                'user_id' => $recipientId,
                'actor_user_id' => $actorId,
                'notification_type' => 'manager.'.$safeModuleKey.'.'.$safeActionKey,
                'module_key' => $this->limit($safeModuleKey, 50),
                'action_key' => $this->limit($safeActionKey, 50),
                'entity_type' => $this->limit($safeEntityType, 50),
                'entity_id' => $safeEntityId,
                'title' => $this->limit($safeTitle, 190),
                'message' => $this->limit($safeMessage, 500),
                'link_path' => $this->limit($safeLinkPath, 255),
                'status' => 'unread',
                'priority' => 'normal',
                'delivery_channel' => 'in_app',
                'is_sent' => true,
                'sent_at' => now(),
            ]);
        }
    }

    public function actorLabel(User $actor): string
    {
        $label = $actor->displayLabel();
        if ($label !== '') {
            return $label;
        }

        return 'Manager';
    }

    /**
     * @return list<int>
     */
    private function adminRecipientIds(int $excludeUserId): array
    {
        $recipientIds = User::query()
            ->select('users.id')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->whereIn('roles.role_key', [RoleKey::SUPER_ADMIN->value, RoleKey::ADMIN->value])
            ->where('users.id', '<>', $excludeUserId)
            ->orderBy('users.id')
            ->pluck('users.id')
            ->map(static fn (mixed $value): int => (int) $value)
            ->filter(static fn (int $value): bool => $value > 0)
            ->values()
            ->all();

        /** @var list<int> $recipientIds */
        return $recipientIds;
    }

    private function isManager(User $actor): bool
    {
        return $actor->resolvedRoleKey() === RoleKey::MANAGER;
    }

    private function limit(string $value, int $maxLength): string
    {
        if ($value === '') {
            return '';
        }

        $length = max(0, $maxLength);

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length);
        }

        return substr($value, 0, $length);
    }
}
