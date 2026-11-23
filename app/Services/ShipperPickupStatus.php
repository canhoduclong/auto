<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\WarehouseTransfer;
use Illuminate\Database\Eloquent\Builder;

class ShipperPickupStatus
{
    public const WAITING_STATUSES = [
        'pending', 'pending_leader_approval', 'pending_manager_approval', 'pending_warehouse_approval',
        'order_placed', 'order_confirmed', 'confirmed', 'approved', 'ready_to_pack', 'packing', 'picking',
        'packed', 'packed_waiting_pickup', 'overdue_delivery',
    ];

    public function withDetails(Builder $query): Builder
    {
        $history = OrderHistory::query()->whereColumn('order_id', 'orders.id');

        return $query->select('orders.*')->addSelect([
            'pickup_schedule_action' => (clone $history)->select('action')
                ->whereIn('action', ['schedule_created', 'schedule_confirmed', 'schedule_rejected', 'schedule_revoked'])
                ->latest('created_at')->latest('id')->limit(1),
            'pickup_latest_failure' => (clone $history)->select('action')
                ->whereIn('action', ['shipper_pickup_stock_failed', 'shipper_pickup_warehouse_failed', 'shipper_accepted'])
                ->latest('id')->limit(1),
        ])->withExists([
            'warehouseTransfers as pickup_transfer_pending' => fn ($transfer) => $transfer->whereIn('status', [
                WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP, WarehouseTransfer::STATUS_IN_TRANSIT,
                WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE,
            ]),
            'warehouseTransfers as pickup_transfer_received' => fn ($transfer) => $transfer->where('status', WarehouseTransfer::STATUS_RECEIVED_COMPLETED),
            'histories as pickup_delivery_completed' => fn ($history) => $history->whereIn('action', ['delivered', 'mobile_delivered', 'shipper_delivered_bulk']),
        ]);
    }

    public function canBePickedUp(Order $order): bool
    {
        return in_array($order->status, [Order::STATUS_PACKED, Order::STATUS_READY_TO_SHIP], true)
            || ($order->status === Order::STATUS_APPROVED && $order->is_return_order)
            || ($order->status === Order::STATUS_COMPLETED && $order->accounting_sales_import_batch_id
                && $order->needs_operational_completion && $order->pickup_transfer_received);
    }

    public function blocker(Order $order): ?string
    {
        if ($order->pickup_delivery_completed) {
            return null;
        }
        if ($order->status === Order::STATUS_OVERDUE_DELIVERY) {
            return 'Quá giờ giao hàng. Liên hệ điều phối để kiểm tra lịch và tiếp tục giao.';
        }
        if (! $this->canBePickedUp($order)) {
            return match ($order->status) {
                Order::STATUS_PENDING_LEADER_APPROVAL => 'Đơn chờ trưởng nhóm sale duyệt.',
                Order::STATUS_PENDING_MANAGER_APPROVAL => 'Đơn chờ quản lý sale duyệt.',
                'pending_warehouse_approval' => 'Đơn chờ kho duyệt.',
                'pending', Order::STATUS_ORDER_PLACED => 'Đơn chưa được xác nhận. Liên hệ sale phụ trách.',
                'picking' => 'Kho đang lấy hàng. Chờ kho hoàn tất đóng gói.',
                Order::STATUS_PACKING => 'Kho đang đóng hàng. Chờ kho hoàn tất đóng gói.',
                Order::STATUS_APPROVED, Order::STATUS_READY_TO_PACK, Order::STATUS_ORDER_CONFIRMED, 'confirmed'
                    => 'Đơn chưa đóng hàng. Liên hệ kho để đóng gói.',
                default => null,
            };
        }
        if ($order->pickup_transfer_pending) {
            return 'Đơn đang điều chuyển kho. Liên hệ kho nhận để xác nhận nhận hàng.';
        }
        if (! $order->shipper_id) {
            return 'Đơn chưa được gán shipper. Liên hệ điều phối giao hàng.';
        }
        if (! $order->skip_auto_cancel && $order->pickup_schedule_action !== 'schedule_confirmed') {
            return match ($order->pickup_schedule_action) {
                'schedule_created' => 'Lộ trình đã gửi, đang chờ shipper xác nhận.',
                'schedule_rejected', 'schedule_revoked' => 'Lộ trình đã bị từ chối hoặc thu hồi. Liên hệ điều phối gửi lại.',
                default => 'Đơn chưa có lộ trình đã xác nhận. Liên hệ điều phối gửi lộ trình.',
            };
        }

        return null;
    }

    public function issue(Order $order): ?string
    {
        return $this->blocker($order) ?? (! $order->pickup_delivery_completed && $this->canBePickedUp($order) ? match ($order->pickup_latest_failure) {
            'shipper_pickup_stock_failed' => 'Lần nhận gần nhất thất bại vì thiếu tồn kho xuất. Liên hệ kho kiểm tra.',
            'shipper_pickup_warehouse_failed' => 'Lần nhận gần nhất thất bại vì chưa xác định được kho xuất. Liên hệ kho kiểm tra.',
            default => null,
        } : null);
    }

    public function canAccept(Order $order): bool
    {
        return ! $order->pickup_delivery_completed && $this->canBePickedUp($order) && $this->blocker($order) === null;
    }
}
