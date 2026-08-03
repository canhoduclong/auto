<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierDebtPayment extends Model
{
    protected $fillable = [
        'procurement_purchase_id',
        'transaction_id',
        'amount',
        'paid_at',
        'recorded_by',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function purchase()
    {
        return $this->belongsTo(ProcurementPurchase::class, 'procurement_purchase_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function recorder()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
