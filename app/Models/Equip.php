<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasLocalizedTitle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equip extends Model
{
    /** @use HasFactory<\Database\Factories\EquipFactory> */
    use HasFactory;

    use HasLocalizedTitle;

    public $timestamps = false;

    protected $table = 'equips';

    protected $fillable = [
        'title',
        'title_translations',
        'title_bg',
        'title_en',
        'service',
    ];

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'title_translations' => 'array',
            'service' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function serviceModel(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service');
    }

    /**
     * @return HasMany<EquipPrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(EquipPrice::class, 'equip_id')->orderBy('days');
    }
}
