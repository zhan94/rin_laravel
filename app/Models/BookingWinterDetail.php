<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingWinterDetail extends Model
{
    /** @use HasFactory<\Database\Factories\BookingWinterDetailFactory> */
    use HasFactory;

    protected $table = 'booking_winter_details';

    protected $primaryKey = 'booking_id';

    public $incrementing = false;

    protected $fillable = [
        'booking_id',
        'skill_id',
        'equip_id',
        'equip_enabled',
        'card_id',
        'shoe_size',
        'height',
    ];

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'booking_id' => 'integer',
            'skill_id' => 'integer',
            'equip_id' => 'integer',
            'equip_enabled' => 'boolean',
            'card_id' => 'integer',
            'shoe_size' => 'integer',
            'height' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}
