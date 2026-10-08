<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingExpenseClaim extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['total' => 'decimal:2', 'confirmed_at' => 'datetime'];

    public function run()
    {
        return $this->belongsTo(ProcessRun::class, 'process_run_id');
    }

    public function items()
    {
        return $this->hasMany(ShippingExpenseItem::class, 'claim_id');
    }

    public function shipper()
    {
        return $this->belongsTo(User::class, 'shipper_id');
    }

    public function payment()
    {
        return $this->belongsTo(Transaction::class, 'payment_transaction_id');
    }
}
