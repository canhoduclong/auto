<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $activeTab = $request->input('active_tab', 'overview');
        if (!in_array($activeTab, ['overview', 'total'], true)) {
            $activeTab = 'overview';
        }

        $warehouseId = $request->integer('warehouse_id') ?: null;

        $inventoryBaseQuery = Inventory::query();
        if ($warehouseId) {
            $inventoryBaseQuery->where('warehouse_id', $warehouseId);
        }

        $inventoryQuery = (clone $inventoryBaseQuery)->with('productVariant.product', 'warehouse');

        $inventories = $inventoryQuery->paginate(10)->appends($request->query());

        $selectedDate = $request->input('selected_date', now()->toDateString());
        $selectedDate = Carbon::parse($selectedDate)->toDateString();

        $rangePreset = $request->input('range_preset', 'week');
        $today = Carbon::today();

        if ($rangePreset === 'month') {
            $fromDate = $request->input('from_date', $today->copy()->subDays(29)->toDateString());
            $toDate = $request->input('to_date', $today->toDateString());
        } elseif ($rangePreset === 'custom') {
            $fromDate = $request->input('from_date', $today->copy()->subDays(6)->toDateString());
            $toDate = $request->input('to_date', $today->toDateString());
        } else {
            $rangePreset = 'week';
            $fromDate = $request->input('from_date', $today->copy()->subDays(6)->toDateString());
            $toDate = $request->input('to_date', $today->toDateString());
        }

        $dailyMovementBase = InventoryMovement::query()->whereDate('created_at', $selectedDate);
        if ($warehouseId) {
            $dailyMovementBase->whereHas('inventory', function ($query) use ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            });
        }

        $dailyImport = (int) (clone $dailyMovementBase)->where('type', 'import')->sum('quantity');
        $dailyExport = abs((int) (clone $dailyMovementBase)->where('type', 'export')->sum('quantity'));
        $dailyAdjustment = (int) (clone $dailyMovementBase)->where('type', 'adjustment')->sum('quantity');
        $dailyNet = (int) (clone $dailyMovementBase)->sum('quantity');

        $dailyDocumentBase = InventoryDocument::query()->whereDate('document_date', $selectedDate);
        if ($warehouseId) {
            $dailyDocumentBase->where('warehouse_id', $warehouseId);
        }

        $dailyStats = [
            'document_count' => (int) (clone $dailyDocumentBase)->count(),
            'import_qty' => $dailyImport,
            'export_qty' => $dailyExport,
            'adjustment_qty' => $dailyAdjustment,
            'net_qty' => $dailyNet,
        ];

        $dailyExportOrderMovementBase = InventoryMovement::query()
            ->whereDate('created_at', $selectedDate)
            ->where('type', 'export')
            ->where('reference_type', Order::class);

        if ($warehouseId) {
            $dailyExportOrderMovementBase->whereHas('inventory', function ($query) use ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            });
        }

        $dailyExportedQuantity = abs((int) (clone $dailyExportOrderMovementBase)->sum('quantity'));

        $exportedOrderIds = (clone $dailyExportOrderMovementBase)
            ->whereNotNull('reference_id')
            ->distinct()
            ->pluck('reference_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        $exportedOrdersByDate = Order::query()->whereIn('id', $exportedOrderIds)->get(['id', 'total', 'shipping_fee']);

        $dailyExportOrderStats = [
            'date' => $selectedDate,
            'order_count' => $exportedOrdersByDate->count(),
            'exported_qty' => $dailyExportedQuantity,
            'shipping_fee_total' => (float) $exportedOrdersByDate->sum(function (Order $order) {
                return (float) ($order->shipping_fee ?? 0);
            }),
            'order_value_total' => (float) $exportedOrdersByDate->sum(function (Order $order) {
                return (float) ($order->total ?? 0);
            }),
        ];

        $dailyExportSeries = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();

            $seriesMovementBase = InventoryMovement::query()
                ->whereDate('created_at', $date)
                ->where('type', 'export')
                ->where('reference_type', Order::class);

            if ($warehouseId) {
                $seriesMovementBase->whereHas('inventory', function ($query) use ($warehouseId) {
                    $query->where('warehouse_id', $warehouseId);
                });
            }

            $seriesExportedQty = abs((int) (clone $seriesMovementBase)->sum('quantity'));

            $seriesOrderIds = (clone $seriesMovementBase)
                ->whereNotNull('reference_id')
                ->distinct()
                ->pluck('reference_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->values();

            $seriesOrders = Order::query()->whereIn('id', $seriesOrderIds)->get(['id', 'total', 'shipping_fee']);

            $seriesShippingFeeTotal = (float) $seriesOrders->sum(function (Order $order) {
                return (float) ($order->shipping_fee ?? 0);
            });

            $seriesOrderValueTotal = (float) $seriesOrders->sum(function (Order $order) {
                return (float) ($order->total ?? 0);
            });

            $hasData = $seriesExportedQty > 0 || $seriesOrders->count() > 0;

            $dailyExportSeries->push([
                'date' => $date,
                'label' => Carbon::parse($date)->format('d/m'),
                'order_count' => $seriesOrders->count(),
                'exported_qty' => $seriesExportedQty,
                'shipping_fee_total' => $seriesShippingFeeTotal,
                'order_value_total' => $seriesOrderValueTotal,
                'has_data' => $hasData,
            ]);
        }

        $rangeMovementBase = InventoryMovement::query()
            ->whereBetween('created_at', [Carbon::parse($fromDate)->startOfDay(), Carbon::parse($toDate)->endOfDay()]);
        if ($warehouseId) {
            $rangeMovementBase->whereHas('inventory', function ($query) use ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            });
        }

        $rangeImport = (int) (clone $rangeMovementBase)->where('type', 'import')->sum('quantity');
        $rangeExport = abs((int) (clone $rangeMovementBase)->where('type', 'export')->sum('quantity'));
        $rangeAdjustment = (int) (clone $rangeMovementBase)->where('type', 'adjustment')->sum('quantity');
        $rangeNet = (int) (clone $rangeMovementBase)->sum('quantity');

        $rangeDocumentBase = InventoryDocument::query()
            ->whereBetween('document_date', [$fromDate, $toDate]);
        if ($warehouseId) {
            $rangeDocumentBase->where('warehouse_id', $warehouseId);
        }

        $rangeStats = [
            'document_count' => (int) (clone $rangeDocumentBase)->count(),
            'import_qty' => $rangeImport,
            'export_qty' => $rangeExport,
            'adjustment_qty' => $rangeAdjustment,
            'net_qty' => $rangeNet,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'range_preset' => $rangePreset,
        ];

        $totalOnHand = (int) (clone $inventoryBaseQuery)->sum('quantity');
        $totalReserved = (int) (clone $inventoryBaseQuery)->sum('reserved_quantity');
        $totalAvailable = max(0, $totalOnHand - $totalReserved);

        $stockSummary = [
            'on_hand' => $totalOnHand,
            'reserved' => $totalReserved,
            'available' => $totalAvailable,
        ];

        $globalOnHand = (int) Inventory::query()->sum('quantity');
        $globalReserved = (int) Inventory::query()->sum('reserved_quantity');
        $globalAvailable = max(0, $globalOnHand - $globalReserved);

        $globalSummary = [
            'total_sku' => (int) Inventory::query()->distinct('product_variant_id')->count('product_variant_id'),
            'inventory_rows' => (int) Inventory::query()->count(),
            'warehouse_count' => (int) Warehouse::query()->count(),
            'on_hand' => $globalOnHand,
            'reserved' => $globalReserved,
            'available' => $globalAvailable,
            'filtered_rows' => $inventories->total(),
        ];

        $warehouseSummary = Inventory::query()
            ->join('warehouses', 'warehouses.id', '=', 'inventories.warehouse_id')
            ->select([
                'warehouses.id as warehouse_id',
                'warehouses.name as warehouse_name',
                DB::raw('COUNT(*) as row_count'),
                DB::raw('COALESCE(SUM(inventories.quantity), 0) as on_hand_sum'),
                DB::raw('COALESCE(SUM(inventories.reserved_quantity), 0) as reserved_sum'),
                DB::raw('COALESCE(SUM(inventories.quantity - inventories.reserved_quantity), 0) as available_sum'),
            ])
            ->groupBy('warehouses.id', 'warehouses.name')
            ->orderByDesc('on_hand_sum')
            ->get();

        $totalStats = [
            'product_count' => (int) Inventory::query()
                ->join('product_variants', 'product_variants.id', '=', 'inventories.product_variant_id')
                ->distinct('product_variants.product_id')
                ->count('product_variants.product_id'),
            'variant_count' => (int) Inventory::query()->distinct('product_variant_id')->count('product_variant_id'),
            'quantity_sum' => $globalOnHand,
            'available_sum' => $globalAvailable,
            'reserved_sum' => $globalReserved,
        ];

        $productTotals = Inventory::query()
            ->join('product_variants', 'product_variants.id', '=', 'inventories.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->select([
                'products.id as product_id',
                'products.name as product_name',
                'products.unit as product_unit',
                DB::raw('COUNT(DISTINCT product_variants.id) as variant_count'),
                DB::raw('COALESCE(SUM(inventories.quantity), 0) as on_hand_sum'),
                DB::raw('COALESCE(SUM(inventories.quantity - inventories.reserved_quantity), 0) as available_sum'),
            ])
            ->groupBy('products.id', 'products.name', 'products.unit')
            ->orderByDesc('on_hand_sum')
            ->limit(20)
            ->get();

        $variantTotals = Inventory::query()
            ->join('product_variants', 'product_variants.id', '=', 'inventories.product_variant_id')
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->select([
                'product_variants.id as variant_id',
                'product_variants.sku as sku',
                'products.name as product_name',
                'products.unit as product_unit',
                DB::raw('COALESCE(SUM(inventories.quantity), 0) as on_hand_sum'),
                DB::raw('COALESCE(SUM(inventories.quantity - inventories.reserved_quantity), 0) as available_sum'),
            ])
            ->groupBy('product_variants.id', 'product_variants.sku', 'products.name', 'products.unit')
            ->orderByDesc('on_hand_sum')
            ->limit(30)
            ->get();

        $warehouses = Warehouse::orderBy('name')->get();

        return view('inventories.index', compact(
            'inventories',
            'warehouses',
            'warehouseId',
            'selectedDate',
            'dailyStats',
            'rangeStats',
            'stockSummary',
            'globalSummary',
            'warehouseSummary',
            'totalStats',
            'productTotals',
            'variantTotals',
            'dailyExportOrderStats',
            'dailyExportSeries',
            'activeTab'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
