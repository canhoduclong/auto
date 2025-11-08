<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'customer_id', 'user_id', 'code', 'total', 'status',
        'amount_paid', 'amount_due', 'payment_method', 'payment_status',
        'qr_code'
    ];
    
    public function approvals()
    {
        return $this->hasMany(OrderApproval::class);
    }

    public function transactions() { return $this->hasMany(Transaction::class); }

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

    public function customer() { return $this->belongsTo(Customer::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(OrderItem::class); }

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