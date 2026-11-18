<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingDate extends Model
{
    /** @use HasFactory<\Database\Factories\BookingDateFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'booking_id',
        'service_date_id',
    ];

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'booking_id' => 'integer',
            'service_date_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    /**
     * @return BelongsTo<ServiceDate, $this>
     */
    public function serviceDate(): BelongsTo
    {
        return $this->belongsTo(ServiceDate::class, 'service_date_id');
    }
}
