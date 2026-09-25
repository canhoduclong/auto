<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TruckRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'truck_brand_id',
        'current_price',
        'regulations',
        'description',
        'note',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'current_price' => 'decimal:0',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(TruckBrand::class, 'truck_brand_id');
    }

    public function stops(): HasMany
    {
        return $this->hasMany(TruckRouteStop::class)->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** First stop */
    public function origin(): ?TruckRouteStop
    {
        return $this->stops()->first();
    }

    /** Last stop */
    public function destination(): ?TruckRouteStop
    {
        return $this->stops()->orderByDesc('sort_order')->first();
    }
}
