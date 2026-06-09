<?php

namespace App\Http\Controllers\Package;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderReturn;
use App\Models\WarehouseInventoryTransfer;
use App\Models\WarehouseTransfer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PackageDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $warehouseId = $user?->warehouse_id ? (int) $user->warehouse_id : null;
        $date = (string) $request->input('date', now()->toDateString());

        $scopeWarehouse = static function (Builder $query) use ($warehouseId): Builder {
            if ($warehouseId) {
                $query->where(function (Builder $warehouseQuery) use ($warehouseId) {
                    $warehouseQuery->where('warehouse_id', $warehouseId)
                        ->orWhereNull('warehouse_id');
                });
            }

            return $query;
        };

        $stats = [
            'ready_to_pack' => $scopeWarehouse(Order::query())
                ->whereIn('status', ['approved', Order::STATUS_READY_TO_PACK])
                ->whereDate('created_at', $date)
                ->count(),
            'packing' => $scopeWarehouse(Order::query())
                ->where('status', Order::STATUS_PACKING)
                ->whereDate('created_at', $date)
                ->count(),
            'packed' => $scopeWarehouse(Order::query())
                ->whereIn('status', ['packed', Order::STATUS_READY_TO_SHIP])
                ->whereDate('updated_at', $date)
                ->count(),
            'returning' => $scopeWarehouse(Order::query())
                ->where('status', Order::STATUS_RETURNING)
                ->whereDate('updated_at', $date)
                ->count(),
            'returned' => $scopeWarehouse(Order::query())
                ->whereIn('status', [Order::STATUS_RETURNED, Order::STATUS_RETURNED_COMPLETED])
                ->whereDate('updated_at', $date)
                ->count(),
            'change_requests' => $scopeWarehouse(Order::query())
                ->whereNotNull('warehouse_adjustment_status')
                ->whereDate('updated_at', $date)
                ->count(),
            'incoming_orders' => WarehouseTransfer::query()
                ->when($warehouseId, fn ($query) => $query->where('target_warehouse_id', $warehouseId))
                ->where('status', WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE)
                ->count(),
            'incoming_inventory' => WarehouseInventoryTransfer::query()
                ->when($warehouseId, fn ($query) => $query->where('target_warehouse_id', $warehouseId))
                ->where('status', WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE)
                ->count(),
            'incoming_returns' => OrderReturn::query()
                ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
                ->whereIn('status', ['pending_warehouse', 'requested', 'ship_confirmed'])
                ->count(),
        ];

        $products = Product::with([
            'variants.inventories' => function ($query) use ($warehouseId) {
                if ($warehouseId) {
                    $query->where('warehouse_id', $warehouseId);
                }
                $query->with('movements');
            },
            'variants.product',
        ])->orderBy('name')->get();

        $summaryRows = $products->map(function ($product) {
            $variants = $product->variants
                ->filter(fn ($variant) => $variant->inventories->isNotEmpty())
                ->sortBy(fn ($variant) => mb_strtolower((string) ($variant->name ?? '')))
                ->values();

            $variantRows = $variants->map(function ($variant) use ($product) {
                $closing = (int) ($variant->snapshot_quantity ?? $variant->inventories->sum('quantity'));
                $import = (int) $variant->inventories->sum(fn ($inventory) => $inventory->movements->where('quantity', '>', 0)->sum('quantity'));
                $reserved = (int) ($variant->snapshot_reserved ?? $variant->inventories->sum('reserved_quantity'));
                $export = (int) abs($variant->inventories->sum(fn ($inventory) => $inventory->movements->where('quantity', '<', 0)->sum('quantity')));

                return [
                    'name' => (string) ($variant->name ?: $product->name),
                    'unit' => (string) ($variant->product?->unit_label ?? '—'),
                    'opening' => $closing - $import + $export,
                    'import' => $import,
                    'reserved' => $reserved,
                    'export' => $export,
                    'closing' => $closing,
                ];
            })->values();

            $units = $variantRows->pluck('unit')->filter(fn ($unit) => $unit !== '—')->unique()->values();

            return [
                'product_id' => (int) $product->id,
                'name' => (string) $product->name,
                'unit' => $units->count() === 1 ? (string) $units->first() : ($units->count() > 1 ? 'Nhiều DVT' : '—'),
                'opening' => (int) $variantRows->sum('opening'),
                'import' => (int) $variantRows->sum('import'),
                'reserved' => (int) $variantRows->sum('reserved'),
                'export' => (int) $variantRows->sum('export'),
                'closing' => (int) $variantRows->sum('closing'),
                'variants' => $variantRows,
            ];
        })->filter(fn ($row) => $row['closing'] > 0)->values();

        return view('package.dashboard', compact('stats', 'summaryRows', 'date'));
    }
}
