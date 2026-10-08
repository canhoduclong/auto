<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingExpenseItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['amount' => 'decimal:2'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function dispatch()
    {
        return $this->belongsTo(ShipperDispatchHistory::class);
    }
}
