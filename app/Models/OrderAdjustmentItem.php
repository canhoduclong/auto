<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderAdjustmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_adjustment_id',
        'order_item_id',
        'product_id',
        'product_variant_id',
        'original_quantity',
        'adjusted_quantity',
        'original_price',
        'adjusted_price',
        'original_weight',
        'adjusted_weight',
        'warehouse_received_quantity',
        'warehouse_received_weight',
        'warehouse_condition',
        'note',
    ];

    protected $casts = [
        'original_price' => 'decimal:2',
        'adjusted_price' => 'decimal:2',
        'original_weight' => 'decimal:3',
        'adjusted_weight' => 'decimal:3',
        'warehouse_received_weight' => 'decimal:3',
    ];

    public function adjustment()
    {
        return $this->belongsTo(OrderAdjustment::class, 'order_adjustment_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
