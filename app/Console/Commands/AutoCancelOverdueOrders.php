<?php

namespace App\Console\Commands;

use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoCancelOverdueOrders extends Command
{
    protected $signature   = 'orders:auto-cancel-overdue';
    protected $description = 'Hủy các đơn quá hạn giao dịch và trả lại tồn kho';

    /**
     * Statuses eligible for auto-cancellation when the order date has passed.
     *
     * Group 1 – pre-packing (never reached the warehouse packing stage):
     *   pending, pending_leader_approval, pending_manager_approval,
     *   pending_warehouse_approval, approved, ready_to_pack, packing
     *
     * Group 2 – packed but waiting for pickup/delivery and past today:
     *   packed (= STATUS_PACKED), packed_waiting_pickup (= STATUS_READY_TO_SHIP)
     */
    private const CANCELLABLE_STATUSES = [
        'pending',
        'pending_leader_approval',
        'pending_manager_approval',
        'pending_warehouse_approval',
        'approved',
        'ready_to_pack',
        'packing',
        'packed',
        'packed_waiting_pickup',
    ];

    public function handle(): int
    {
        $today     = now()->startOfDay();
        $cancelled = 0;

        $orders = Order::with('items')
            ->whereIn('status', self::CANCELLABLE_STATUSES)
            ->whereDate('created_at', '<', $today)
            ->get();

        foreach ($orders as $order) {
            try {
                DB::transaction(function () use ($order) {
                    $statusBefore = (string) $order->status;

                    // Release reserved stock for this order
                    $this->releaseReservedStock($order);

                    $order->status = Order::STATUS_CANCELLED;
                    $order->save();

                    OrderHistory::create([
                        'order_id'      => $order->id,
                        'action'        => 'auto_cancel_overdue',
                        'user_id'       => null,
                        'role'          => 'system',
                        'status_before' => $statusBefore,
                        'status_after'  => Order::STATUS_CANCELLED,
                        'note'          => 'Hủy do quá thời gian giao dịch',
                    ]);
                });

                $cancelled++;
                $this->line("  Đã hủy đơn #{$order->code} (trạng thái trước: {$order->getOriginal('status')})");
            } catch (\Throwable $e) {
                $this->error("  Lỗi khi hủy đơn #{$order->code}: " . $e->getMessage());
            }
        }

        $this->info("Hoàn tất: đã hủy {$cancelled} đơn quá hạn.");

        return self::SUCCESS;
    }

    private function releaseReservedStock(Order $order): void
    {
        // Collect all inventory IDs affected by this order's reservations
        $affectedInventoryIds = collect();

        foreach ($order->items as $item) {
            $reservations = InventoryReservation::where('order_item_id', $item->id)->lockForUpdate()->get();
            foreach ($reservations as $reservation) {
                $affectedInventoryIds->push($reservation->inventory_id);
            }
            InventoryReservation::where('order_item_id', $item->id)->delete();
        }

        // Reconcile reserved_quantity for each affected inventory from the source of truth
        // (sum of remaining active reservations in the table) rather than just subtracting,
        // which prevents drift accumulation from prior inconsistencies.
        foreach ($affectedInventoryIds->unique() as $inventoryId) {
            $inventory = Inventory::lockForUpdate()->find($inventoryId);
            if (!$inventory) {
                continue;
            }

            $actualReserved = (int) InventoryReservation::where('inventory_id', $inventoryId)->sum('quantity');
            $inventory->reserved_quantity = max(0, $actualReserved);
            $inventory->save();
        }
    }
}
