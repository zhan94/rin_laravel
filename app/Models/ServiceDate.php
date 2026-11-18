<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceDate extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceDateFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'slot_id',
        'slot_date',
        'is_active',
    ];

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'slot_id' => 'integer',
            'slot_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<ServiceSlot, $this>
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(ServiceSlot::class, 'slot_id');
    }

    /**
     * @return HasMany<BookingDate, $this>
     */
    public function bookingDates(): HasMany
    {
        return $this->hasMany(BookingDate::class, 'service_date_id');
    }
}
