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
    public const REQUEST_FORM_CASH = 'cash_request';
    public const REQUEST_FORM_PAYMENT = 'payment_proposal';

    protected $fillable = [
        'order_id',
        'order_return_id',
        'customer_id',
        'amount',
        'type',
        'expense_type_id',
        'payee_user_id',
        'transaction_category_id',
        'account_id',
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
        'request_source',
        'request_department',
        'request_form_type',
        'request_title',
        'request_items',
        'request_subtotal',
        'request_vat',
        'request_total',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'request_items' => 'array',
        'request_subtotal' => 'decimal:2',
        'request_vat' => 'decimal:2',
        'request_total' => 'decimal:2',
    ];

    public function order() { return $this->belongsTo(Order::class); }
    public function orderReturn() { return $this->belongsTo(OrderReturn::class); }
    public function expenseType() { return $this->belongsTo(ExpenseType::class); }
    public function payeeUser() { return $this->belongsTo(User::class, 'payee_user_id'); }
    public function transactionCategory() { return $this->belongsTo(TransactionCategory::class, 'transaction_category_id'); }
    public function account() { return $this->belongsTo(Account::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function submitter() { return $this->belongsTo(User::class, 'submitted_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function rejecter() { return $this->belongsTo(User::class, 'rejected_by'); }

    public function approvalSteps()
    {
        return $this->hasMany(ApprovalOrder::class, 'transaction_id');
    }
}
