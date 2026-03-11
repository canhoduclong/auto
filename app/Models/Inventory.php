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
        'reserved_quantity',
        'low_stock_threshold',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'reserved_quantity' => 'integer',
        'low_stock_threshold' => 'integer',
    ];

    public function getOnHandAttribute(): int
    {
        return (int) $this->quantity;
    }

    public function getReservedAttribute(): int
    {
        return (int) $this->reserved_quantity;
    }

    public function getAvailableAttribute(): int
    {
        return max(0, (int) $this->quantity - (int) $this->reserved_quantity);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}