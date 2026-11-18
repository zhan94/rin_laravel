<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipPrice extends Model
{
    /** @use HasFactory<\Database\Factories\EquipPriceFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'equips_price';

    protected $fillable = [
        'equip_id',
        'price',
        'days',
        'per_day',
    ];

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'equip_id' => 'integer',
            'price' => 'decimal:2',
            'days' => 'integer',
            'per_day' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Equip, $this>
     */
    public function equip(): BelongsTo
    {
        return $this->belongsTo(Equip::class, 'equip_id');
    }
}
