<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\Product;
use App\Models\ProductCuttingBatch;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Services\ExternalCuttingReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

trait SupplementalCuttingPacking
{
    private function supplementalContext(Request $request, Order $order): array
    {
        $this->authorizePackingOrderAccess($order);
        $warehouseId = (int) ($request->user()->warehouse_id ?: $order->warehouse_id);
        abort_unless($warehouseId && (! $order->warehouse_id || (int) $order->warehouse_id === $warehouseId), 403);
        if (! in_array($order->status, ['approved', Order::STATUS_READY_TO_PACK, Order::STATUS_PACKING], true)
            || ! $this->canProcessOrderOnCurrentRun($order)
            || ProductCuttingBatch::where('order_id', $order->id)->where('status', ProductCuttingBatch::STATUS_IN_PROGRESS)->exists()) {
            throw ValidationException::withMessages(['order' => 'Đơn không thể đóng bù hoặc còn mẻ pha lóc chưa hoàn thành.']);
        }
        $order->load('items.variant.product', 'items.product');
        $expand = (bool) Warehouse::whereKey($warehouseId)->value('expand_packing_size_bounds');
        $recipes = Product::where('product_type', Product::TYPE_WHOLE)->whereNotNull('cutting_product_targets')->get();
        $lines = [];
        foreach ($order->items as $item) {
            if ($item->product?->product_type !== Product::TYPE_CUT) {
                continue;
            }
            $variants = ProductVariant::where('product_id', $item->product_id)->get();
            $allowed = $this->packingVariantIdsForItem($order, $item, $variants, $expand);
            $lines[$item->id] = [
                'item' => $item,
                'variants' => $variants->filter(fn ($v) => in_array((int) $v->id, $allowed, true)
                    && (($item->product->allow_adjacent_packing_sizes ?? true) || $v->id === $item->product_variant_id))->values(),
                'recipes' => $recipes->filter(fn ($p) => isset($p->cutting_product_targets[$item->product_id]))->values(),
            ];
        }

        return compact('warehouseId', 'lines');
    }

    public function supplementalPackingForm(Request $request, Order $order)
    {
        $context = $this->supplementalContext($request, $order);

        $preview = [];
        foreach ($context['lines'] as $id => $line) {
            foreach ($line['recipes'] as $recipe) {
                try {
                    $rates = $recipe->cuttingPercentagesForTarget((int) $line['item']->product_id) ?? [];
                } catch (\RuntimeException $e) {
                    $rates = [];
                }
                $preview[$id][$recipe->id] = [
                    'yield' => (float) ($rates[$line['item']->product_id] ?? 0),
                    'components' => Product::whereIn('id', array_keys($rates))->get()->filter(fn ($product) => $product->id !== $line['item']->product_id)->map(fn ($product) => ['name' => $product->name, 'rate' => (float) $rates[$product->id]])->values(),
                ];
            }
        }

        return view('warehouse.orders.supplemental-packing', $context + compact('order', 'preview'));
    }

