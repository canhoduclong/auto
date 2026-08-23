<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderFee extends Model
{
    protected $fillable = [
        'order_id', 'order_fee_type_id', 'order_adjustment_id', 'fee_code', 'fee_name',
        'calculation_type', 'direction', 'rate', 'base_amount', 'amount',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function type()
    {
        return $this->belongsTo(OrderFeeType::class, 'order_fee_type_id');
    }

    public function adjustment()
    {
        return $this->belongsTo(OrderAdjustment::class, 'order_adjustment_id');
    }
}
