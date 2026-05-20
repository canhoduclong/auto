<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'order_adjustment_id',
        'customer_id',
        'warehouse_id',
        'created_by',
        'ship_confirmed_by',
        'ship_confirmed_at',
        'warehouse_confirmed_by',
        'warehouse_confirmed_at',
        'status',
        'reason',
        'evidence_image_path',
        'note',
        'refund_amount',
        'return_shipping_fee',
        'return_scope',
    ];

    protected $casts = [
        'ship_confirmed_at' => 'datetime',
        'warehouse_confirmed_at' => 'datetime',
        'refund_amount' => 'decimal:2',
        'return_shipping_fee' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function adjustment()
    {
        return $this->belongsTo(OrderAdjustment::class, 'order_adjustment_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shipConfirmer()
    {
        return $this->belongsTo(User::class, 'ship_confirmed_by');
    }

    public function warehouseConfirmer()
    {
        return $this->belongsTo(User::class, 'warehouse_confirmed_by');
    }

    public function returnItems()
    {
        return $this->hasMany(ReturnItem::class);
    }

    public function refundTransaction()
    {
        return $this->hasOne(Transaction::class, 'order_return_id');
    }
}