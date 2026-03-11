<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryMovement;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $warehouseId = $request->integer('warehouse_id') ?: null;

        $inventoryQuery = Inventory::with('productVariant.product', 'warehouse');
        if ($warehouseId) {
            $inventoryQuery->where('warehouse_id', $warehouseId);
        }

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

        $totalOnHand = (int) (clone $inventoryQuery)->sum('quantity');
        $totalReserved = (int) (clone $inventoryQuery)->sum('reserved_quantity');
        $totalAvailable = max(0, $totalOnHand - $totalReserved);

        $stockSummary = [
            'on_hand' => $totalOnHand,
            'reserved' => $totalReserved,
            'available' => $totalAvailable,
        ];

        $warehouses = Warehouse::orderBy('name')->get();

        return view('inventories.index', compact(
            'inventories',
            'warehouses',
            'warehouseId',
            'selectedDate',
            'dailyStats',
            'rangeStats',
            'stockSummary'
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
