<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\WarehouseDashboardController;
use App\Models\Inventory;
use App\Models\InventoryReservation;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class WarehouseMobileController extends Controller
{
    public function index()
    {
        return view('mobile.warehouse.index');
    }

    public function orders(Request $request): JsonResponse
    {
        $status = (string) $request->query('status', '');
        $date = (string) $request->query('date', now()->toDateString());

        $query = Order::query()
            ->with(['customer:id,name,phone,address', 'items.variant.product'])
            ->whereDate('created_at', $date)
            ->orderBy('created_at');

        if ($status !== '') {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', [
                'approved',
                Order::STATUS_READY_TO_PACK,
                Order::STATUS_PACKING,
                'packed',
                Order::STATUS_READY_TO_SHIP,
            ]);
        }

        $orders = $query->get();
        $warehouseId = $request->user()?->warehouse_id ? (int) $request->user()->warehouse_id : null;
        $guards = $this->buildStockGuards($orders, $warehouseId);

        $data = $orders->map(function (Order $order) use ($guards) {
            $guard = $guards[$order->id] ?? ['has_shortage' => false, 'can_start_packing' => true, 'shortages' => []];

            return [
                'id' => (int) $order->id,
                'code' => (string) ($order->code ?: ('#' . $order->id)),
                'status' => (string) $order->status,
                'customer' => (string) ($order->customer?->name ?? '—'),
                'phone' => (string) ($order->customer?->phone ?? '—'),
                'address' => (string) ($order->customer?->address ?? '—'),
                'items_count' => (int) $order->items->count(),
                'has_shortage' => (bool) ($guard['has_shortage'] ?? false),
                'can_start_packing' => (bool) ($guard['can_start_packing'] ?? true),
                'shortages' => $guard['shortages'] ?? [],
                'created_at' => optional($order->created_at)->format('d/m H:i'),
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function orderDetail(Order $order): JsonResponse
    {
        $order->load(['customer:id,name,phone,address', 'items.variant.product']);

        $items = $order->items->map(function ($item) {
            return [
                'id' => (int) $item->id,
                'name' => (string) ($item->variant?->name ?? $item->product?->name ?? 'Sản phẩm'),
                'sku' => (string) ($item->variant?->sku ?? '—'),
                'quantity' => (int) ($item->quantity ?? 0),
                'price' => (float) ($item->price ?? 0),
            ];
        });

        return response()->json([
            'data' => [
                'id' => (int) $order->id,
                'code' => (string) ($order->code ?: ('#' . $order->id)),
                'status' => (string) $order->status,
                'customer' => (string) ($order->customer?->name ?? '—'),
                'phone' => (string) ($order->customer?->phone ?? '—'),
                'address' => (string) ($order->customer?->address ?? '—'),
                'items' => $items,
            ],
        ]);
    }

    public function startPacking(Request $request, Order $order, WarehouseDashboardController $warehouseDashboardController)
    {
        $request->headers->set('Accept', 'application/json');

        return $warehouseDashboardController->startPacking($request, $order);
    }

    private function buildStockGuards(Collection $orders, ?int $warehouseId): array
    {
        $queue = $orders
            ->filter(fn (Order $order) => in_array($order->status, ['approved', Order::STATUS_READY_TO_PACK], true))
            ->values();

        if ($queue->isEmpty()) {
            return [];
        }

        $variantIds = $queue->flatMap(fn (Order $order) => $order->items->pluck('product_variant_id'))
            ->filter()
            ->unique()
            ->values();

        $itemIds = $queue->flatMap(fn (Order $order) => $order->items->pluck('id'))
            ->filter()
            ->unique()
            ->values();

        $availableByVariant = $this->availableByVariant($variantIds, $warehouseId);
        $reservedByItem = $this->reservedByItem($itemIds, $warehouseId);

        $blockedVariantIds = [];
        $result = [];

        foreach ($queue as $order) {
            $shortages = [];

            foreach ($order->items as $item) {
                $variantId = (int) $item->product_variant_id;
                $required = max(0, (int) $item->quantity);

                if ($variantId <= 0 || $required <= 0) {
                    continue;
                }

                $reserved = max(0, (int) ($reservedByItem[$item->id] ?? 0));
                $available = max(0, (int) ($availableByVariant[$variantId] ?? 0));
                $effective = $available + $reserved;

                if (in_array($variantId, $blockedVariantIds, true) || $effective < $required) {
                    $shortages[] = [
                        'variant_id' => $variantId,
                        'variant_name' => (string) ($item->variant?->name ?? $item->product?->name ?? ('SP #' . $variantId)),
                        'required_qty' => $required,
                        'available_qty' => $effective,
                        'short_qty' => max(0, $required - $effective),
                    ];

                    $blockedVariantIds[] = $variantId;
                    continue;
                }

                $availableByVariant[$variantId] = max(0, $available - max(0, $required - $reserved));
            }

            $result[$order->id] = [
                'has_shortage' => !empty($shortages),
                'can_start_packing' => empty($shortages),
                'shortages' => $shortages,
            ];
        }

        return $result;
    }

    private function availableByVariant(Collection $variantIds, ?int $warehouseId): array
    {
        if ($variantIds->isEmpty()) {
            return [];
        }

        $query = Inventory::query()
            ->selectRaw('product_variant_id, COALESCE(SUM(quantity - reserved_quantity), 0) as available_qty')
            ->whereIn('product_variant_id', $variantIds->all());

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->groupBy('product_variant_id')
            ->pluck('available_qty', 'product_variant_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    private function reservedByItem(Collection $itemIds, ?int $warehouseId): array
    {
        if ($itemIds->isEmpty()) {
            return [];
        }

        $query = InventoryReservation::query()
            ->selectRaw('inventory_reservations.order_item_id, COALESCE(SUM(inventory_reservations.quantity), 0) as reserved_qty')
            ->whereIn('inventory_reservations.order_item_id', $itemIds->all());

        if ($warehouseId) {
            $query->join('inventories', 'inventories.id', '=', 'inventory_reservations.inventory_id')
                ->where('inventories.warehouse_id', $warehouseId);
        }

        return $query->groupBy('inventory_reservations.order_item_id')
            ->pluck('reserved_qty', 'inventory_reservations.order_item_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }
}
