<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderScheduleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_schedule_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'scheduled_price',
        'current_price',
        'price_diff',
        'stock_available',
        'stock_diff',
    ];

    protected $casts = [
        'price_diff' => 'boolean',
        'stock_diff' => 'boolean',
    ];

    public function schedule()
    {
        return $this->belongsTo(OrderSchedule::class, 'order_schedule_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
