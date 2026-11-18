<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    /** @use HasFactory<\Database\Factories\BookingFactory> */
    use HasFactory;

    public const SERVICE_TYPE_SERVICE = 1;

    public const SERVICE_TYPE_BONUS = 2;

    public const SKI_SERVICE_IDS = [1, 11];

    public const EQUIP_SERVICE_IDS = [1];

    public const CARD_SERVICE_IDS = [1];

    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'service',
        'service_type',
        'sum_paid',
        'paid',
        'location',
        'approved',
        'active',
        'lunch',
        'note',
    ];

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'customer_id' => 'integer',
            'service' => 'integer',
            'service_type' => 'integer',
            'sum_paid' => 'integer',
            'paid' => 'integer',
            'location' => 'integer',
            'approved' => 'boolean',
            'active' => 'boolean',
            'lunch' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function serviceModel(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service');
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function locationRecord(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location');
    }

    /**
     * @return HasMany<BookingDate, $this>
     */
    public function bookingDates(): HasMany
    {
        return $this->hasMany(BookingDate::class, 'booking_id')->orderBy('id');
    }

    /**
     * @return HasOne<BookingPayment, $this>
     */
    public function payment(): HasOne
    {
        return $this->hasOne(BookingPayment::class, 'booking_id');
    }

    /**
     * @return HasOne<BookingWinterDetail, $this>
     */
    public function winterDetail(): HasOne
    {
        return $this->hasOne(BookingWinterDetail::class, 'booking_id');
    }

    public function displayLabel(): string
    {
        $key = $this->getKey();

        return is_scalar($key) && (string) $key !== ''
            ? '#'.(string) $key
            : '';
    }
}
