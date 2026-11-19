<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicePrice extends Model
{
    public $timestamps = false;

    protected $table = 'services_price';

    protected $fillable = ['days', 'price', 'per_day'];

    /** @return array<string,string> */
    protected function casts(): array
    {
        return [
            'days' => 'integer',
            'price' => 'decimal:2',
            'per_day' => 'decimal:2',
        ];
    }
}
