<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceSlot extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceSlotFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'service_id',
        'name',
        'date_start',
        'date_end',
        'max',
        'season_type',
    ];

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'service_id' => 'integer',
            'date_start' => 'date',
            'date_end' => 'date',
            'max' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    /**
     * @return HasMany<ServiceDate, $this>
     */
    public function dates(): HasMany
    {
        return $this->hasMany(ServiceDate::class, 'slot_id')->orderBy('slot_date');
    }

    public function displayLabel(): string
    {
        $name = trim((string) $this->name);
        if ($name !== '') {
            return $name;
        }

        $key = $this->getKey();

        return is_scalar($key) && (string) $key !== ''
            ? '#'.(string) $key
            : '';
    }
}
