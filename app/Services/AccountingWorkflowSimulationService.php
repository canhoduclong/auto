<?php

namespace App\Services;

use App\Models\AccountingReconciliation;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\WarehouseTransfer;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountingWorkflowSimulationService
{
    public function stockIn(int $variantId, int $warehouseId, int $quantity, float $unitCost, string $date, User $actor): InventoryDocument
    {
        return $this->stockInMany([[
            'product_variant_id' => $variantId,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
        ]], $warehouseId, $date, $actor);
    }

    public function stockInMany(array $items, int $warehouseId, string $date, User $actor): InventoryDocument
    {
        return DB::transaction(function () use ($items, $warehouseId, $date, $actor): InventoryDocument {
            $document = InventoryDocument::create([
                'type' => 'import',
                'document_date' => Carbon::parse($date)->toDateString(),
                'warehouse_id' => $warehouseId,
                'notes' => 'Nhập kho từ trung tâm mô phỏng quy trình kế toán.',
                'shipping_fee' => 0,
                'user_id' => $actor->id,
            ]);
            foreach ($items as $item) {
                $variant = ProductVariant::query()->findOrFail((int) $item['product_variant_id']);
                $quantity = (int) $item['quantity'];
                $document->items()->create([
                    'product_variant_id' => $variant->id,
                    'quantity' => $quantity,
                    'unit_cost' => (float) $item['unit_cost'],
                ]);
                $this->addInventory($variant->id, $warehouseId, $quantity, $document, $actor);
            }

            return $document;
        });
    }

    public function createOrder(array $data, User $actor): Order
    {
        return DB::transaction(function () use ($data, $actor): Order {
            $variant = ProductVariant::query()->with('product')->findOrFail((int) $data['product_variant_id']);
            Customer::query()->findOrFail((int) $data['customer_id']);
            $quantity = (int) $data['quantity'];
            $unitWeight = round(max(0.001, (float) $variant->effective_kg), 3);
            $pricedByKg = (bool) $variant->effective_priced_by_kg;
            $price = (float) $data['price'];
            $totalWeight = round($unitWeight * $quantity, 3);
            $total = round($price * $quantity * ($pricedByKg ? $unitWeight : 1), 2);
            $operatingAt = Carbon::parse($data['date'])->setTime(9, 0);

            $order = Order::create([
                'customer_id' => (int) $data['customer_id'],
                'user_id' => (int) $data['sale_id'],
                'warehouse_id' => (int) $data['warehouse_id'],
                'status' => Order::STATUS_PENDING_LEADER_APPROVAL,
                'subtotal_amount' => $total,
                'total' => $total,
                'total_weight' => $totalWeight,
                'payment_status' => 'unpaid',
                'amount_paid' => 0,
                'amount_due' => 0,
                'delivery_date' => $operatingAt->copy()->addDay()->toDateString(),
                'note' => $data['note'] ?? 'Đơn tạo từ trung tâm mô phỏng quy trình kế toán.',
            ]);
            $order->forceFill([
                'code' => 'WF-'.$operatingAt->format('ymd').'-'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT),
                'created_at' => $operatingAt,
                'updated_at' => $operatingAt,
            ])->saveQuietly();
            $order->items()->create([
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
                'price' => $price,
                'base_price' => $price,
                'unit_discount' => 0,
                'discount_total' => 0,
                'unit_weight' => $unitWeight,
                'is_priced_by_kg' => $pricedByKg,
                'total_weight' => $totalWeight,
                'total' => $total,
            ]);
            $this->history($order, $actor, 'create_order', 'sale', 'Sale đã tạo đơn mô phỏng.', null, Order::STATUS_PENDING_LEADER_APPROVAL);

            return $order;
        });
    }

    public function advanceOrders(array $orderIds, string $action, User $actor): int
    {
        $transitions = [
            'leader_approve' => [[Order::STATUS_PENDING_LEADER_APPROVAL, 'pending'], Order::STATUS_PENDING_MANAGER_APPROVAL, 'leader_approved', 'leader_sale', 'Trưởng phòng đã duyệt đơn.'],
            'manager_approve' => [[Order::STATUS_PENDING_MANAGER_APPROVAL], Order::STATUS_APPROVED, 'manager_approved', 'manager_sale', 'Manager đã duyệt đơn.'],
            'warehouse_confirm' => [[Order::STATUS_APPROVED, Order::STATUS_READY_TO_PACK], Order::STATUS_PACKING, 'warehouse_confirm_pack', 'warehouse', 'Kho đã xác nhận và bắt đầu đóng hàng.'],
            'complete_packing' => [[Order::STATUS_PACKING], Order::STATUS_READY_TO_SHIP, 'complete_packing', 'package', 'Đã hoàn tất đóng hàng, sẵn sàng bàn giao ship.'],
        ];
        if (! isset($transitions[$action])) {
            throw new \InvalidArgumentException('Công đoạn không hợp lệ.');
        }

        [$from, $to, $historyAction, $role, $note] = $transitions[$action];
        $orders = Order::query()->with('items')->whereIn('id', $orderIds)->whereIn('status', $from)->get();
        $processed = 0;
        foreach ($orders as $order) {
            DB::transaction(function () use ($order, $action, $to, $historyAction, $role, $note, $actor): void {
                if ($action === 'warehouse_confirm') {
                    $this->assertEnoughInventory($order);
                }
                $before = (string) $order->status;
                $payload = ['status' => $to];
                if ($action === 'complete_packing') {
                    $weight = round((float) $order->items->sum(fn ($item) => (float) ($item->total_weight ?? 0)), 3);
                    $payload['actual_weight'] = $weight;
                    foreach ($order->items as $item) {
                        $item->update(['actual_weight' => $item->total_weight, 'packed_weight' => $item->total_weight]);
                    }
                }
                $order->update($payload);
                $this->history($order, $actor, $historyAction, $role, $note, $before, $to);
            });
            $processed++;
        }

        return $processed;
    }

    public function createTransfers(array $orderIds, int $targetWarehouseId, int $shipperId, User $actor): int
    {
        $shipper = User::query()->whereKey($shipperId)
            ->whereHas('roles', fn ($query) => $query->whereIn(DB::raw('LOWER(name)'), ['shipper', 'manager_shipper']))
            ->firstOrFail();

        $orders = Order::query()->with('items')->whereIn('id', $orderIds)
            ->whereIn('status', [Order::STATUS_READY_TO_SHIP, Order::STATUS_PACKED])
            ->get();
        $created = 0;

        DB::transaction(function () use ($orders, $targetWarehouseId, $shipper, $actor, &$created): void {
            foreach ($orders as $order) {
                if (! $order->warehouse_id || (int) $order->warehouse_id === $targetWarehouseId) {
                    continue;
                }
                if ($order->warehouseTransfers()->exists()) {
                    continue;
                }

                $transfer = WarehouseTransfer::create([
                    'order_id' => $order->id,
                    'source_warehouse_id' => $order->warehouse_id,
                    'target_warehouse_id' => $targetWarehouseId,
                    'shipper_id' => $shipper->id,
                    'status' => WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP,
                    'packed_total_weight' => (float) $order->items->sum(fn ($item) => (float) ($item->packed_weight ?? $item->total_weight ?? 0)),
                    'note' => 'Tạo từ trung tâm mô phỏng vận hành kế toán.',
                ]);
                $this->history($order, $actor, 'warehouse_transfer_requested', 'warehouse', 'Đã tạo phiếu điều chuyển #'.$transfer->id.' và chọn shipper '.$shipper->name.'.');
                $created++;
            }
        });

        return $created;
    }

    public function pickupTransfers(Collection $transfers, User $actor): int
    {
        $processed = 0;
        foreach ($transfers as $transfer) {
            if ($transfer->status !== WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP) continue;
            DB::transaction(function () use ($transfer, $actor): void {
                $transfer->loadMissing('order.items');
                $order = $transfer->order;
                if (! $order) throw new \RuntimeException('Không tìm thấy đơn của phiếu điều chuyển #'.$transfer->id.'.');

                $document = InventoryDocument::create([
                    'type' => 'export', 'document_date' => now()->toDateString(),
                    'warehouse_id' => $transfer->source_warehouse_id,
                    'notes' => 'Xuất kho điều chuyển đơn #'.$order->code.' [WHT#'.$transfer->id.']',
                    'shipping_fee' => 0, 'user_id' => $actor->id,
                ]);
                foreach ($order->items as $item) {
                    if (! $item->product_variant_id || (int) $item->quantity <= 0) continue;
                    $document->items()->create([
                        'product_variant_id' => $item->product_variant_id,
                        'quantity' => (int) $item->quantity,
                        'unit_cost' => (float) ($item->price ?? 0),
                    ]);
                    $this->deductInventory((int) $item->product_variant_id, (int) $transfer->source_warehouse_id, (int) $item->quantity, $document, $actor);
                }
                $transfer->update([
                    'status' => WarehouseTransfer::STATUS_IN_TRANSIT,
                    'export_document_id' => $document->id,
                    'picked_up_by' => $actor->id,
                    'picked_up_at' => now(),
                    'shipper_pickup_note' => 'Shipper xác nhận nhận hàng hàng loạt.',
                ]);
                $this->history($order, $actor, 'shipper_pickup_warehouse_transfer', 'shipper', 'Shipper đã nhận phiếu điều chuyển #'.$transfer->id.'.');
            });
            $processed++;
        }
        return $processed;
    }

    public function deliverTransfers(Collection $transfers, User $actor): int
    {
        $processed = 0;
        foreach ($transfers as $transfer) {
            if ($transfer->status !== WarehouseTransfer::STATUS_IN_TRANSIT) continue;
            $transfer->update([
                'status' => WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE,
                'delivered_by' => $actor->id,
                'delivered_at' => now(),
                'shipper_delivery_note' => 'Đã giao đến kho đích hàng loạt.',
            ]);
            if ($transfer->order) $this->history($transfer->order, $actor, 'shipper_deliver_warehouse_transfer', 'shipper', 'Đã giao phiếu điều chuyển #'.$transfer->id.' đến kho đích.');
            $processed++;
        }
        return $processed;
    }

    public function receiveTransfers(Collection $transfers, User $actor): int
    {
        $processed = 0;
        foreach ($transfers as $transfer) {
            if ($transfer->status !== WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE) continue;
            DB::transaction(function () use ($transfer, $actor): void {
                $transfer->loadMissing('order.items');
                $order = $transfer->order;
                if (! $order) throw new \RuntimeException('Không tìm thấy đơn của phiếu điều chuyển #'.$transfer->id.'.');
                $document = InventoryDocument::create([
                    'type' => 'import', 'document_date' => now()->toDateString(),
                    'warehouse_id' => $transfer->target_warehouse_id,
                    'notes' => 'Nhập kho điều chuyển đơn #'.$order->code.' [WHT#'.$transfer->id.']',
                    'shipping_fee' => 0, 'user_id' => $actor->id,
                ]);
                $weights = []; $totalWeight = 0.0;
                foreach ($order->items as $item) {
                    if (! $item->product_variant_id || (int) $item->quantity <= 0) continue;
                    $document->items()->create([
                        'product_variant_id' => $item->product_variant_id,
                        'quantity' => (int) $item->quantity,
                        'unit_cost' => (float) ($item->price ?? 0),
                    ]);
                    $this->addInventory((int) $item->product_variant_id, (int) $transfer->target_warehouse_id, (int) $item->quantity, $document, $actor);
                    $weight = (float) ($item->packed_weight ?? $item->total_weight ?? 0);
                    $weights[] = ['order_item_id' => $item->id, 'product_variant_id' => $item->product_variant_id, 'received_weight' => $weight];
                    $totalWeight += $weight;
                }
                $order->update(['warehouse_id' => $transfer->target_warehouse_id]);
                $transfer->update([
                    'status' => WarehouseTransfer::STATUS_RECEIVED_COMPLETED,
                    'import_document_id' => $document->id,
                    'received_by' => $actor->id,
                    'received_at' => now(),
                    'received_weights' => $weights,
                    'received_total_weight' => round($totalWeight, 3),
                    'weight_loss' => round((float) ($transfer->packed_total_weight ?? $totalWeight) - $totalWeight, 3),
                ]);
                $this->history($order, $actor, 'warehouse_transfer_received', 'warehouse', 'Kho đích đã nhận phiếu điều chuyển #'.$transfer->id.'.');
            });
            $processed++;
        }
        return $processed;
    }

    public function assignOrders(array $orderIds, int $shipperId, User $actor): int
    {
        $shipper = User::query()->whereKey($shipperId)
            ->whereHas('roles', fn ($query) => $query->whereIn(DB::raw('LOWER(name)'), ['shipper', 'manager_shipper']))
            ->firstOrFail();
        $orders = Order::query()->whereIn('id', $orderIds)
            ->whereIn('status', [Order::STATUS_READY_TO_SHIP, Order::STATUS_PACKED])
            ->whereHas('warehouseTransfers', fn ($query) => $query->where('status', WarehouseTransfer::STATUS_RECEIVED_COMPLETED))
            ->get();
        foreach ($orders as $order) {
            $order->update(['shipper_id' => $shipper->id]);
            $this->history($order, $actor, 'shipper_assigned', 'manager_shipper', 'Đã điều phối đơn cho shipper '.$shipper->name.'.');
        }
        return $orders->count();
    }

    public function deliverOrders(array $orderIds, string $paymentMode, User $actor): int
    {
        $orders = Order::query()->whereIn('id', $orderIds)->whereNotNull('shipper_id')
            ->whereIn('status', [Order::STATUS_READY_TO_SHIP, Order::STATUS_PACKED, Order::STATUS_DELIVERING])
            ->get();
        foreach ($orders as $order) {
            DB::transaction(function () use ($order, $paymentMode, $actor): void {
                $before = (string) $order->status;
                if ($before !== Order::STATUS_DELIVERING) {
                    $order->update(['status' => Order::STATUS_DELIVERING]);
                    $this->history($order, $actor, 'shipper_accepted', 'shipper', 'Shipper đã nhận đơn và bắt đầu giao.', $before, Order::STATUS_DELIVERING);
                }
                $paid = $paymentMode === 'paid' ? (float) $order->total : 0.0;
                $order->forceFill([
                    'status' => Order::STATUS_DELIVERED,
                    'delivered_at' => now(),
                    'collected_amount' => $paid,
                    'amount_paid' => $paid,
                    'payment_status' => $paymentMode === 'paid' ? 'paid' : 'unpaid',
                    'amount_due' => 0,
                ])->save();
                $this->history($order, $actor, 'shipper_delivered_bulk', 'shipper', $paymentMode === 'paid' ? 'Đã giao và thu đủ tiền hàng.' : 'Đã giao và xác nhận khách trả sau.', Order::STATUS_DELIVERING, Order::STATUS_DELIVERED);
            });
        }
        return $orders->count();
    }

    public function confirmOrders(array $orderIds, User $actor): int
    {
        $orders = Order::query()->with(['customer', 'accountingReconciliation'])
            ->whereIn('id', $orderIds)->where('status', Order::STATUS_DELIVERED)->get();
        $confirmed = 0;
        foreach ($orders as $order) {
            if ($order->accountingReconciliation?->status === AccountingReconciliation::STATUS_CONFIRMED) continue;
            DB::transaction(function () use ($order, $actor): void {
                $recognized = (float) ($order->total ?? 0);
                $paid = max((float) ($order->amount_paid ?? 0), (float) ($order->collected_amount ?? 0));
                $reconciliation = AccountingReconciliation::query()->updateOrCreate(['order_id' => $order->id], [
                    'sale_id' => $order->user_id, 'shipper_id' => $order->shipper_id,
                    'total_amount' => $recognized, 'paid_amount' => $paid,
                    'shipping_fee' => (float) ($order->shipping_fee ?? 0), 'return_amount' => 0,
                    'recognized_revenue' => $recognized, 'status' => AccountingReconciliation::STATUS_CONFIRMED,
                    'confirmed_by' => $actor->id, 'confirmed_at' => now(),
                    'note' => 'Xác nhận hàng loạt từ trung tâm mô phỏng vận hành.',
                ]);
                $due = max(0, $recognized - $paid);
                $order->forceFill([
                    'status' => Order::STATUS_COMPLETED, 'amount_due' => $due,
                    'payment_status' => $due <= 0.0001 ? 'paid' : ($paid > 0 ? 'partially_paid' : 'unpaid'),
                ])->save();
                $this->createCommission($order, $recognized, $actor);
                app(AccountingSalesLedgerService::class)->syncOrder($order->fresh());
                $this->history($order, $actor, 'accounting_confirmed_bulk', 'accounting', 'Kế toán xác nhận giao dịch, doanh số và hoa hồng.', Order::STATUS_DELIVERED, Order::STATUS_COMPLETED);
            });
            $confirmed++;
        }
        return $confirmed;
    }

    private function deductInventory(int $variantId, int $warehouseId, int $quantity, InventoryDocument $document, User $actor): void
    {
        $remaining = $quantity;
        $rows = Inventory::query()->where('product_variant_id', $variantId)->where('warehouse_id', $warehouseId)
            ->orderByDesc('quantity')->lockForUpdate()->get();
        foreach ($rows as $inventory) {
            if ($remaining <= 0) break;
            $available = max(0, (int) $inventory->quantity - (int) $inventory->reserved_quantity);
            $deduct = min($available, $remaining);
            if ($deduct <= 0) continue;
            $inventory->decrement('quantity', $deduct);
            InventoryMovement::create(['inventory_id' => $inventory->id, 'quantity' => -$deduct, 'type' => 'export', 'reference_id' => $document->id, 'reference_type' => InventoryDocument::class, 'user_id' => $actor->id]);
            $remaining -= $deduct;
        }
        if ($remaining > 0) throw new \RuntimeException('Không đủ tồn kho để điều chuyển sản phẩm #'.$variantId.'.');
        ProductVariant::whereKey($variantId)->update(['stock' => Inventory::where('product_variant_id', $variantId)->sum('quantity')]);
    }

    private function assertEnoughInventory(Order $order): void
    {
        if (! $order->warehouse_id) {
            throw new \RuntimeException('Đơn #'.$order->code.' chưa có kho xử lý.');
        }
        foreach ($order->items as $item) {
            if (! $item->product_variant_id) continue;
            $available = (float) Inventory::query()
                ->where('product_variant_id', $item->product_variant_id)
                ->where('warehouse_id', $order->warehouse_id)
                ->sum(DB::raw('quantity - reserved_quantity'));
            if ($available < (int) $item->quantity) {
                throw new \RuntimeException('Không đủ tồn kho cho đơn #'.$order->code.'. Hãy nhập kho trước khi xác nhận đóng hàng.');
            }
        }
    }

    private function addInventory(int $variantId, int $warehouseId, int $quantity, InventoryDocument $document, User $actor): void
    {
        $inventory = Inventory::firstOrCreate(['product_variant_id' => $variantId, 'warehouse_id' => $warehouseId], ['quantity' => 0, 'reserved_quantity' => 0]);
        $inventory->increment('quantity', $quantity);
        InventoryMovement::create(['inventory_id' => $inventory->id, 'quantity' => $quantity, 'type' => 'import', 'reference_id' => $document->id, 'reference_type' => InventoryDocument::class, 'user_id' => $actor->id]);
        ProductVariant::whereKey($variantId)->update(['stock' => Inventory::where('product_variant_id', $variantId)->sum('quantity')]);
    }

    private function createCommission(Order $order, float $recognized, User $actor): void
    {
        if (! Schema::hasTable('order_commissions') || ! $order->user_id) return;
        $percent = (float) ($order->commission_percent_snapshot ?: $order->customer?->commission_percent ?: 0);
        $amount = round($recognized * $percent / 100, 2);
        DB::table('order_commissions')->updateOrInsert(['order_id' => $order->id], [
            'sale_user_id' => $order->user_id, 'customer_id' => $order->customer_id,
            'order_total' => $recognized, 'commission_percent' => $percent, 'commission_amount' => $amount,
            'status' => 'confirmed', 'confirmed_by' => $actor->id, 'confirmed_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $order->forceFill(['commission_percent_snapshot' => $percent, 'commission_amount_snapshot' => $amount, 'commission_created_at' => now()])->saveQuietly();
    }

    private function history(Order $order, User $actor, string $action, string $role, string $note, ?string $before = null, ?string $after = null): void
    {
        OrderHistory::create(['order_id' => $order->id, 'user_id' => $actor->id, 'action' => $action, 'role' => $role, 'status_before' => $before ?? $order->status, 'status_after' => $after ?? $order->status, 'note' => $note]);
    }
}
