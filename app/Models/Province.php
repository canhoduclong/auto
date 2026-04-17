<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Ward;

class Province extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
    ];

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    public function wards(): HasMany
    {
        return $this->hasMany(Ward::class);
    }
}

