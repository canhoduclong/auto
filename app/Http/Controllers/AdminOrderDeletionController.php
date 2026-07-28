<?php

namespace App\Http\Controllers;

use App\Models\AccountingSalesImportBatch;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdminOrderDeletionController extends Controller
{
    public function destroy(Request $request, Order $order)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'reason.required' => 'Vui lòng nhập lý do xóa đơn.',
            'reason.min' => 'Lý do xóa đơn phải có ít nhất 5 ký tự.',
        ]);

        if (Schema::hasTable('order_returns') && DB::table('order_returns')->where('order_id', $order->id)->exists()) {
            return back()->with('error', 'Không thể xóa đơn đã phát sinh trả hàng. Hãy hoàn tất hoặc xử lý phiếu trả hàng trước.');
        }

        try {
            $deletedCode = $order->code ?: '#'.$order->id;

            DB::transaction(function () use ($order, $request, $validated): void {
                $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
                $lockedOrder->load(['items.product', 'items.variant', 'customer', 'user']);

                $batchIds = collect([$lockedOrder->accounting_sales_import_batch_id])
                    ->merge(Schema::hasTable('accounting_sales_entries')
                        ? DB::table('accounting_sales_entries')->where('order_id', $lockedOrder->id)->pluck('import_batch_id')
                        : [])
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                $recognizedRevenue = Schema::hasTable('accounting_reconciliations')
                    ? (float) DB::table('accounting_reconciliations')->where('order_id', $lockedOrder->id)->value('recognized_revenue')
                    : 0.0;
                $commissionAmount = Schema::hasTable('order_commissions')
                    ? (float) DB::table('order_commissions')->where('order_id', $lockedOrder->id)->value('commission_amount')
                    : 0.0;

                DB::table('admin_deleted_orders')->insert([
                    'order_id' => $lockedOrder->id,
                    'order_code' => $lockedOrder->code,
                    'customer_id' => $lockedOrder->customer_id,
                    'sale_user_id' => $lockedOrder->user_id,
                    'order_total' => (float) $lockedOrder->total,
                    'recognized_revenue' => $recognizedRevenue,
                    'commission_amount' => $commissionAmount,
                    'accounting_sales_import_batch_id' => $lockedOrder->accounting_sales_import_batch_id,
                    'reason' => trim($validated['reason']),
                    'snapshot' => json_encode([
                        'order' => $lockedOrder->getAttributes(),
                        'customer' => $lockedOrder->customer?->only(['id', 'customer_code', 'name']),
                        'sale' => $lockedOrder->user?->only(['id', 'name', 'short_name']),
                        'items' => $lockedOrder->items->map(fn ($item) => [
                            'id' => $item->id,
                            'product' => $item->display_name,
                            'product_id' => $item->product_id,
                            'product_variant_id' => $item->product_variant_id,
                            'quantity' => $item->quantity,
                            'unit_weight' => $item->unit_weight,
                            'total_weight' => $item->total_weight,
                            'price' => $item->price,
                            'total' => $item->total,
                        ])->values()->all(),
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'deleted_by' => $request->user()->id,
                    'deleted_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->reverseInventoryEffects($lockedOrder);
                $lockedOrder->delete();

                foreach ($batchIds as $batchId) {
                    $batch = AccountingSalesImportBatch::query()->find($batchId);
                    if (! $batch) {
                        continue;
                    }
                    $batch->update([
                        'row_count' => $batch->entries()->count(),
                        'total_amount' => $batch->entries()->sum('total_amount'),
                    ]);
                }
            });

            return back()->with('success', 'Đã xóa đơn '.$deletedCode.' và loại toàn bộ doanh số, hoa hồng liên quan khỏi sale.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('error', 'Không thể xóa đơn vì còn dữ liệu nghiệp vụ liên quan. Vui lòng kiểm tra log hoặc xử lý chứng từ liên quan trước.');
        }
    }

    private function reverseInventoryEffects(Order $order): void
    {
        $itemIds = $order->items->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($itemIds !== []) {
            $reservations = InventoryReservation::query()
                ->whereIn('order_item_id', $itemIds)
                ->lockForUpdate()
                ->get();
            foreach ($reservations as $reservation) {
                $inventory = Inventory::query()->lockForUpdate()->find($reservation->inventory_id);
                if ($inventory) {
                    $inventory->reserved_quantity = max(0, (int) $inventory->reserved_quantity - (int) $reservation->quantity);
                    $inventory->save();
                }
            }
            InventoryReservation::query()->whereIn('order_item_id', $itemIds)->delete();
        }

        $movements = InventoryMovement::query()
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->lockForUpdate()
            ->get();
        $variantIds = [];
        foreach ($movements->groupBy('inventory_id') as $inventoryId => $inventoryMovements) {
            $inventory = Inventory::query()->lockForUpdate()->find($inventoryId);
            if (! $inventory) {
                continue;
            }
            $inventory->quantity = (int) $inventory->quantity - (int) $inventoryMovements->sum('quantity');
            $inventory->save();
            $variantIds[] = (int) $inventory->product_variant_id;
        }
        if ($movements->isNotEmpty()) {
            InventoryMovement::query()->whereIn('id', $movements->pluck('id'))->delete();
        }

        foreach (array_unique($variantIds) as $variantId) {
            ProductVariant::query()->whereKey($variantId)->update([
                'stock' => (int) Inventory::query()->where('product_variant_id', $variantId)->sum('quantity'),
            ]);
        }
    }
}
