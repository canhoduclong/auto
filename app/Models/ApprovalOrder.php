<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalOrder extends Model
{
    use HasFactory;

    protected $table = 'approval_orders';

    protected $fillable = [
        'order_id',
        'order_adjustment_id',
        'transaction_id',
        'approval_step_id',
        'status',
        'approved_by',
        'approved_at',
        'note',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderAdjustment()
    {
        return $this->belongsTo(OrderAdjustment::class, 'order_adjustment_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function task()
    {
        return $this->belongsTo(\App\Models\TaskAssignment::class, 'task_id');
    }

    public function step()
    {
        return $this->belongsTo(ApprovalStep::class, 'approval_step_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
