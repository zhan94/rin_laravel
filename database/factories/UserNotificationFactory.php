<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserNotification>
 */
class UserNotificationFactory extends Factory
{
    protected $model = UserNotification::class;

    /**
     * @return array<string,mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'actor_user_id' => null,
            'notification_type' => 'manager_action',
            'module_key' => 'booking',
            'action_key' => 'created',
            'entity_type' => 'booking',
            'entity_id' => null,
            'title' => fake()->sentence(4),
            'message' => fake()->sentence(8),
            'metadata' => null,
            'link_path' => '/crm/bookings',
            'status' => 'unread',
            'priority' => 'normal',
            'delivery_channel' => 'in_app',
            'is_sent' => true,
            'sent_at' => now(),
            'created_at' => now(),
            'read_at' => null,
            'deleted_at' => null,
        ];
    }
}
