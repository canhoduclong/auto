<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WarehouseInventorySummaryService
{
    public function buildConsolidated(string $date, string $search = '', ?string $status = null): array
    {
        $selectedDate = Carbon::parse($date)->toDateString();
        $dayStart = Carbon::parse($selectedDate)->startOfDay();
        $dayEnd = Carbon::parse($selectedDate)->endOfDay();
        $isToday = $selectedDate === Carbon::today()->toDateString();

        $warehouses = Warehouse::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $products = Product::query()
            ->with([
                'variants' => function ($query) use ($dayStart, $dayEnd) {
                    $query->with([
                        'product',
                        'inventories.warehouse:id,name',
                        'inventories.movements' => fn ($movementQuery) => $movementQuery
                            ->whereBetween('created_at', [$dayStart, $dayEnd]),
                    ])
                        ->where('status', true)
                        ->orderBy('sort_order')
                        ->orderBy('id');
                },
            ])
            ->whereHas('variants', fn ($variantQuery) => $variantQuery->where('status', true))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhereHas('variants', fn ($variantQuery) => $variantQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%"));
                });
            })
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get();

        $inventoryIds = $products->flatMap(fn ($product) => $product->variants)
            ->flatMap(fn ($variant) => $variant->inventories)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $movementsAfterDate = $inventoryIds->isEmpty()
            ? collect()
            : InventoryMovement::query()
                ->whereIn('inventory_id', $inventoryIds->all())
                ->where('created_at', '>', $dayEnd)
                ->selectRaw('inventory_id, COALESCE(SUM(quantity), 0) as quantity')
                ->groupBy('inventory_id')
                ->pluck('quantity', 'inventory_id');

        $emptyWarehouseValues = fn () => $warehouses->mapWithKeys(fn ($warehouse) => [
            (string) $warehouse->id => [
                'opening' => 0,
                'import' => 0,
                'export' => 0,
                'closing' => 0,
            ],
        ])->all();

        $rows = $products->map(function ($product) use ($warehouses, $movementsAfterDate, $isToday, $emptyWarehouseValues) {
            $variantRows = $product->variants
                ->map(function ($variant) use ($warehouses, $movementsAfterDate, $isToday, $emptyWarehouseValues) {
                    $warehouseValues = $emptyWarehouseValues();
                    $book = 0;

                    foreach ($variant->inventories as $inventory) {
                        $warehouseId = (string) $inventory->warehouse_id;
                        if (!array_key_exists($warehouseId, $warehouseValues)) {
                            continue;
                        }

                        $import = (int) $inventory->movements->where('quantity', '>', 0)->sum('quantity');
                        $export = abs((int) $inventory->movements->where('quantity', '<', 0)->sum('quantity'));
                        $closing = (int) $inventory->quantity - (int) ($movementsAfterDate[$inventory->id] ?? 0);

                        $warehouseValues[$warehouseId] = [
                            'opening' => $closing - $import + $export,
                            'import' => $import,
                            'export' => $export,
                            'closing' => $closing,
                        ];
                        $book += $isToday ? (int) $inventory->reserved_quantity : 0;
                    }

                    $totalClosing = (int) collect($warehouseValues)->sum('closing');
                    $totalExport = (int) collect($warehouseValues)->sum('export');

                    return [
                        'variant_id' => (int) $variant->id,
                        'name' => (string) ($variant->name ?: 'Mặc định'),
                        'sku' => (string) ($variant->sku ?: '—'),
                        'unit' => (string) ($variant->product?->unit_label ?? '—'),
                        'warehouses' => $warehouseValues,
                        'available' => max(0, $totalClosing - $book),
                        'book' => $book,
                        'total_export' => $totalExport,
                        'total_closing' => $totalClosing,
                    ];
                })
                ->values();

            $warehouseValues = $emptyWarehouseValues();
            foreach ($warehouses as $warehouse) {
                foreach (['opening', 'import', 'export', 'closing'] as $field) {
                    $warehouseValues[(string) $warehouse->id][$field] = (int) $variantRows->sum(
                        fn ($row) => $row['warehouses'][(string) $warehouse->id][$field]
                    );
                }
            }

            $units = $variantRows->pluck('unit')->filter(fn ($unit) => $unit !== '—')->unique()->values();

            return [
                'product_id' => (int) $product->id,
                'name' => (string) $product->name,
                'unit' => $units->count() === 1 ? (string) $units->first() : ($units->count() > 1 ? 'Nhiều DVT' : '—'),
                'warehouses' => $warehouseValues,
                'available' => (int) $variantRows->sum('available'),
                'book' => (int) $variantRows->sum('book'),
                'total_export' => (int) $variantRows->sum('total_export'),
                'total_closing' => (int) $variantRows->sum('total_closing'),
                'variants' => $variantRows,
            ];
        })
            ->when($status === 'out_of_stock', fn ($items) => $items->filter(fn ($row) => $row['total_closing'] <= 0))
            ->when($status === 'low_stock', fn ($items) => $items->filter(fn ($row) => $row['total_closing'] > 0 && $row['total_closing'] <= 5))
            ->values();

        $totals = [
            'warehouses' => $emptyWarehouseValues(),
            'available' => (int) $rows->sum('available'),
            'book' => (int) $rows->sum('book'),
            'total_export' => (int) $rows->sum('total_export'),
            'total_closing' => (int) $rows->sum('total_closing'),
        ];
        foreach ($warehouses as $warehouse) {
            foreach (['opening', 'import', 'export', 'closing'] as $field) {
                $totals['warehouses'][(string) $warehouse->id][$field] = (int) $rows->sum(
                    fn ($row) => $row['warehouses'][(string) $warehouse->id][$field]
                );
            }
        }

        return compact('warehouses', 'rows', 'totals', 'selectedDate');
    }

    public function build(?int $warehouseId, ?string $date = null): array
    {
        $selectedDate = Carbon::parse($date ?: Carbon::today())->toDateString();
        $dayStart = Carbon::parse($selectedDate)->startOfDay();
        $dayEnd = Carbon::parse($selectedDate)->endOfDay();
        $isToday = $selectedDate === Carbon::today()->toDateString();

        $products = Product::with([
            'variants.inventories' => function ($query) use ($warehouseId, $dayStart, $dayEnd) {
                if ($warehouseId) {
                    $query->where('warehouse_id', $warehouseId);
                }

                $query->with([
                    'movements' => fn ($movementQuery) => $movementQuery
                        ->whereBetween('created_at', [$dayStart, $dayEnd]),
                ]);
            },
            'variants.product',
        ])
            ->orderBy('name')
            ->get();

        $inventoryIds = $products->flatMap(fn ($product) => $product->variants)
            ->flatMap(fn ($variant) => $variant->inventories)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $movementsAfterDate = $inventoryIds->isEmpty()
            ? collect()
            : InventoryMovement::query()
                ->whereIn('inventory_id', $inventoryIds->all())
                ->where('created_at', '>', $dayEnd)
                ->selectRaw('inventory_id, COALESCE(SUM(quantity), 0) as quantity')
                ->groupBy('inventory_id')
                ->pluck('quantity', 'inventory_id');

        $rows = $products->map(function ($product) use ($movementsAfterDate, $isToday) {
            $variants = $product->variants
                ->filter(fn ($variant) => $variant->inventories->isNotEmpty())
                ->sortBy(fn ($variant) => mb_strtolower((string) ($variant->name ?? '')))
                ->values();

            $variantRows = $variants->map(function ($variant) use ($movementsAfterDate, $isToday) {
                $closing = (float) $variant->inventories->sum(
                    fn ($inventory) => (float) $inventory->quantity - (float) ($movementsAfterDate[$inventory->id] ?? 0)
                );
                $import = (float) $variant->inventories->sum(
                    fn ($inventory) => $inventory->movements->where('quantity', '>', 0)->sum('quantity')
                );
                $reserved = $isToday ? (float) $variant->inventories->sum('reserved_quantity') : 0;
                $export = (float) abs($variant->inventories->sum(
                    fn ($inventory) => $inventory->movements->where('quantity', '<', 0)->sum('quantity')
                ));

                return [
                    'name' => (string) ($variant->name ?: ($variant->product?->name ?? 'Biến thể')),
                    'unit' => (string) ($variant->product?->unit_label ?? '—'),
                    'opening' => $closing - $import + $export,
                    'import' => $import,
                    'reserved' => $reserved,
                    'available' => max(0, $closing - $reserved),
                    'export' => $export,
                    'closing' => $closing,
                ];
            })->values();

            $unitLabels = $variantRows
                ->pluck('unit')
                ->filter(fn ($unit) => $unit !== '—' && $unit !== '')
                ->unique()
                ->values();

            return [
                'product_id' => (int) $product->id,
                'name' => (string) $product->name,
                'unit' => $unitLabels->count() === 1
                    ? (string) $unitLabels->first()
                    : ($unitLabels->count() > 1 ? 'Nhiều DVT' : '—'),
                'variant_count' => (int) $variants->count(),
                'opening' => (float) $variantRows->sum('opening'),
                'import' => (float) $variantRows->sum('import'),
                'reserved' => (float) $variantRows->sum('reserved'),
                'available' => (float) $variantRows->sum('available'),
                'export' => (float) $variantRows->sum('export'),
                'closing' => (float) $variantRows->sum('closing'),
                'variants' => $variantRows,
            ];
        })->sortBy(fn ($row) => mb_strtolower((string) $row['name']))->values();

        return [
            'rows' => $rows,
            'totals' => $this->totals($rows),
            'selectedDate' => $selectedDate,
        ];
    }

    private function totals(Collection $rows): array
    {
        return [
            'opening' => (float) $rows->sum('opening'),
            'import' => (float) $rows->sum('import'),
            'reserved' => (float) $rows->sum('reserved'),
            'available' => (float) $rows->sum('available'),
            'export' => (float) $rows->sum('export'),
            'closing' => (float) $rows->sum('closing'),
        ];
    }
}
