<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryStocktakeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stocktake_id',
        'inventory_id',
        'product_variant_id',
        'system_quantity',
        'counted_quantity',
        'difference',
        'system_weight_kg',
        'counted_weight_kg',
        'weight_difference',
    ];

    protected $casts = [
        'system_quantity' => 'float',
        'counted_quantity' => 'float',
        'difference' => 'float',
        'system_weight_kg' => 'float',
        'counted_weight_kg' => 'float',
        'weight_difference' => 'float',
    ];

    public function stocktake()
    {
        return $this->belongsTo(InventoryStocktake::class, 'stocktake_id');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
