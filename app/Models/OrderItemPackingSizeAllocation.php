<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItemPackingSizeAllocation extends Model
{
    protected $fillable = [
        'order_item_id',
        'product_variant_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
