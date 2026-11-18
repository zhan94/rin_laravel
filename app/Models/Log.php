<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Log extends Model
{
    /** @use HasFactory<\Database\Factories\LogFactory> */
    use HasFactory;

    protected $table = 'logs';

    public $timestamps = false;

    protected $fillable = [
        'user',
        'page',
        'ip',
        'location',
        'date',
    ];

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'user' => 'integer',
            'date' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function userModel(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user');
    }
}
