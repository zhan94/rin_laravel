<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Names\PersonName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'firstname',
        'surname',
        'lastname',
        'date_birth',
        'age',
        'parent_name',
        'parent_phone',
        'email',
        'active',
    ];

    /**
     * @return array<string,string>
     */
    protected function casts(): array
    {
        return [
            'date_birth' => 'date',
            'age' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function fullName(): string
    {
        return PersonName::fromParts([
            $this->firstname,
            $this->surname,
            $this->lastname,
        ]);
    }

    public function displayLabel(): string
    {
        $name = $this->fullName();
        if ($name !== '') {
            return $name;
        }

        $key = $this->getKey();

        return is_scalar($key) && (string) $key !== ''
            ? '#'.(string) $key
            : '';
    }

    public function getFullNameAttribute(): string
    {
        return $this->fullName();
    }
}
