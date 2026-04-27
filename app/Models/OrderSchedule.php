<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'schedule_date',
        'status',
        'price_status',
        'stock_status',
        'created_by',
        'generated_order_id',
        'review_meta',
        'is_active',
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'review_meta'   => 'array',
        'is_active'     => 'boolean',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(OrderScheduleItem::class);
    }

    public function generatedOrder()
    {
        return $this->belongsTo(Order::class, 'generated_order_id');
    }
}
