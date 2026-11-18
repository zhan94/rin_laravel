<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardPrice extends Model
{
    /** @use HasFactory<\Database\Factories\CardPriceFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $table = 'cards_price';

    protected $fillable = [
        'card_id',
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
            'card_id' => 'integer',
            'price' => 'decimal:2',
            'days' => 'integer',
            'per_day' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Card, $this>
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class, 'card_id');
    }
}
