<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasLocalizedTitle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Bonus extends Model
{
    /** @use HasFactory<\Database\Factories\BonusFactory> */
    use HasFactory;

    use HasLocalizedTitle;

    private static ?string $priceForeignKey = null;

    public $timestamps = false;

    protected $table = 'bonus';

    protected $fillable = [
        'title',
        'title_translations',
        'title_bg',
        'title_en',
        'title_key',
        'location_from',
        'location_to',
        'date_from',
        'date_to',
        'price',
        'description',
    ];

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'title_translations' => 'array',
            'title_key' => 'integer',
            'date_from' => 'date',
            'date_to' => 'date',
            'price' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<BonusPrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(BonusPrice::class, self::resolvePriceForeignKey())->orderBy('days');
    }

    public static function resolvePriceForeignKey(): string
    {
        if (self::$priceForeignKey !== null) {
            return self::$priceForeignKey;
        }

        self::$priceForeignKey = Schema::hasColumn('bonus_price', 'bonus_id')
            ? 'bonus_id'
            : 'service';

        return self::$priceForeignKey;
    }
}
