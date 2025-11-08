<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_return_id',
        'product_variant_id',
        'quantity',
        'condition',
    ];

    public function orderReturn()
    {
        return $this->belongsTo(OrderReturn::class);
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}