<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerOwnershipHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'cycle_no',
        'sale_id',
        'priority_level',
        'started_at',
        'ended_at',
        'transfer_reason',
        'final_score',
        'order_id',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale()
    {
        return $this->belongsTo(User::class, 'sale_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
