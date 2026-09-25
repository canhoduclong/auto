<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyOrderScheduleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_order_schedule_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'scheduled_price',
    ];

    public function dailySchedule()
    {
        return $this->belongsTo(DailyOrderSchedule::class, 'daily_order_schedule_id');
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