    public function supplementalPackingStore(Request $request, Order $order)
    {
        $data = $request->validate([
            'packing_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'lines' => ['required', 'array'], 'lines.*.enabled' => ['nullable', 'boolean'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'lines.*.weight' => ['required', 'numeric', 'min:0.001', 'max:1000000'],
            'lines.*.variant_id' => ['nullable', 'integer'], 'lines.*.recipe_id' => ['nullable', 'integer'],
        ]);
        DB::transaction(function () use ($request, $order, $data) {
            $warehouseId = (int) ($request->user()->warehouse_id ?: $order->warehouse_id);
            Warehouse::whereKey($warehouseId)->lockForUpdate()->firstOrFail();
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $context = $this->supplementalContext($request, $order);
            Inventory::where('warehouse_id', $warehouseId)->orderBy('id')->lockForUpdate()->get();
            $count = 0;
            $receipts = [];
            foreach ($data['lines'] as $id => $row) {
                if (empty($row['enabled'])) {
                    continue;
                }
                $line = $context['lines'][$id] ?? null;
                $variant = $line ? $line['variants']->firstWhere('id', $row['variant_id'] ?? 0) : null;
                $recipe = $line ? $line['recipes']->firstWhere('id', $row['recipe_id'] ?? 0) : null;
                if (! $variant || ! $recipe) {
                    throw ValidationException::withMessages(['lines' => 'Vui lòng chọn size được phép và định mức pha lóc cho từng sản phẩm nhập bù.']);
                }
                $item = $line['item'];
                $weight = round((float) $row['weight'], 3);
                $quantity = (int) $row['quantity'];
                if ($weight + 0.000001 < $item->required_packing_weight) {
                    throw ValidationException::withMessages(['lines' => 'Khối lượng nhập bù phải đủ kg theo bill của '.$item->product->name.'.']);
                }
                app(ExternalCuttingReceiptService::class)->receive($order, $variant, $recipe, $quantity, $weight, $warehouseId, (int) $request->user()->id);
                OrderHistory::create(['order_id' => $order->id, 'action' => 'supplemental_cutting_packing', 'user_id' => $request->user()->id, 'role' => 'warehouse', 'status_before' => $order->status, 'status_after' => $order->status, 'note' => $item->product->name.': nhập bù bên ngoài '.$quantity.' sản phẩm, '.$weight.' kg; số lượng bill trước đóng '.$item->quantity.'; size xác nhận '.$variant->size.'.']);
                $item->update(['quantity' => $quantity, 'packed_quantity' => $quantity, 'actual_weight' => $weight, 'packed_weight' => $weight]);
                $item->packingSizeAllocations()->delete();
                $item->packingSizeAllocations()->create(['product_variant_id' => $variant->id, 'quantity' => $quantity]);
                $receipts[] = ['item_id' => $item->id, 'variant_id' => $variant->id, 'quantity' => $quantity];
                $count++;
            }
            if (! $count) {
                throw ValidationException::withMessages(['lines' => 'Chọn ít nhất một sản phẩm nhập bù.']);
            }
            $order->update(['actual_weight' => $order->items()->sum('actual_weight')]);
            $packingRequest = clone $request;
            $packingRequest->headers->set('Accept', 'application/json');
            foreach (['startPacking', 'completePacking'] as $method) {
                $order->refresh();
                if ($method === 'startPacking' && $order->status === Order::STATUS_PACKING) {
                    continue;
                }
                $result = $this->{$method}($packingRequest, $order)->getData(true);
                if (empty($result['ok'])) {
                    throw ValidationException::withMessages(['order' => $result['message'] ?? 'Không thể hoàn thành đóng hàng.']);
                }
            }
            // Move reservations as well as allocations to the confirmed physical size.
            foreach ($receipts as $receipt) {
                foreach (InventoryReservation::where('order_item_id', $receipt['item_id'])->lockForUpdate()->get() as $reservation) {
                    $stock = Inventory::whereKey($reservation->inventory_id)->lockForUpdate()->firstOrFail();
                    $stock->update(['reserved_quantity' => max(0, $stock->reserved_quantity - $reservation->quantity)]);
                    $reservation->delete();
                }
                $stock = Inventory::where('warehouse_id', $warehouseId)->where('product_variant_id', $receipt['variant_id'])->lockForUpdate()->firstOrFail();
                if ($stock->available < $receipt['quantity']) {
                    throw ValidationException::withMessages(['lines' => 'Tồn khả dụng đã thay đổi. Vui lòng kiểm tra lại trước khi nhập bù.']);
                }
                $stock->increment('reserved_quantity', $receipt['quantity']);
                InventoryReservation::create(['order_item_id' => $receipt['item_id'], 'inventory_id' => $stock->id, 'quantity' => $receipt['quantity'], 'reserved_at' => now()]);
            }
        });

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('warehouse.orders', ['date' => $data['packing_date']])->with('success', 'Đã nhập hàng bù, hoàn tất đóng hàng và cộng phụ phẩm vào danh sách chờ nhập kho.');
    }
}
