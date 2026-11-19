<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasLocalizedTitle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceFactory> */
    use HasFactory;

    use HasLocalizedTitle;

    public $timestamps = false;

    protected $fillable = [
        'title',
        'title_translations',
        'title_bg',
        'title_en',
        'title_key',
        'max',
        'active',
    ];

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'title_translations' => 'array',
            'title_key' => 'integer',
            'max' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ServiceSlot, $this>
     */
    public function slots(): HasMany
    {
        return $this->hasMany(ServiceSlot::class, 'service_id');
    }

    /** @return HasMany<ServicePrice, $this> */
    public function prices(): HasMany
    {
        return $this->hasMany(ServicePrice::class, 'services')->orderBy('days')->orderBy('id');
    }

    /**
     * @return HasMany<Location, $this>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class, 'service_id');
    }
}
