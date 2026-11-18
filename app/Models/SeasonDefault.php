<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeasonDefault extends Model
{
    /** @use HasFactory<\Database\Factories\SeasonDefaultFactory> */
    use HasFactory;

    protected $table = 'season_defaults';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'start_month',
        'start_day',
        'end_month',
        'end_day',
        'updated_at',
    ];

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'start_month' => 'integer',
            'start_day' => 'integer',
            'end_month' => 'integer',
            'end_day' => 'integer',
            'updated_at' => 'datetime',
        ];
    }
}
