<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingReconciliation extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';

    protected $fillable = [
        'order_id',
        'sale_id',
        'shipper_id',
        'total_amount',
        'paid_amount',
        'shipping_fee',
        'return_amount',
        'recognized_revenue',
        'status',
        'confirmed_by',
        'confirmed_at',
        'note',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'return_amount' => 'decimal:2',
        'recognized_revenue' => 'decimal:2',
        'confirmed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function sale()
    {
        return $this->belongsTo(User::class, 'sale_id');
    }

    public function shipper()
    {
        return $this->belongsTo(User::class, 'shipper_id');
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
