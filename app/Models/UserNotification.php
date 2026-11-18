<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification extends Model
{
    /** @use HasFactory<\Database\Factories\UserNotificationFactory> */
    use HasFactory;

    protected $table = 'user_notifications';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'actor_user_id',
        'notification_type',
        'module_key',
        'action_key',
        'entity_type',
        'entity_id',
        'title',
        'message',
        'metadata',
        'link_path',
        'status',
        'priority',
        'delivery_channel',
        'is_sent',
        'sent_at',
        'created_at',
        'read_at',
        'deleted_at',
    ];

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'actor_user_id' => 'integer',
            'entity_id' => 'integer',
            'metadata' => 'array',
            'is_sent' => 'boolean',
            'sent_at' => 'datetime',
            'created_at' => 'datetime',
            'read_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
