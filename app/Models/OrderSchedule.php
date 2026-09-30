<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'daily_order_schedule_id',
        'text_order_draft_id',
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

    public function dailySchedule()
    {
        return $this->belongsTo(DailyOrderSchedule::class, 'daily_order_schedule_id');
    }

    public function draftTemplate()
    {
        return $this->belongsTo(TextOrderDraft::class, 'text_order_draft_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(OrderScheduleItem::class);
    }

    public function hasActiveGeneratedOrder(): bool
    {
        $order = $this->generatedOrder;

        return $order !== null && $order->status !== 'cancelled' && $order->trash_at === null;
    }

    public function scopeWithActiveGeneratedOrder($query)
    {
        return $query->whereHas('generatedOrder', function ($orders): void {
            $orders->where('status', '!=', 'cancelled');
            if (\Illuminate\Support\Facades\Schema::hasColumn('orders', 'trash_at')) {
                $orders->whereNull('trash_at');
            }
        });
    }

    public function generatedOrder()
    {
        return $this->belongsTo(Order::class, 'generated_order_id');
    }
}
