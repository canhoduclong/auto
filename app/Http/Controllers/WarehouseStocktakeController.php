<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryAdjustment;
use App\Models\InventoryMovement;
use App\Models\InventoryStocktake;
use App\Models\ProductVariant;
use App\Models\Warehouse;
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
        ]);

        $warehouse = $this->resolveWarehouse($request);
        $search = trim((string) $request->input('search', ''));

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
            'search'
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
            'items.*.expected_quantity' => ['required_with:items.*.counted_quantity', 'nullable', 'numeric', 'min:0'],
            'items.*.counted_quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        $countedRows = collect($validated['items'])
            ->filter(fn ($row) => array_key_exists('counted_quantity', $row) && $row['counted_quantity'] !== null && $row['counted_quantity'] !== '')
            ->mapWithKeys(fn ($row, $inventoryId) => [(int) $inventoryId => $row]);

        if ($countedRows->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Vui lòng nhập số lượng thực tế cho ít nhất một sản phẩm.',
            ]);
        }

        $stocktake = DB::transaction(function () use ($countedRows, $validated, $warehouse) {
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

            $this->guardUnchangedInventory($countedRows, $inventories);

            $stocktake = InventoryStocktake::create([
                'warehouse_id' => $warehouse->id,
                'counted_at' => $validated['counted_at'],
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
                $systemQuantity = round((float) $inventory->quantity, 3);
                $countedQuantity = round((float) $row['counted_quantity'], 3);
                $difference = round($countedQuantity - $systemQuantity, 3);

                $stocktake->items()->create([
                    'inventory_id' => $inventory->id,
                    'product_variant_id' => $inventory->product_variant_id,
                    'system_quantity' => $systemQuantity,
                    'counted_quantity' => $countedQuantity,
                    'difference' => $difference,
                ]);

                if (abs($difference) >= 0.001) {
                    InventoryAdjustment::create([
                        'inventory_id' => $inventory->id,
                        'quantity' => $difference,
                        'reason' => 'Kiểm kê kho '.$stocktake->code,
                        'user_id' => Auth::id(),
                    ]);
                    InventoryMovement::create([
                        'inventory_id' => $inventory->id,
                        'quantity' => $difference,
                        'type' => 'stocktake_adjustment',
                        'reference_id' => $stocktake->id,
                        'reference_type' => InventoryStocktake::class,
                        'user_id' => Auth::id(),
                    ]);
                }

                $inventory->update(['quantity' => $countedQuantity]);
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

    private function guardUnchangedInventory(Collection $countedRows, Collection $inventories): void
    {
        foreach ($countedRows as $inventoryId => $row) {
            $expected = round((float) $row['expected_quantity'], 3);
            $current = round((float) $inventories->get($inventoryId)->quantity, 3);

            if (abs($expected - $current) >= 0.001) {
                throw ValidationException::withMessages([
                    'items' => 'Tồn kho đã thay đổi trong lúc kiểm kê. Vui lòng tải lại trang và kiểm tra lại số thực tế.',
                ]);
            }
        }
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
