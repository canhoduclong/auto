<?php

namespace App\Http\Controllers;

use App\Enums\DeliveryStatus;
use App\Models\GoogleSheetInventorySync;
use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\OrderReturn;
use App\Models\OrderTransfer;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Models\WarehouseDispatchSlip;
use App\Models\WarehouseDispatchSlipEntry;
use App\Models\WarehouseInventoryTransfer;
use App\Models\WarehouseTransfer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminDailyRebuildController extends Controller
{
    private const REBUILDABLE_STATUSES = [
        Order::STATUS_CANCELLED,
        Order::STATUS_DELIVERED,
        Order::STATUS_COMPLETED,
    ];

    public function index(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $date = Carbon::parse($request->input('date', now('Asia/Bangkok')->toDateString()))->toDateString();
        $warehouseId = (int) $request->input('warehouse_id', 0);
        $warehouses = Warehouse::query()->where('status', true)->orderBy('name')->get(['id', 'name']);
        if ($warehouseId <= 0 && $warehouses->isNotEmpty()) {
            $warehouseId = (int) $warehouses->first()->id;
        }
        $warehouse = $warehouses->firstWhere('id', $warehouseId);

        $orders = $this->ordersForDay($date, $warehouseId)
            ->whereIn('status', self::REBUILDABLE_STATUSES)
            ->with(['customer:id,name', 'user:id,name', 'warehouse:id,name'])
            ->orderBy('id')
            ->get();
        $syncs = GoogleSheetInventorySync::query()
            ->where('warehouse_id', $warehouseId)
            ->whereDate('inventory_date', $date)
            ->where('status', 'completed')
            ->orderBy('id')
            ->get();

        return view('admin.daily-rebuild.index', [
            'date' => $date,
            'warehouseId' => $warehouseId,
            'warehouse' => $warehouse,
            'warehouses' => $warehouses,
            'orders' => $orders,
            'cancelledOrders' => $orders->where('status', Order::STATUS_CANCELLED)->values(),
            'deliveredOrders' => $orders->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])->values(),
            'syncs' => $syncs,
        ]);
    }

    public function execute(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'confirmation_date' => ['required', 'date_format:Y-m-d', Rule::in([(string) $request->input('date')])],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'confirm_rebuild' => ['accepted'],
        ], [
            'confirmation_date.in' => 'Ngày xác nhận phải trùng với ngày cần làm lại.',
        ]);
        $date = Carbon::parse($validated['date'])->toDateString();
        $warehouseId = (int) $validated['warehouse_id'];
        $admin = $request->user();

        $result = DB::transaction(function () use ($date, $warehouseId, $admin, $validated): array {
            $orders = $this->ordersForDay($date, $warehouseId)
                ->whereIn('status', self::REBUILDABLE_STATUSES)
                ->with('items')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $affectedVariantIds = collect();
            $dispatchSlipIds = collect();
            $orderTransferIds = collect();

            foreach ($orders as $order) {
                $cleanup = $this->cleanOrderLogistics($order);
                $affectedVariantIds = $affectedVariantIds->merge($cleanup['variant_ids']);
                $dispatchSlipIds = $dispatchSlipIds->merge($cleanup['dispatch_slip_ids']);
                $orderTransferIds = $orderTransferIds->merge($cleanup['order_transfer_ids']);
            }

            $syncResult = $this->resetSheetSyncs($date, $warehouseId, (int) $admin->id, $validated['reason']);
            $affectedVariantIds = $affectedVariantIds->merge($syncResult['variant_ids']);

            foreach ($orders as $order) {
                $before = (string) $order->status;
                $order->items()->update(['actual_weight' => null, 'packed_weight' => null]);
                $order->forceFill([
                    'status' => Order::STATUS_READY_TO_PACK,
                    'delivery_status' => DeliveryStatus::NotShipped->value,
                    'shipper_id' => null,
                    'order_transfer_id' => null,
                    'delivered_at' => null,
                    'delivered_image_path' => null,
                    'packed_image_path' => null,
                    'proof_images' => null,
                    'shipper_note' => null,
                    'collected_amount' => null,
                    'actual_weight' => null,
                    'package_count' => null,
                    'cancelled_by' => null,
                    'cancelled_at' => null,
                    'cancel_reason' => null,
                    'cancel_images' => null,
                    'skip_auto_cancel' => true,
                    'stock_sufficient' => null,
                    'stock_shortage_detail' => null,
                    'stock_alert_status' => null,
                ])->save();
                $this->reserveAvailableStock($order, $warehouseId);

                OrderHistory::query()->create([
                    'order_id' => $order->id,
                    'action' => 'admin_daily_rebuild',
                    'user_id' => $admin->id,
                    'role' => 'admin',
                    'status_before' => $before,
                    'status_after' => Order::STATUS_READY_TO_PACK,
                    'note' => 'Admin làm lại ngày '.$date.': '.$validated['reason'],
                ]);
            }

            WarehouseDispatchSlip::query()
                ->whereIn('id', $dispatchSlipIds->unique()->all())
                ->whereDoesntHave('entries')
                ->delete();
            OrderTransfer::query()
                ->whereIn('id', $orderTransferIds->unique()->all())
                ->whereDoesntHave('orders')
                ->delete();

            foreach ($affectedVariantIds->filter()->unique() as $variantId) {
                ProductVariant::query()->whereKey((int) $variantId)->update([
                    'stock' => Inventory::query()->where('product_variant_id', (int) $variantId)->sum('quantity'),
                ]);
            }

            DB::table('admin_daily_rebuilds')->insert([
                'business_date' => $date,
                'warehouse_id' => $warehouseId,
                'executed_by' => $admin->id,
                'reason' => $validated['reason'],
                'orders_restored_count' => $orders->count(),
                'inventory_syncs_reset_count' => $syncResult['sync_count'],
                'result' => json_encode([
                    'order_ids' => $orders->pluck('id')->all(),
                    'sync_ids' => $syncResult['sync_ids'],
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'orders' => $orders->count(),
                'cancelled' => $orders->where('status', Order::STATUS_CANCELLED)->count(),
                'syncs' => $syncResult['sync_count'],
            ];
        });

        return redirect()->route('warehouse.google-sheet-inventory.index', [
            'date' => $date,
            'warehouse_id' => $warehouseId,
        ])->with('warning', 'Đã hoàn tác '.$result['syncs'].' lần đồng bộ và đưa '
            .$result['orders'].' đơn về Chờ đóng gói. Bước tiếp theo: kiểm tra rồi áp dụng lại tồn Google Sheet của ngày này.');
    }

    private function ordersForDay(string $date, int $warehouseId): Builder
    {
        return Order::query()
            ->forPackingDate($date)
            ->where('warehouse_id', $warehouseId)
            ->whereNull('trash_at');
    }

    /** @return array{variant_ids: Collection, dispatch_slip_ids: Collection, order_transfer_ids: Collection} */
    private function cleanOrderLogistics(Order $order): array
    {
        $this->releaseReservations($order);
        $variantIds = collect();
        $documentIds = collect();
        $dispatchSlipIds = collect();
        $orderTransferIds = collect([$order->order_transfer_id])->filter();

        $warehouseTransfers = WarehouseTransfer::query()->where('order_id', $order->id)->lockForUpdate()->get();
        $inventoryTransfers = WarehouseInventoryTransfer::query()->where('order_id', $order->id)->lockForUpdate()->get();
        $documentIds = $documentIds
            ->merge($warehouseTransfers->pluck('export_document_id'))
            ->merge($warehouseTransfers->pluck('import_document_id'))
            ->merge($inventoryTransfers->pluck('export_document_id'))
            ->merge($inventoryTransfers->pluck('import_document_id'));

        $hasDispatchLinks = $orderTransferIds->isNotEmpty()
            || $warehouseTransfers->isNotEmpty()
            || $inventoryTransfers->isNotEmpty();
        $entries = $hasDispatchLinks
            ? WarehouseDispatchSlipEntry::query()->where(function ($query) use ($orderTransferIds, $warehouseTransfers, $inventoryTransfers): void {
                $query->whereRaw('1 = 0');
                if ($orderTransferIds->isNotEmpty()) {
                    $query->orWhereIn('order_transfer_id', $orderTransferIds->all());
                }
                if ($warehouseTransfers->isNotEmpty()) {
                    $query->orWhereIn('warehouse_transfer_id', $warehouseTransfers->pluck('id'));
                }
                if ($inventoryTransfers->isNotEmpty()) {
                    $query->orWhereIn('inventory_transfer_id', $inventoryTransfers->pluck('id'));
                }
            })->lockForUpdate()->get()
            : collect();
        $dispatchSlipIds = $dispatchSlipIds->merge($entries->pluck('warehouse_dispatch_slip_id'));
        WarehouseDispatchSlipEntry::query()->whereIn('id', $entries->pluck('id'))->delete();

        $directDocuments = InventoryDocument::query()
            ->where('type', 'export')
            ->where('notes', 'Xuất kho cho đơn #'.$order->code)
            ->pluck('id');
        $documentIds = $documentIds->merge($directDocuments)->filter()->unique();
        $variantIds = $variantIds->merge($this->reverseDocuments($documentIds));
        $variantIds = $variantIds->merge($this->reverseMovements(Order::class, collect([$order->id])));

        $returns = OrderReturn::query()->where('order_id', $order->id)->lockForUpdate()->get();
        if ($returns->isNotEmpty()) {
            $variantIds = $variantIds->merge($this->reverseMovements(OrderReturn::class, $returns->pluck('id')));
            foreach ($returns as $return) {
                $returnDocuments = InventoryDocument::query()
                    ->where('notes', 'like', '%[order_return:#'.$return->id.']%')
                    ->pluck('id');
                $variantIds = $variantIds->merge($this->reverseDocuments($returnDocuments));
            }
            OrderReturn::query()->whereIn('id', $returns->pluck('id'))->delete();
        }

        WarehouseTransfer::query()->whereIn('id', $warehouseTransfers->pluck('id'))->delete();
        WarehouseInventoryTransfer::query()->whereIn('id', $inventoryTransfers->pluck('id'))->delete();

        return [
            'variant_ids' => $variantIds,
            'dispatch_slip_ids' => $dispatchSlipIds,
            'order_transfer_ids' => $orderTransferIds,
        ];
    }

    private function reverseDocuments(Collection $documentIds): Collection
    {
        $ids = $documentIds->filter()->map(fn ($id) => (int) $id)->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }
        $variantIds = $this->reverseMovements(InventoryDocument::class, $ids);
        InventoryDocument::query()->whereIn('id', $ids)->delete();

        return $variantIds;
    }

    private function reverseMovements(string $referenceType, Collection $referenceIds): Collection
    {
        if ($referenceIds->isEmpty()) {
            return collect();
        }
        $movements = InventoryMovement::query()
            ->where('reference_type', $referenceType)
            ->whereIn('reference_id', $referenceIds->all())
            ->lockForUpdate()
            ->get();
        $variantIds = collect();
        foreach ($movements->groupBy('inventory_id') as $inventoryId => $rows) {
            $inventory = Inventory::query()->lockForUpdate()->find($inventoryId);
            if (! $inventory) {
                continue;
            }
            $inventory->update(['quantity' => (float) $inventory->quantity - (float) $rows->sum('quantity')]);
            $variantIds->push((int) $inventory->product_variant_id);
        }
        InventoryMovement::query()->whereIn('id', $movements->pluck('id'))->delete();

        return $variantIds;
    }

    private function releaseReservations(Order $order): void
    {
        $itemIds = $order->items->pluck('id');
        $inventoryIds = InventoryReservation::query()->whereIn('order_item_id', $itemIds)->pluck('inventory_id')->unique();
        InventoryReservation::query()->whereIn('order_item_id', $itemIds)->delete();
        foreach ($inventoryIds as $inventoryId) {
            Inventory::query()->whereKey($inventoryId)->update([
                'reserved_quantity' => InventoryReservation::query()->where('inventory_id', $inventoryId)->sum('quantity'),
            ]);
        }
    }

    private function reserveAvailableStock(Order $order, int $warehouseId): void
    {
        $order->loadMissing('items');
        $shortages = [];
        foreach ($order->items as $item) {
            if (! $item->product_variant_id || (int) $item->quantity <= 0) {
                continue;
            }
            $inventory = Inventory::query()
                ->where('warehouse_id', $warehouseId)
                ->where('product_variant_id', $item->product_variant_id)
                ->lockForUpdate()
                ->first();
            $available = $inventory ? max(0, (int) $inventory->quantity - (int) $inventory->reserved_quantity) : 0;
            $reserved = min((int) $item->quantity, $available);
            if ($reserved > 0) {
                $inventory->increment('reserved_quantity', $reserved);
                InventoryReservation::query()->create([
                    'order_item_id' => $item->id,
                    'inventory_id' => $inventory->id,
                    'quantity' => $reserved,
                ]);
            }
            if ($reserved < (int) $item->quantity) {
                $shortages[] = [
                    'variant_id' => (int) $item->product_variant_id,
                    'required_qty' => (int) $item->quantity,
                    'available_qty' => $reserved,
                    'short_qty' => (int) $item->quantity - $reserved,
                ];
            }
        }
        $order->update([
            'stock_sufficient' => $shortages === [],
            'stock_shortage_detail' => $shortages ?: null,
            'stock_alert_status' => $shortages === [] ? 'ready' : 'waiting_stock',
        ]);
    }

    /** @return array{sync_count:int,sync_ids:array,variant_ids:Collection} */
    private function resetSheetSyncs(string $date, int $warehouseId, int $adminId, string $reason): array
    {
        $syncs = GoogleSheetInventorySync::query()
            ->where('warehouse_id', $warehouseId)
            ->whereDate('inventory_date', $date)
            ->where('status', 'completed')
            ->lockForUpdate()
            ->get();
        $variantIds = collect();
        foreach ($syncs as $sync) {
            foreach (collect((array) $sync->changes)->groupBy('product_variant_id') as $variantId => $changes) {
                $delta = round((float) $changes->sum('delta'), 3);
                if ((int) $variantId <= 0 || abs($delta) < 0.001) {
                    continue;
                }
                $inventory = Inventory::query()
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_variant_id', (int) $variantId)
                    ->lockForUpdate()
                    ->first();
                if (! $inventory) {
                    throw ValidationException::withMessages(['date' => 'Không tìm thấy tồn kho cho sản phẩm #'.$variantId.'.']);
                }
                $newQuantity = round((float) $inventory->quantity - $delta, 3);
                if ($newQuantity < 0 || $newQuantity < (float) $inventory->reserved_quantity) {
                    throw ValidationException::withMessages(['date' => 'Không thể reset tồn sản phẩm #'.$variantId.' vì số lượng sau hoàn tác không hợp lệ.']);
                }
                InventoryMovement::query()->create([
                    'inventory_id' => $inventory->id,
                    'quantity' => -$delta,
                    'type' => 'google_sheet_reset',
                    'reference_id' => $sync->id,
                    'reference_type' => GoogleSheetInventorySync::class,
                    'user_id' => $adminId,
                ]);
                $inventory->update(['quantity' => $newQuantity]);
                $variantIds->push((int) $variantId);
            }
            $sync->update([
                'status' => 'reset',
                'reset_by' => $adminId,
                'reset_at' => now(),
                'reset_reason' => $reason,
            ]);
        }

        return ['sync_count' => $syncs->count(), 'sync_ids' => $syncs->pluck('id')->all(), 'variant_ids' => $variantIds];
    }
}
