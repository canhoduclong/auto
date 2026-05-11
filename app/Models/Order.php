<?php
namespace App\Models;

use App\Models\ApprovalOrder;
use App\Models\OrderHistory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'customer_id', 'user_id', 'shipper_id', 'code', 'total', 'status',
        'commission_percent_snapshot', 'commission_amount_snapshot', 'commission_created_at',
        'copied_from_order_id',
        'warehouse_id', 'return_warehouse_id',
        'recipient_name', 'recipient_phone', 'recipient_email', 'recipient_address', 'note',
        'subtotal_amount', 'item_discount_total', 'extra_discount_total',
        'total_discount', 'order_discount', 'order_discount_type', 'total_weight', 'actual_weight', 'charge_shipping_fee', 'shipping_fee',
        'charge_foam_box_fee', 'foam_box_price',
        'amount_paid', 'amount_due', 'payment_method', 'payment_status',
        'qr_code', 'packed_image_path', 'delivered_image_path', 'has_return_order',
        'collected_amount', 'delivered_at', 'return_reason', 'proof_images', 'shipper_note', 'delivery_time',
        'daily_sequence', 'stock_sufficient', 'stock_shortage_detail',
        'stock_alert_status',
        'cancelled_by', 'cancelled_at', 'cancel_reason', 'cancel_images',
    ];

    protected $casts = [
        'proof_images' => 'array',
        'cancel_images' => 'array',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'total_weight' => 'decimal:3',
        'actual_weight' => 'decimal:3',
        'charge_shipping_fee' => 'boolean',
        'shipping_fee' => 'decimal:2',
        'charge_foam_box_fee' => 'boolean',
        'foam_box_price' => 'decimal:2',
        'stock_shortage_detail' => 'array',
        'stock_alert_status' => 'string',
        'commission_percent_snapshot' => 'decimal:2',
        'commission_amount_snapshot' => 'decimal:2',
        'commission_created_at' => 'datetime',
    ];
    
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

    public function customer() { return $this->belongsTo(Customer::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function shipper() { return $this->belongsTo(User::class, 'shipper_id'); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function returnWarehouse() { return $this->belongsTo(Warehouse::class, 'return_warehouse_id'); }
    public function items() { return $this->hasMany(OrderItem::class); }
    public function schedule() { return $this->hasOne(OrderSchedule::class, 'generated_order_id'); }
    public function adjustments() { return $this->hasMany(OrderAdjustment::class); }

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