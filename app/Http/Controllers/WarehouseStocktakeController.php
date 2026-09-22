<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryAdjustment;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\InventoryStocktake;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Services\GoogleSheetsInventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WarehouseStocktakeController extends Controller
{
    public function index(Request $request, GoogleSheetsInventoryService $sheets)
    {
        $request->validate([
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'counted_at' => ['nullable', 'date', 'before_or_equal:now'],
            'inventory_date' => ['nullable', 'date', 'before_or_equal:today'],
            'stocktake_type' => ['nullable', 'in:opening,closing'],
            'load_sheet_closing' => ['nullable', 'boolean'],
        ]);

        $warehouse = $this->resolveWarehouse($request);
        $search = trim((string) $request->input('search', ''));
        $stocktakeType = (string) $request->input('stocktake_type', InventoryStocktake::TYPE_OPENING);
        $usesLegacyCountedAt = $request->filled('counted_at') && ! $request->filled('inventory_date');
        $inventoryDate = $request->filled('inventory_date')
            ? Carbon::parse($request->input('inventory_date'))->startOfDay()
            : ($request->filled('counted_at') ? Carbon::parse($request->input('counted_at'))->startOfDay() : today());
        $countedAt = $usesLegacyCountedAt
            ? Carbon::parse($request->input('counted_at'))
            : $this->resolveCountedAt($inventoryDate, $stocktakeType);
        $sheetClosingByVariant = collect();
        $sheetLoadError = null;
        $sheetUnmatchedRows = collect();

        if ($request->boolean('load_sheet_closing')) {
            if ($stocktakeType !== InventoryStocktake::TYPE_CLOSING) {
                $sheetLoadError = 'Chỉ có thể nạp tồn cuối Google Sheet khi loại kiểm kê là Tồn cuối.';
            } else {
                try {
                    $preview = $sheets->preview($warehouse, $inventoryDate->toDateString(), true);
                    $sheetUnmatchedRows = $preview['rows']->where('matched', false)->pluck('sheet_code');
                    $sheetClosingByVariant = $preview['rows']
                        ->filter(fn (array $row): bool => $row['matched'] && $row['variant_id'] !== null)
                        ->mapWithKeys(fn (array $row): array => [(int) $row['variant_id'] => (float) $row['stock_quantity']]);

                    foreach ($sheetClosingByVariant->keys() as $variantId) {
                        Inventory::query()->firstOrCreate(
                            [
                                'warehouse_id' => $warehouse->id,
                                'product_variant_id' => (int) $variantId,
                            ],
                            [
                                'quantity' => 0,
                                'weight_kg' => 0,
                                'reserved_quantity' => 0,
                            ]
                        );
                    }
                } catch (\Throwable $exception) {
                    report($exception);
                    $sheetLoadError = $exception->getMessage();
                }
            }
        }

        $inventories = Inventory::query()
            ->with(['productVariant.product:id,name,unit'])
            ->where('warehouse_id', $warehouse->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('productVariant', function ($variantQuery) use ($search) {
                    $variantQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy(
                ProductVariant::query()
                    ->select('product_id')
                    ->whereColumn('product_variants.id', 'inventories.product_variant_id')
                    ->limit(1)
            )
            ->orderBy('product_variant_id')
            ->paginate(50)
            ->withQueryString();

        $this->attachBalancesAt($inventories->getCollection(), $countedAt);
        $packedReservations = $this->packedReservationsByInventory(
            $inventories->getCollection()->pluck('id')
        );
        foreach ($inventories as $inventory) {
            $packedQuantity = (float) $packedReservations->get((int) $inventory->id, 0);
            $inventory->setAttribute('packed_reserved_quantity', $packedQuantity);
            $inventory->setAttribute(
                'stocktake_unpacked_quantity',
                max(0, round((float) $inventory->stocktake_quantity - $packedQuantity, 3))
            );

            if ($sheetClosingByVariant->has((int) $inventory->product_variant_id)) {
                $inventory->setAttribute(
                    'sheet_closing_quantity',
                    $sheetClosingByVariant->get((int) $inventory->product_variant_id)
                );
            }
        }

        $recentStocktakes = InventoryStocktake::query()
            ->with(['creator:id,name', 'items.productVariant.product:id,name'])
            ->withCount('items')
            ->where('warehouse_id', $warehouse->id)
            ->orderByDesc('counted_at')
            ->limit(10)
            ->get();

        $warehouses = $this->canSelectAnyWarehouse($request)
            ? Warehouse::query()->orderBy('name')->get(['id', 'name'])
            : collect([$warehouse]);

        $stocktakeRoutePrefix = $this->stocktakeRoutePrefix($request);
        $stocktakeLayout = $stocktakeRoutePrefix === 'accounting'
            ? 'layouts.accounting'
            : 'layouts.warehouse';
        $stocktakeContentSection = $stocktakeRoutePrefix === 'accounting'
            ? 'accounting_content'
            : 'content';

        return view('warehouse.stocktakes.index', compact(
            'warehouse',
            'warehouses',
            'inventories',
            'recentStocktakes',
            'search',
            'countedAt',
            'inventoryDate',
            'stocktakeType',
            'sheetLoadError',
            'sheetUnmatchedRows',
            'stocktakeRoutePrefix',
            'stocktakeLayout',
            'stocktakeContentSection'
        ));
    }

    public function store(Request $request)
    {
        $warehouse = $this->resolveWarehouse($request);
        $validated = $request->validate([
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'counted_at' => ['required', 'date', 'before_or_equal:now'],
            'stocktake_type' => ['nullable', 'in:opening,closing'],
            'note' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array'],
            'items.*.expected_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.expected_weight_kg' => ['required', 'numeric', 'min:0'],
            'items.*.counted_quantity' => ['nullable', 'numeric', 'multiple_of:1', 'min:0'],
            'items.*.counted_weight_kg' => ['nullable', 'numeric', 'min:0'],
        ], [
            'items.*.counted_quantity.numeric' => 'Số lượng thực tế phải là số.',
            'items.*.counted_quantity.multiple_of' => 'Số lượng thực tế phải là số nguyên. Vui lòng nhập khối lượng vào ô Kg thực tế.',
            'items.*.counted_quantity.min' => 'Số lượng thực tế không được âm.',
        ]);

        $countedRows = collect($validated['items'])
            ->filter(fn ($row) => $this->hasCountedValue($row, 'counted_quantity')
                || $this->hasCountedValue($row, 'counted_weight_kg'))
            ->mapWithKeys(function ($row, $inventoryId) {
                $row['_has_counted_quantity'] = $this->hasCountedValue($row, 'counted_quantity');
                $row['counted_quantity'] = $row['_has_counted_quantity']
                    ? $row['counted_quantity']
                    : $row['expected_quantity'];
                $row['counted_weight_kg'] = $this->hasCountedValue($row, 'counted_weight_kg')
                    ? $row['counted_weight_kg']
                    : $row['expected_weight_kg'];

                return [(int) $inventoryId => $row];
            });

        if ($countedRows->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Vui lòng nhập số con/số lượng hoặc số kg thực tế cho ít nhất một sản phẩm.',
            ]);
        }

        $countedAt = Carbon::parse($validated['counted_at']);
        $stocktakeType = (string) ($validated['stocktake_type'] ?? InventoryStocktake::TYPE_OPENING);

        $stocktake = DB::transaction(function () use ($countedRows, $validated, $warehouse, $countedAt, $stocktakeType) {
            $inventories = Inventory::query()
                ->where('warehouse_id', $warehouse->id)
                ->whereIn('id', $countedRows->keys()->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($inventories->count() !== $countedRows->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Có sản phẩm không thuộc kho đang kiểm kê.',
                ]);
            }

            $balancesAtCount = $this->balancesAt($inventories, $countedAt);
            $this->guardUnchangedInventory($countedRows, $balancesAtCount);
            $packedReservations = $this->packedReservationsByInventory($inventories->pluck('id'));

            $stocktake = InventoryStocktake::create([
                'warehouse_id' => $warehouse->id,
                'counted_at' => $countedAt,
                'stocktake_type' => $stocktakeType,
                'status' => InventoryStocktake::STATUS_COMPLETED,
                'note' => trim((string) ($validated['note'] ?? '')) ?: null,
                'created_by' => Auth::id(),
            ]);
            $stocktake->update([
                'code' => 'KKK-'.now()->format('Ymd').'-'.str_pad((string) $stocktake->id, 5, '0', STR_PAD_LEFT),
            ]);

            $variantIds = collect();
            foreach ($countedRows as $inventoryId => $row) {
                $inventory = $inventories->get($inventoryId);
                $balanceAtCount = $balancesAtCount->get($inventoryId);
                $systemQuantity = round((float) $balanceAtCount['quantity'], 3);
                // Hàng đã hoàn tất đóng gói không còn nằm trên kệ để kiểm đếm,
                // nhưng vẫn thuộc tồn sổ cho tới khi shipper nhận và phiếu xuất
                // kho được lập. Vì vậy số thực đếm chỉ thay thế phần chưa đóng.
                $packedReservedQuantity = round(
                    (float) $packedReservations->get((int) $inventory->id, 0),
                    3
                );
                $countedQuantity = $row['_has_counted_quantity']
                    ? round((float) $row['counted_quantity'] + $packedReservedQuantity, 3)
                    : $systemQuantity;
                $difference = round($countedQuantity - $systemQuantity, 3);
                $systemWeight = round((float) $balanceAtCount['weight_kg'], 3);
                $countedWeight = round((float) $row['counted_weight_kg'], 3);
                $weightDifference = round($countedWeight - $systemWeight, 3);

                $stocktake->items()->create([
                    'inventory_id' => $inventory->id,
                    'product_variant_id' => $inventory->product_variant_id,
                    'system_quantity' => $systemQuantity,
                    'counted_quantity' => $countedQuantity,
                    'physical_counted_quantity' => $row['_has_counted_quantity']
                        ? round((float) $row['counted_quantity'], 3)
                        : null,
                    'packed_reserved_quantity' => $packedReservedQuantity,
                    'difference' => $difference,
                    'system_weight_kg' => $systemWeight,
                    'counted_weight_kg' => $countedWeight,
                    'weight_difference' => $weightDifference,
                ]);

                if (abs($difference) >= 0.001 || abs($weightDifference) >= 0.001) {
                    $adjustment = new InventoryAdjustment;
                    $adjustment->forceFill([
                        'inventory_id' => $inventory->id,
                        'quantity' => $difference,
                        'weight_kg' => $weightDifference,
                        'reason' => 'Kiểm kê '.($stocktakeType === InventoryStocktake::TYPE_CLOSING ? 'tồn cuối ' : 'tồn đầu ').$stocktake->code,
                        'user_id' => Auth::id(),
                        'created_at' => $countedAt,
                        'updated_at' => $countedAt,
                    ])->save();

                    $movement = new InventoryMovement;
                    $movement->forceFill([
                        'inventory_id' => $inventory->id,
                        'quantity' => $difference,
                        'weight_kg' => $weightDifference,
                        'type' => 'stocktake_adjustment',
                        'reference_id' => $stocktake->id,
                        'reference_type' => InventoryStocktake::class,
                        'user_id' => Auth::id(),
                        'created_at' => $countedAt,
                        'updated_at' => $countedAt,
                    ])->save();
                }
                // A past stocktake must restate the present balance only by its
                // historical difference. Replacing the current balance with the
                // old physical count would discard all later movements.
                $inventory->update([
                    'quantity' => round((float) $inventory->quantity + $difference, 3),
                    'weight_kg' => round((float) $inventory->weight_kg + $weightDifference, 3),
                ]);
                $variantIds->push((int) $inventory->product_variant_id);
            }

            $this->syncVariantStocks($variantIds->unique());

            return $stocktake;
        });

        $redirectParameters = ['warehouse_id' => $warehouse->id];
        if ($request->filled('stocktake_type')) {
            $redirectParameters['inventory_date'] = $countedAt->toDateString();
            $redirectParameters['stocktake_type'] = $stocktakeType;
        }

        return redirect()->route($this->stocktakeRoutePrefix($request).'.stocktakes.index', $redirectParameters)
            ->with('success', 'Đã hoàn tất phiếu kiểm kê '.$stocktake->code.' và cập nhật tồn kho.');
    }

    private function resolveCountedAt(Carbon $inventoryDate, string $stocktakeType): Carbon
    {
        if ($stocktakeType === InventoryStocktake::TYPE_CLOSING) {
            return $inventoryDate->isToday() ? now() : $inventoryDate->copy()->endOfDay();
        }

        return $inventoryDate->copy()->startOfDay();
    }

    private function resolveWarehouse(Request $request): Warehouse
    {
        $user = Auth::user();
        $assignedWarehouseId = (int) ($user?->warehouse_id ?? 0);

        if (! $this->canSelectAnyWarehouse($request) && $assignedWarehouseId > 0) {
            return Warehouse::query()->findOrFail($assignedWarehouseId);
        }

        abort_unless($this->canSelectAnyWarehouse($request), 403, 'Tài khoản chưa được gán kho quản lý.');

        $warehouseId = (int) $request->input('warehouse_id', 0);
        $warehouse = $warehouseId > 0
            ? Warehouse::query()->find($warehouseId)
            : Warehouse::query()->where('status', true)->orderBy('name')->first();

        abort_unless($warehouse, 404, 'Không có kho để kiểm kê.');

        return $warehouse;
    }

    private function canSelectAnyWarehouse(Request $request): bool
    {
        if ($this->stocktakeRoutePrefix($request) === 'accounting') {
            return true;
        }

        $user = Auth::user();

        return $user !== null && $user->hasRole('admin');
    }

    private function stocktakeRoutePrefix(Request $request): string
    {
        return str_starts_with((string) $request->route()?->getName(), 'accounting.')
            ? 'accounting'
            : 'warehouse';
    }

    private function guardUnchangedInventory(Collection $countedRows, Collection $balancesAtCount): void
    {
        foreach ($countedRows as $inventoryId => $row) {
            $expected = round((float) $row['expected_quantity'], 3);
            $current = round((float) $balancesAtCount->get($inventoryId)['quantity'], 3);
            $expectedWeight = round((float) $row['expected_weight_kg'], 3);
            $currentWeight = round((float) $balancesAtCount->get($inventoryId)['weight_kg'], 3);

            if (abs($expected - $current) >= 0.001 || abs($expectedWeight - $currentWeight) >= 0.001) {
                throw ValidationException::withMessages([
                    'items' => 'Tồn kho tại thời điểm kiểm kê đã thay đổi. Vui lòng tải lại tồn theo thời điểm và kiểm tra lại số thực tế.',
                ]);
            }
        }
    }

    /**
     * Rebuild balances at a point in time from the current balance by reversing
     * every inventory movement recorded after that point.
     */
    private function balancesAt(Collection $inventories, Carbon $countedAt): Collection
    {
        if ($inventories->isEmpty()) {
            return collect();
        }

        $futureMovements = InventoryMovement::query()
            ->whereIn('inventory_id', $inventories->pluck('id'))
            ->where('created_at', '>', $countedAt)
            ->selectRaw('inventory_id, SUM(quantity) AS quantity_after, SUM(COALESCE(weight_kg, 0)) AS weight_after')
            ->groupBy('inventory_id')
            ->get()
            ->keyBy('inventory_id');

        return $inventories->mapWithKeys(function (Inventory $inventory) use ($futureMovements): array {
            $future = $futureMovements->get($inventory->id);

            return [$inventory->id => [
                'quantity' => round((float) $inventory->quantity - (float) ($future?->quantity_after ?? 0), 3),
                'weight_kg' => round((float) $inventory->weight_kg - (float) ($future?->weight_after ?? 0), 3),
            ]];
        });
    }

    private function attachBalancesAt(Collection $inventories, Carbon $countedAt): void
    {
        $balances = $this->balancesAt($inventories, $countedAt);

        foreach ($inventories as $inventory) {
            $balance = $balances->get($inventory->id, ['quantity' => 0, 'weight_kg' => 0]);
            $inventory->setAttribute('stocktake_quantity', $balance['quantity']);
            $inventory->setAttribute('stocktake_weight_kg', $balance['weight_kg']);
        }
    }

    private function hasCountedValue(array $row, string $key): bool
    {
        return array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '';
    }

    private function syncVariantStocks(Collection $variantIds): void
    {
        foreach ($variantIds as $variantId) {
            $total = (float) Inventory::query()
                ->where('product_variant_id', $variantId)
                ->sum('quantity');

            ProductVariant::query()->whereKey($variantId)->update(['stock' => $total]);
        }
    }

    /**
     * Quantity already removed from the picking shelf for completed packages,
     * but not exported yet because a shipper has not accepted the order.
     */
    private function packedReservationsByInventory(Collection $inventoryIds): Collection
    {
        if ($inventoryIds->isEmpty()) {
            return collect();
        }

        return InventoryReservation::query()
            ->join('order_items', 'order_items.id', '=', 'inventory_reservations.order_item_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('inventory_reservations.inventory_id', $inventoryIds->all())
            ->whereIn('orders.status', [Order::STATUS_PACKED, Order::STATUS_READY_TO_SHIP])
            ->whereNull('orders.trash_at')
            ->selectRaw('inventory_reservations.inventory_id, SUM(inventory_reservations.quantity) AS packed_quantity')
            ->groupBy('inventory_reservations.inventory_id')
            ->pluck('packed_quantity', 'inventory_reservations.inventory_id');
    }
}
