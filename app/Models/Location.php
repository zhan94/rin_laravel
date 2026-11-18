<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Locations\LocationLabel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    /** @use HasFactory<\Database\Factories\LocationFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'location_from',
        'location_to',
        'from_time',
        'to_time',
        'service_id',
    ];

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'service_id' => 'integer',
        ];
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'location');
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function serviceModel(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function displayLabel(string $separator = ' - '): string
    {
        return LocationLabel::route(
            $this->location_from,
            $this->location_to,
            $separator,
            'Location #'.(int) $this->id,
        );
    }
}
