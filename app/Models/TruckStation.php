<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class TruckStation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'brand_id',
        'province_id',
        'ward_id',
        'address',
        'phone',
        'parking_fee',
        'branch_info',
        'has_home_delivery',
        'home_delivery_fee',
        'note',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active'         => 'boolean',
        'parking_fee'       => 'decimal:0',
        'has_home_delivery' => 'boolean',
        'home_delivery_fee' => 'decimal:0',
    ];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(TruckBrand::class, 'brand_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function routeStops(): HasMany
    {
        return $this->hasMany(TruckRouteStop::class);
    }
}
