<?php

namespace App\Http\Controllers\Package;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\OrderReturn;
use App\Models\ProductVariant;
use App\Models\WarehouseInventoryTransfer;
use App\Models\WarehouseTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReceivingController extends Controller
{
    public function incomingOrders()
    {
        $warehouseId = $this->warehouseId();
        $transfers = WarehouseTransfer::with([
            'order.customer', 'order.items.product', 'order.items.variant.product',
            'sourceWarehouse', 'targetWarehouse', 'shipper',
        ])->where('target_warehouse_id', $warehouseId)
            ->whereIn('status', [WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE, WarehouseTransfer::STATUS_RECEIVED_COMPLETED])
            ->orderByRaw("CASE WHEN status = 'delivered_waiting_receive' THEN 0 ELSE 1 END")
            ->latest('id')->get();

        return view('package.receiving.orders', compact('transfers'));
    }

    public function confirmIncomingOrder(WarehouseTransfer $transfer)
    {
        $this->assertTargetWarehouse((int) $transfer->target_warehouse_id);
        if ($transfer->status !== WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE) {
            return back()->with('error', 'Phiếu điều chuyển không còn chờ tiếp nhận.');
        }
        $transfer->loadMissing('order.items');
        $order = $transfer->order;
        if (! $order) {
            return back()->with('error', 'Không tìm thấy đơn hàng.');
        }

        DB::transaction(function () use ($transfer, $order) {
            $document = $this->createImportDocument(
                (int) $transfer->target_warehouse_id,
                'Package tiếp nhận đơn điều chuyển #'.$order->code
            );
            foreach ($order->items as $item) {
                $this->importVariant($document, (int) $item->product_variant_id, (int) $item->quantity, (float) $item->price, $transfer);
            }
            $order->update(['warehouse_id' => $transfer->target_warehouse_id]);
            $transfer->update([
                'status' => WarehouseTransfer::STATUS_RECEIVED_COMPLETED,
                'import_document_id' => $document->id,
                'received_by' => Auth::id(),
                'received_at' => now(),
                'received_total_weight' => $transfer->packed_total_weight,
                'weight_loss' => 0,
            ]);
            $this->history($order, 'package_received_order_transfer', 'Package đã tiếp nhận đơn điều chuyển và nhập kho.');
        });

        return back()->with('success', 'Đã tiếp nhận đơn và cập nhật tồn kho.');
    }

    public function incomingInventory()
    {
        $warehouseId = $this->warehouseId();
        $transfers = WarehouseInventoryTransfer::with([
            'sourceWarehouse:id,name', 'targetWarehouse:id,name', 'requester:id,name',
            'receiver:id,name', 'items.variant.product',
        ])->where('target_warehouse_id', $warehouseId)
            ->whereIn('status', [WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE, WarehouseInventoryTransfer::STATUS_RECEIVED_COMPLETED])
            ->orderByRaw("CASE WHEN status = 'pending_receive' THEN 0 ELSE 1 END")
            ->latest('id')->paginate(12);

        return view('package.receiving.inventory', compact('transfers'));
    }

    public function confirmIncomingInventory(WarehouseInventoryTransfer $transfer)
    {
        $this->assertTargetWarehouse((int) $transfer->target_warehouse_id);
        if ($transfer->status !== WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE) {
            return back()->with('error', 'Phiếu điều chuyển không còn chờ tiếp nhận.');
        }
        $transfer->loadMissing('items');

        DB::transaction(function () use ($transfer) {
            $document = $this->createImportDocument(
                (int) $transfer->target_warehouse_id,
                'Package tiếp nhận điều chuyển kho #'.($transfer->transfer_code ?: $transfer->id)
            );
            foreach ($transfer->items as $item) {
                $this->importVariant($document, (int) $item->product_variant_id, (int) $item->quantity, (float) $item->unit_cost, $transfer);
            }
            $transfer->update([
                'status' => WarehouseInventoryTransfer::STATUS_RECEIVED_COMPLETED,
                'received_by' => Auth::id(),
                'received_at' => now(),
                'import_document_id' => $document->id,
            ]);
        });

        return back()->with('success', 'Đã tiếp nhận hàng và cập nhật nhập kho.');
    }

    public function incomingReturns()
    {
        $warehouseId = $this->warehouseId();
        $returns = OrderReturn::with([
            'order.customer', 'order.shipper', 'warehouse', 'returnItems.productVariant.product',
        ])->where('warehouse_id', $warehouseId)
            ->whereIn('status', ['pending_warehouse', 'requested', 'ship_confirmed', 'warehouse_received'])
            ->orderByRaw("CASE WHEN status = 'warehouse_received' THEN 1 ELSE 0 END")
            ->latest('id')->get();

        return view('package.receiving.returns', compact('returns'));
    }

    public function receiveReturn(OrderReturn $orderReturn)
    {
        $this->assertTargetWarehouse((int) $orderReturn->warehouse_id);
        if ($orderReturn->status === 'warehouse_received') {
            return redirect()->route('package.incoming-returns')->with('error', 'Phiếu trả đã được tiếp nhận.');
        }
        $orderReturn->loadMissing(['order.customer', 'order.shipper', 'returnItems.productVariant.product']);
        foreach ($orderReturn->returnItems as $item) {
            if ($item->original_weight === null) {
                $item->update(['original_weight' => (float) ($item->productVariant?->effective_kg ?? 1) * (int) $item->quantity]);
            }
        }
        $orderReturn->load('returnItems.productVariant.product');

        return view('package.receiving.return-receive', compact('orderReturn'));
    }

    public function confirmReturn(Request $request, OrderReturn $orderReturn)
    {
        $this->assertTargetWarehouse((int) $orderReturn->warehouse_id);
        if ($orderReturn->status === 'warehouse_received') {
            return back()->with('error', 'Phiếu trả đã được tiếp nhận.');
        }
        $validated = $request->validate([
            'item_weights' => ['required', 'array', 'min:1'],
            'item_weights.*.item_id' => ['required', 'integer'],
            'item_weights.*.received_weight' => ['required', 'numeric', 'min:0'],
        ]);
        $orderReturn->loadMissing(['order', 'returnItems']);
        $items = $orderReturn->returnItems->keyBy('id');

        DB::transaction(function () use ($orderReturn, $validated, $items) {
            $document = $this->createImportDocument((int) $orderReturn->warehouse_id, 'Package tiếp nhận đơn trả #'.$orderReturn->order?->code);
            foreach ($validated['item_weights'] as $weight) {
                $item = $items->get((int) $weight['item_id']);
                if (! $item) {
                    continue;
                }
                $item->received_weight = (float) $weight['received_weight'];
                $item->weight_confirmed_at = now();
                $item->calculateWeightLoss();
                $item->save();
                $this->importVariant($document, (int) $item->product_variant_id, (int) $item->quantity, 0, $orderReturn);
            }
            $orderReturn->update([
                'status' => 'warehouse_received',
                'warehouse_confirmed_by' => Auth::id(),
                'warehouse_confirmed_at' => now(),
            ]);
            if ($orderReturn->order) {
                $orderReturn->order->update(['status' => $orderReturn->completedOrderStatus()]);
                $this->history($orderReturn->order, 'package_received_return', 'Package đã tiếp nhận đơn trả và nhập kho.');
            }
        });

        return redirect()->route('package.incoming-returns')->with('success', 'Đã tiếp nhận đơn trả về và cập nhật tồn kho.');
    }

    private function warehouseId(): int
    {
        $warehouseId = (int) (Auth::user()?->warehouse_id ?? 0);
        abort_if($warehouseId <= 0, 403, 'Tài khoản package chưa được gán kho.');

        return $warehouseId;
    }

    private function assertTargetWarehouse(int $warehouseId): void
    {
        abort_unless($warehouseId === $this->warehouseId(), 403, 'Bạn chỉ được tiếp nhận cho kho được gán.');
    }

    private function createImportDocument(int $warehouseId, string $notes): InventoryDocument
    {
        return InventoryDocument::create([
            'type' => 'import', 'document_date' => now()->toDateString(), 'warehouse_id' => $warehouseId,
            'notes' => $notes, 'shipping_fee' => 0, 'user_id' => Auth::id(),
        ]);
    }

    private function importVariant(InventoryDocument $document, int $variantId, int $quantity, float $unitCost, object $reference): void
    {
        if ($variantId <= 0 || $quantity <= 0) {
            return;
        }
        $document->items()->create(['product_variant_id' => $variantId, 'quantity' => $quantity, 'unit_cost' => $unitCost]);
        $inventory = Inventory::firstOrCreate(
            ['warehouse_id' => $document->warehouse_id, 'product_variant_id' => $variantId],
            ['quantity' => 0, 'reserved_quantity' => 0, 'low_stock_threshold' => 5]
        );
        InventoryMovement::create([
            'inventory_id' => $inventory->id, 'quantity' => $quantity, 'type' => 'transfer_in',
            'reference_id' => $reference->id, 'reference_type' => $reference::class, 'user_id' => Auth::id(),
        ]);
        $inventory->increment('quantity', $quantity);
        ProductVariant::whereKey($variantId)->update(['stock' => Inventory::where('product_variant_id', $variantId)->sum('quantity')]);
    }

    private function history(Order $order, string $action, string $note): void
    {
        OrderHistory::create([
            'order_id' => $order->id, 'action' => $action, 'user_id' => Auth::id(), 'role' => 'package',
            'status_before' => $order->status, 'status_after' => $order->status, 'note' => $note,
        ]);
    }
}
