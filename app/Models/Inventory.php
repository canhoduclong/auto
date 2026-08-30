<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_variant_id',
        'warehouse_id',
        'quantity',
        'weight_kg',
        'reserved_quantity',
        'low_stock_threshold',
    ];

    protected $casts = [
        'quantity' => 'float',
        'weight_kg' => 'float',
        'reserved_quantity' => 'float',
        'low_stock_threshold' => 'float',
    ];

    public function getOnHandAttribute(): float
    {
        return (float) $this->quantity;
    }

    public function getReservedAttribute(): float
    {
        return (float) $this->reserved_quantity;
    }

    public function getAvailableAttribute(): float
    {
        return max(0, (float) $this->quantity - (float) $this->reserved_quantity);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function movements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function reservations()
    {
        return $this->hasMany(InventoryReservation::class);
    }
}
