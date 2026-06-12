<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class WarehouseInventorySummaryService
{
    public function build(?int $warehouseId): array
    {
        $products = Product::with([
            'variants.inventories' => function ($query) use ($warehouseId) {
                if ($warehouseId) {
                    $query->where('warehouse_id', $warehouseId);
                }

                $query->with('movements');
            },
            'variants.product',
        ])
            ->orderBy('name')
            ->get();

        $rows = $products->map(function ($product) {
            $variants = $product->variants
                ->filter(fn ($variant) => $variant->inventories->isNotEmpty())
                ->sortBy(fn ($variant) => mb_strtolower((string) ($variant->name ?? '')))
                ->values();

            $variantRows = $variants->map(function ($variant) {
                $closing = (int) $variant->inventories->sum('quantity');
                $import = (int) $variant->inventories->sum(
                    fn ($inventory) => $inventory->movements->where('quantity', '>', 0)->sum('quantity')
                );
                $reserved = (int) $variant->inventories->sum('reserved_quantity');
                $export = (int) abs($variant->inventories->sum(
                    fn ($inventory) => $inventory->movements->where('quantity', '<', 0)->sum('quantity')
                ));

                return [
                    'name' => (string) ($variant->name ?: ($variant->product?->name ?? 'Biến thể')),
                    'unit' => (string) ($variant->product?->unit_label ?? '—'),
                    'opening' => $closing - $import + $export,
                    'import' => $import,
                    'reserved' => $reserved,
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
                'opening' => (int) $variantRows->sum('opening'),
                'import' => (int) $variantRows->sum('import'),
                'reserved' => (int) $variantRows->sum('reserved'),
                'export' => (int) $variantRows->sum('export'),
                'closing' => (int) $variantRows->sum('closing'),
                'variants' => $variantRows,
            ];
        })->sortBy(fn ($row) => mb_strtolower((string) $row['name']))->values();

        return [
            'rows' => $rows,
            'totals' => $this->totals($rows),
        ];
    }

    private function totals(Collection $rows): array
    {
        return [
            'opening' => (int) $rows->sum('opening'),
            'import' => (int) $rows->sum('import'),
            'reserved' => (int) $rows->sum('reserved'),
            'export' => (int) $rows->sum('export'),
            'closing' => (int) $rows->sum('closing'),
        ];
    }
}
