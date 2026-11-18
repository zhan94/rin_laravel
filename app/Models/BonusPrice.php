<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonusPrice extends Model
{
    /** @use HasFactory<\Database\Factories\BonusPriceFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'bonus_price';

    protected $fillable = [
        'bonus_id',
        'service',
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
            'bonus_id' => 'integer',
            'service' => 'integer',
            'price' => 'decimal:2',
            'days' => 'integer',
            'per_day' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Bonus, $this>
     */
    public function bonus(): BelongsTo
    {
        return $this->belongsTo(Bonus::class, Bonus::resolvePriceForeignKey());
    }
}
