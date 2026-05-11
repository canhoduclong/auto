<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderAdjustment extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'order_id',
        'order_return_id',
        'requested_by',
        'approved_by',
        'rejected_by',
        'completed_by',
        'workflow_code',
        'status',
        'approval_note',
        'reject_reason',
        'adjustment_note',
        'evidence_images',
        'return_warehouse_id',
        'warehouse_confirmation_status',
        'warehouse_confirmed_by',
        'warehouse_confirmed_at',
        'warehouse_confirmation_note',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'completed_at',
    ];

    protected $casts = [
        'evidence_images' => 'array',
        'warehouse_confirmed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderReturn()
    {
        return $this->belongsTo(OrderReturn::class, 'order_return_id');
    }

    public function items()
    {
        return $this->hasMany(OrderAdjustmentItem::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function completer()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function returnWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'return_warehouse_id');
    }

    public function warehouseConfirmer()
    {
        return $this->belongsTo(User::class, 'warehouse_confirmed_by');
    }

    public function approvalSteps()
    {
        return $this->hasMany(\App\Models\ApprovalOrder::class, 'order_adjustment_id');
    }
}
