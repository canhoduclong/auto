<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'order_id',
        'order_return_id',
        'customer_id',
        'amount',
        'type',
        'method',
        'note',
        'receipt_image_path',
        'delivery_image_path',
        'status',
        'submitted_by',
        'approved_by',
        'rejected_by',
        'approved_at',
        'rejected_at',
        'reject_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function order() { return $this->belongsTo(Order::class); }
    public function orderReturn() { return $this->belongsTo(OrderReturn::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function submitter() { return $this->belongsTo(User::class, 'submitted_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function rejecter() { return $this->belongsTo(User::class, 'rejected_by'); }

    public function approvalSteps()
    {
        return $this->hasMany(ApprovalOrder::class, 'transaction_id');
    }
}

