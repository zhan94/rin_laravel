<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPayment extends Model
{
    /** @use HasFactory<\Database\Factories\BookingPaymentFactory> */
    use HasFactory;

    protected $table = 'booking_payments';

    protected $primaryKey = 'booking_id';

    public $incrementing = false;

    protected $fillable = [
        'booking_id',
        'sum_paid',
        'paid',
    ];

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'booking_id' => 'integer',
            'sum_paid' => 'decimal:2',
            'paid' => 'decimal:2',
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
