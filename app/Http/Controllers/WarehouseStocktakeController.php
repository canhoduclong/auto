<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryAdjustment;
use App\Models\InventoryMovement;
use App\Models\InventoryStocktake;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WarehouseStocktakeController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'counted_at' => ['nullable', 'date', 'before_or_equal:now'],
        ]);

        $warehouse = $this->resolveWarehouse($request);
        $search = trim((string) $request->input('search', ''));
        $countedAt = $request->filled('counted_at')
            ? Carbon::parse($request->input('counted_at'))
            : now();

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

        $recentStocktakes = InventoryStocktake::query()
            ->with(['creator:id,name', 'items.productVariant.product:id,name'])
            ->withCount('items')
            ->where('warehouse_id', $warehouse->id)
            ->orderByDesc('counted_at')
            ->limit(10)
            ->get();

        $warehouses = Auth::user()?->hasRole('admin')
            ? Warehouse::query()->where('status', true)->orderBy('name')->get(['id', 'name'])
            : collect([$warehouse]);

        return view('warehouse.stocktakes.index', compact(
            'warehouse',
            'warehouses',
            'inventories',
            'recentStocktakes',
            'search',
            'countedAt'
        ));
    }

    public function store(Request $request)
    {
        $warehouse = $this->resolveWarehouse($request);
        $validated = $request->validate([
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'counted_at' => ['required', 'date', 'before_or_equal:now'],
            'note' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array'],
            'items.*.expected_quantity' => ['required', 'numeric', 'min:0'],
            'items.*.expected_weight_kg' => ['required', 'numeric', 'min:0'],
            'items.*.counted_quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.counted_weight_kg' => ['nullable', 'numeric', 'min:0'],
        ]);

        $countedRows = collect($validated['items'])
            ->filter(fn ($row) => $this->hasCountedValue($row, 'counted_quantity')
                || $this->hasCountedValue($row, 'counted_weight_kg'))
            ->mapWithKeys(function ($row, $inventoryId) {
                $row['counted_quantity'] = $this->hasCountedValue($row, 'counted_quantity')
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

        $stocktake = DB::transaction(function () use ($countedRows, $validated, $warehouse, $countedAt) {
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

            $stocktake = InventoryStocktake::create([
                'warehouse_id' => $warehouse->id,
                'counted_at' => $countedAt,
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
                $countedQuantity = round((float) $row['counted_quantity'], 3);
                $difference = round($countedQuantity - $systemQuantity, 3);
                $systemWeight = round((float) $balanceAtCount['weight_kg'], 3);
                $countedWeight = round((float) $row['counted_weight_kg'], 3);
                $weightDifference = round($countedWeight - $systemWeight, 3);

                $stocktake->items()->create([
                    'inventory_id' => $inventory->id,
                    'product_variant_id' => $inventory->product_variant_id,
                    'system_quantity' => $systemQuantity,
                    'counted_quantity' => $countedQuantity,
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
                        'reason' => 'Kiểm kê kho '.$stocktake->code,
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

        return redirect()->route('warehouse.stocktakes.index', ['warehouse_id' => $warehouse->id])
            ->with('success', 'Đã hoàn tất phiếu kiểm kê '.$stocktake->code.' và cập nhật tồn kho.');
    }

    private function resolveWarehouse(Request $request): Warehouse
    {
        $user = Auth::user();
        $assignedWarehouseId = (int) ($user?->warehouse_id ?? 0);

        if ($assignedWarehouseId > 0) {
            return Warehouse::query()->findOrFail($assignedWarehouseId);
        }

        abort_unless($user?->hasRole('admin'), 403, 'Tài khoản chưa được gán kho quản lý.');

        $warehouseId = (int) $request->input('warehouse_id', 0);
        $warehouse = $warehouseId > 0
            ? Warehouse::query()->find($warehouseId)
            : Warehouse::query()->where('status', true)->orderBy('name')->first();

        abort_unless($warehouse, 404, 'Không có kho để kiểm kê.');

        return $warehouse;
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
}
