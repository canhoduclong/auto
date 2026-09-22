<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED_PENDING_COMPLETION = 'approved_pending_completion';
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
        'destination_type',
        'destination_account_id',
        'external_recipient',
        'external_account_number',
        'external_bank_name',
        'external_bank_branch',
        'method',
        'note',
        'receipt_image_path',
        'delivery_image_path',
        'transfer_proof_path',
        'transfer_proof_uploaded_by',
        'transfer_proof_uploaded_at',
        'status',
        'submitted_by',
        'approved_by',
        'rejected_by',
        'approved_at',
        'rejected_at',
        'reject_reason',
        'request_source',
        'request_department',
        'request_job_title',
        'request_form_type',
        'request_title',
        'request_items',
        'request_subtotal',
        'request_vat',
        'request_total',
        'request_attachments',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'transfer_proof_uploaded_at' => 'datetime',
        'request_items' => 'array',
        'request_subtotal' => 'decimal:2',
        'request_vat' => 'decimal:2',
        'request_total' => 'decimal:2',
        'request_attachments' => 'array',
    ];

    public function order() { return $this->belongsTo(Order::class); }
    public function orderReturn() { return $this->belongsTo(OrderReturn::class); }
    public function expenseType() { return $this->belongsTo(ExpenseType::class); }
    public function payeeUser() { return $this->belongsTo(User::class, 'payee_user_id'); }
    public function transactionCategory() { return $this->belongsTo(TransactionCategory::class, 'transaction_category_id'); }
    public function account() { return $this->belongsTo(Account::class); }
    public function destinationAccount() { return $this->belongsTo(Account::class, 'destination_account_id'); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function submitter() { return $this->belongsTo(User::class, 'submitted_by'); }
    public function approver() { return $this->belongsTo(User::class, 'approved_by'); }
    public function rejecter() { return $this->belongsTo(User::class, 'rejected_by'); }
    public function transferProofUploader() { return $this->belongsTo(User::class, 'transfer_proof_uploaded_by'); }

    public function approvalSteps()
    {
        return $this->hasMany(ApprovalOrder::class, 'transaction_id');
    }

    /** The request detail is authoritative; accounting must never diverge from it. */
    public function calculatedRequestAmounts(): ?array
    {
        if (! $this->request_source) {
            return null;
        }

        $items = collect($this->request_items ?: []);
        if ($items->isEmpty()) {
            $total = (float) ($this->request_total ?? $this->amount);

            return ['subtotal' => max(0, $total - (float) ($this->request_vat ?? 0)), 'vat' => (float) ($this->request_vat ?? 0), 'total' => $total];
        }

        $subtotal = round((float) $items->sum(fn (array $item) =>
            (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0)
        ), 2);
        $vat = round((float) ($this->request_vat ?? 0), 2);

        return ['subtotal' => $subtotal, 'vat' => $vat, 'total' => round($subtotal + $vat, 2)];
    }
}
