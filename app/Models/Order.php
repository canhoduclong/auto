<?php
namespace App\Models;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\ApprovalOrder;
use App\Models\OrderHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'customer_id', 'user_id', 'shipper_id', 'code', 'total', 'status',
        'commission_percent_snapshot', 'commission_amount_snapshot', 'commission_created_at',
        'copied_from_order_id', 'order_type', 'workflow_code', 'is_return_order', 'parent_order_id',
        'warehouse_id', 'warehouse_can_adjust', 'return_warehouse_id',
        'recipient_name', 'recipient_phone', 'recipient_email', 'recipient_address', 'note',
        'subtotal_amount', 'item_discount_total', 'extra_discount_total',
        'total_discount', 'order_discount', 'order_discount_type', 'total_weight', 'actual_weight', 'charge_shipping_fee', 'shipping_fee', 'shipping_fee_transaction_id',
        'charge_foam_box_fee', 'foam_box_price',
        'amount_paid', 'amount_due', 'payment_method', 'payment_status',
        'qr_code', 'packed_image_path', 'delivered_image_path', 'has_return_order',
        'collected_amount', 'delivered_at', 'return_reason', 'proof_images', 'shipper_note', 'delivery_time', 'delivery_date',
        'customer_feedback_status', 'customer_feedback_note', 'customer_feedback_sale_review',
        'customer_feedback_images', 'customer_feedback_by', 'customer_feedback_at',
        'daily_sequence', 'stock_sufficient', 'stock_shortage_detail',
        'stock_alert_status',
        'warehouse_adjustment_status', 'warehouse_adjustment_note', 'warehouse_adjustment_changes',
        'warehouse_adjustment_requested_by', 'warehouse_adjustment_requested_at',
        'warehouse_adjustment_confirmed_by', 'warehouse_adjustment_confirmed_at',
        'warehouse_adjustment_rejected_by', 'warehouse_adjustment_rejected_at', 'warehouse_adjustment_rejected_reason',
        'cancelled_by', 'cancelled_at', 'cancel_reason', 'cancel_images', 'trash_at',
    ];

    protected $casts = [
        'proof_images' => 'array',
        'cancel_images' => 'array',
        'customer_feedback_images' => 'array',
        'delivered_at' => 'datetime',
        'customer_feedback_at' => 'datetime',
        'delivery_date' => 'date',
        'cancelled_at' => 'datetime',
        'trash_at' => 'datetime',
        'total_weight' => 'decimal:3',
        'actual_weight' => 'decimal:3',
        'charge_shipping_fee' => 'boolean',
        'warehouse_can_adjust' => 'boolean',
        'shipping_fee' => 'decimal:2',
        'charge_foam_box_fee' => 'boolean',
        'is_return_order' => 'boolean',
        'foam_box_price' => 'decimal:2',
        'stock_shortage_detail' => 'array',
        'stock_alert_status' => 'string',
        'warehouse_adjustment_changes' => 'array',
        'warehouse_adjustment_requested_at' => 'datetime',
        'warehouse_adjustment_confirmed_at' => 'datetime',
        'warehouse_adjustment_rejected_at' => 'datetime',
        'commission_percent_snapshot' => 'decimal:2',
        'commission_amount_snapshot' => 'decimal:2',
        'commission_created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            $order->delivery_date ??= now()->addDay()->toDateString();
            $order->shipper_id ??= $order->resolveDefaultShipperId();
        });

        // Công nợ đơn hàng chỉ được ghi nhận sau khi kế toán xác nhận đối soát.
        // Chặn mọi luồng cập nhật tổng tiền/thanh toán vô tình làm phát sinh nợ sớm.
        static::saving(function (Order $order): void {
            if (!$order->isDirty('amount_due')) {
                return;
            }

            $recognizedRevenue = Schema::hasTable('accounting_reconciliations') && $order->exists
                ? AccountingReconciliation::query()
                    ->where('order_id', $order->getKey())
                    ->where('status', AccountingReconciliation::STATUS_CONFIRMED)
                    ->value('recognized_revenue')
                : null;

            if ($recognizedRevenue === null) {
                $order->amount_due = 0;

                return;
            }

            $effectivePaid = max(
                (float) ($order->amount_paid ?? 0),
                (float) ($order->collected_amount ?? 0)
            );
            $order->amount_due = max(0, (float) $recognizedRevenue - $effectivePaid);
        });
    }

    private function resolveDefaultShipperId(): ?int
    {
        if (!$this->customer_id || $this->is_return_order || (string) $this->order_type === 'order_return') {
            return null;
        }

        $defaultShipperId = Customer::query()
            ->whereKey($this->customer_id)
            ->value('default_shipper_id');

        return $defaultShipperId ? (int) $defaultShipperId : null;
    }

    public function scopeForDeliveryDate($query, string $date)
    {
        return $query->whereDate('delivery_date', $date);
    }
    
    public function approvals()
    {
        return $this->hasMany(ApprovalOrder::class);
    }

    public function transactions() { return $this->hasMany(Transaction::class); }
    public function histories() { return $this->hasMany(OrderHistory::class); }

    const STATUS_PENDING_LEADER_APPROVAL = 'pending_leader_approval';
    const STATUS_PENDING_MANAGER_APPROVAL = 'pending_manager_approval';
    const STATUS_APPROVED = 'approved'; 
    const STATUS_SHIPPING = 'shipping'; 
    const STATUS_REJECTED = 'rejected';


    // Trạng thái đơn hàng chuẩn
    const STATUS_ORDER_PLACED = 'order_placed';
    const STATUS_ORDER_CONFIRMED = 'order_confirmed';
    const STATUS_PACKED = 'packed';
    const STATUS_IN_DELIVERY = 'in_delivery';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_COMPLETED = 'completed';
    const STATUS_RETURNED = 'returned';
    const STATUS_CANCELLED = 'cancelled';

    public static function statusOptions()
    {
        return [
            self::STATUS_ORDER_PLACED => 'Đơn hàng đã đặt',
            self::STATUS_ORDER_CONFIRMED => 'Đơn hàng đã xác nhận',
            self::STATUS_PACKED => 'Đã đóng gói',
            self::STATUS_IN_DELIVERY => 'Đang giao hàng',
            self::STATUS_DELIVERED => 'Đã giao hàng',
            self::STATUS_COMPLETED => 'Hoàn thành',
            self::STATUS_RETURNED => 'Hoàn trả',
            self::STATUS_CANCELLED => 'Đã hủy',
        ];
    }
    
    // Warehouse & Shipper statuses
    const STATUS_READY_TO_PACK    = 'ready_to_pack';
    const STATUS_PACKING          = 'packing';
    const STATUS_READY_TO_SHIP    = 'packed_waiting_pickup';
    const STATUS_DELIVERING       = 'delivering';
    const STATUS_RETURNING        = 'returning';
    const STATUS_RETURNED_COMPLETED = 'returned_completed';

    public const CANCELLABLE_STATUSES = [
        'draft',
        'pending',
        self::STATUS_PENDING_LEADER_APPROVAL,
        self::STATUS_PENDING_MANAGER_APPROVAL,
        'pending_warehouse_approval',
        self::STATUS_APPROVED,
        self::STATUS_PACKING,
        'confirmed',
        'picking',
        self::STATUS_ORDER_PLACED,
    ];

    public function canBeCancelled(): bool
    {
        return in_array((string) $this->status, self::CANCELLABLE_STATUSES, true);
    }

    const WAREHOUSE_ADJUSTMENT_STATUS_NONE = 'none';
    const WAREHOUSE_ADJUSTMENT_STATUS_PENDING_SALE_CONFIRMATION = 'pending_sale_confirmation';
    const WAREHOUSE_ADJUSTMENT_STATUS_SALE_CONFIRMED = 'sale_confirmed';
    const WAREHOUSE_ADJUSTMENT_STATUS_SALE_REJECTED = 'sale_rejected';

    public const CUSTOMER_FEEDBACK_GOOD = 'good';
    public const CUSTOMER_FEEDBACK_CAREFUL = 'careful';
    public const CUSTOMER_FEEDBACK_RISK = 'risk';

    public static function customerFeedbackOptions(): array
    {
        return [
            self::CUSTOMER_FEEDBACK_GOOD => 'Khách ổn định',
            self::CUSTOMER_FEEDBACK_CAREFUL => 'Cần đóng kỹ',
            self::CUSTOMER_FEEDBACK_RISK => 'Rủi ro/khó tính',
        ];
    }

    public static function customerFeedbackMeta(?string $status): array
    {
        return match ($status) {
            self::CUSTOMER_FEEDBACK_GOOD => ['label' => 'Khách ổn định', 'class' => 'bg-success-subtle text-success border-success-subtle', 'level' => 1],
            self::CUSTOMER_FEEDBACK_RISK => ['label' => 'Rủi ro/khó tính', 'class' => 'bg-danger-subtle text-danger border-danger-subtle', 'level' => 3],
            self::CUSTOMER_FEEDBACK_CAREFUL => ['label' => 'Cần đóng kỹ', 'class' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle', 'level' => 2],
            default => ['label' => 'Chưa có phản hồi', 'class' => 'bg-secondary-subtle text-secondary border-secondary-subtle', 'level' => 0],
        };
    }

    public function canReceiveCustomerFeedback(): bool
    {
        return in_array((string) $this->status, [
            self::STATUS_DELIVERED,
            self::STATUS_COMPLETED,
            self::STATUS_RETURNING,
            self::STATUS_RETURNED,
            self::STATUS_RETURNED_COMPLETED,
        ], true) || (bool) ($this->has_return_order ?? false);
    }

    public function hasCustomerFeedback(): bool
    {
        return filled($this->customer_feedback_status) || filled($this->customer_feedback_note);
    }

    public function clearWarehouseAdjustmentState(): self
    {
        $this->forceFill([
            'warehouse_adjustment_status' => self::WAREHOUSE_ADJUSTMENT_STATUS_NONE,
            'warehouse_adjustment_note' => null,
            'warehouse_adjustment_changes' => null,
            'warehouse_adjustment_requested_by' => null,
            'warehouse_adjustment_requested_at' => null,
            'warehouse_adjustment_confirmed_by' => null,
            'warehouse_adjustment_confirmed_at' => null,
            'warehouse_adjustment_rejected_by' => null,
            'warehouse_adjustment_rejected_at' => null,
            'warehouse_adjustment_rejected_reason' => null,
        ]);

        return $this;
    }

    public function resetForCopiedOrder(?int $sourceOrderId = null): self
    {
        $this->forceFill([
            'status' => OrderStatus::Pending->value,
            'payment_status' => PaymentStatus::Unpaid->value,
            'delivery_status' => DeliveryStatus::NotShipped->value,
            'delivered_at' => null,
            'packed_image_path' => null,
            'delivered_image_path' => null,
            'amount_paid' => 0,
            'amount_due' => 0,
            'payment_method' => null,
            'collected_amount' => null,
            'proof_images' => null,
            'return_reason' => null,
            'shipping_fee_transaction_id' => null,
            'copied_from_order_id' => $sourceOrderId,
            'warehouse_id' => null,
            'daily_sequence' => null,
            'stock_sufficient' => null,
            'stock_shortage_detail' => null,
            'stock_alert_status' => null,
            'cancelled_by' => null,
            'cancelled_at' => null,
            'cancel_reason' => null,
            'cancel_images' => null,
            'trash_at' => null,
        ]);

        return $this->clearWarehouseAdjustmentState();
    }

    public function customer() { return $this->belongsTo(Customer::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function customerFeedbackUser() { return $this->belongsTo(User::class, 'customer_feedback_by'); }
    public function warehouseAdjustmentRequester() { return $this->belongsTo(User::class, 'warehouse_adjustment_requested_by'); }
    public function warehouseAdjustmentConfirmer() { return $this->belongsTo(User::class, 'warehouse_adjustment_confirmed_by'); }
    public function warehouseAdjustmentRejecter() { return $this->belongsTo(User::class, 'warehouse_adjustment_rejected_by'); }
    public function parentOrder() { return $this->belongsTo(Order::class, 'parent_order_id'); }
    public function returnOrders() { return $this->hasMany(Order::class, 'parent_order_id'); }
    public function returnRecords() { return $this->hasMany(OrderReturn::class); }
    public function shipper() { return $this->belongsTo(User::class, 'shipper_id'); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function returnWarehouse() { return $this->belongsTo(Warehouse::class, 'return_warehouse_id'); }
    public function items() { return $this->hasMany(OrderItem::class); }
    public function schedule() { return $this->hasOne(OrderSchedule::class, 'generated_order_id'); }
    public function adjustments() { return $this->hasMany(OrderAdjustment::class); }
    public function warehouseTransfers() { return $this->hasMany(WarehouseTransfer::class); }
    public function accountingReconciliation() { return $this->hasOne(AccountingReconciliation::class); }
    public function shippingFeeRequest() { return $this->belongsTo(Transaction::class, 'shipping_fee_transaction_id'); }

    public function getPaymentStatusTextAttribute()
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return 'Đã hoàn thành';
        }
        $paid = $this->transactions()->where('type', 'payment')->sum('amount') - $this->transactions()->where('type', 'refund')->sum('amount');
        if ($paid >= $this->total) {
            return 'Đã thanh toán đủ';
        } elseif ($paid > 0) {
            return 'Thanh toán một phần';
        } else {
            return 'Chưa thanh toán';
        }
    }
    public function isPaid() {
        $paid = $this->transactions()->where('type', 'payment')->sum('amount') - $this->transactions()->where('type', 'refund')->sum('amount');
        return $paid >= $this->total;
    }
    public function isPartialPaid() {
        $paid = $this->transactions()->where('type', 'payment')->sum('amount') - $this->transactions()->where('type', 'refund')->sum('amount');
        return $paid > 0 && $paid < $this->total;
    }
    public function isUnpaid() {
        $paid = $this->transactions()->where('type', 'payment')->sum('amount') - $this->transactions()->where('type', 'refund')->sum('amount');
        return $paid <= 0;
    }
}
