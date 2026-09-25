<?php

namespace App\Services;

use App\Models\GoogleSheetInventorySync;
use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\Warehouse;
use Illuminate\Support\Collection;

class GoogleSheetInventoryComparisonService
{
    /** @param array<string, mixed> $preview */
    public function compare(array $preview, Warehouse $warehouse, string $marker): array
    {
        $allSyncs = GoogleSheetInventorySync::query()
            ->with(['creator:id,name', 'importDocument:id,document_number'])
            ->where('warehouse_id', $warehouse->id)
            ->where('spreadsheet_id', $preview['spreadsheet_id'])
            ->where('sheet_id', $preview['sheet_id'])
            ->whereDate('inventory_date', $preview['selected_date'])
            ->orderBy('id')
            ->get();
        $syncs = $allSyncs->where('status', 'completed')->values();
        $lastSync = $syncs->last();
        $allDocuments = InventoryDocument::query()
            ->with('items:id,inventory_document_id,product_variant_id,quantity')
            ->where('warehouse_id', $warehouse->id)
            ->where('type', 'import')
            ->where('notes', 'like', '%'.$marker.'%')
            ->orderBy('id')
            ->get();
        $legacyDocuments = $allDocuments
            ->reject(fn (InventoryDocument $document): bool => str_contains(
                (string) $document->notes,
                '[google_sheet_inventory_sync:'
            ))
            ->values();

        $baseline = collect($lastSync?->snapshot ?? []);
        $baselineSource = $lastSync ? 'sync' : null;
        if (! $lastSync && $legacyDocuments->isNotEmpty()) {
            $baseline = $legacyDocuments->last()->items
                ->groupBy('product_variant_id')
                ->map(fn (Collection $items) => (float) $items->sum('quantity'));
            $baselineSource = 'legacy_document';
        }

        $variantIds = $preview['rows']->pluck('variant_id')->filter()->map(fn ($id) => (int) $id)->unique();
        $inventories = Inventory::query()
            ->where('warehouse_id', $warehouse->id)
            ->whereIn('product_variant_id', $variantIds->all())
            ->get()
            ->keyBy('product_variant_id');

        $rows = $preview['rows']->map(function (array $row) use ($baseline, $inventories): array {
            $variantId = (int) ($row['variant_id'] ?? 0);
            $hasBaseline = $variantId > 0 && $baseline->has((string) $variantId);
            if (! $hasBaseline && $variantId > 0) {
                $hasBaseline = $baseline->has($variantId);
            }
            $previous = $hasBaseline ? (float) ($baseline->get((string) $variantId) ?? $baseline->get($variantId)) : 0.0;
            $sheetQuantity = (float) $row['quantity'];
            $delta = round($sheetQuantity - $previous, 3);
            $inventory = $variantId > 0 ? $inventories->get($variantId) : null;
            $currentQuantity = (float) ($inventory?->quantity ?? 0);
            $reservedQuantity = (float) ($inventory?->reserved_quantity ?? 0);
            $projectedQuantity = round($currentQuantity + $delta, 3);
            $canApply = $variantId > 0 && ($delta >= 0 || $projectedQuantity >= $reservedQuantity);

            $row['previous_sheet_quantity'] = $previous;
            $row['has_previous_snapshot'] = $hasBaseline;
            $row['delta'] = $delta;
            $row['change_type'] = match (true) {
                abs($delta) < 0.001 => 'unchanged',
                ! $hasBaseline && $sheetQuantity > 0 => 'new',
                $delta > 0 => 'increase',
                default => 'decrease',
            };
            $row['current_quantity'] = $currentQuantity;
            $row['reserved_quantity'] = $reservedQuantity;
            $row['projected_quantity'] = $projectedQuantity;
            $row['can_apply'] = $canApply;
            $row['apply_error'] = ! $canApply && $delta < 0
                ? 'Không thể giảm vì tồn sau điều chỉnh thấp hơn số lượng đang giữ chỗ ('.number_format($reservedQuantity, 0, ',', '.').').'
                : null;

            return $row;
        });
        $changedRows = $rows
            ->filter(fn (array $row) => $row['matched'] && abs((float) $row['delta']) >= 0.001)
            ->values();

        return [
            'rows' => $rows,
            'changed_rows' => $changedRows,
            'applicable_rows' => $changedRows->where('can_apply', true)->values(),
            'new_count' => $changedRows->where('change_type', 'new')->count(),
            'increase_count' => $changedRows->where('change_type', 'increase')->count(),
            'decrease_count' => $changedRows->where('change_type', 'decrease')->count(),
            'unchanged_count' => $rows->where('matched', true)->where('change_type', 'unchanged')->count(),
            'positive_delta' => (float) $changedRows->where('delta', '>', 0)->sum('delta'),
            'negative_delta' => abs((float) $changedRows->where('delta', '<', 0)->sum('delta')),
            'baseline' => $baseline,
            'baseline_source' => $baselineSource,
            'has_previous' => $lastSync !== null || $legacyDocuments->isNotEmpty(),
            'syncs' => $allSyncs,
            'legacy_documents' => $legacyDocuments,
            'import_documents' => $allDocuments,
            'next_sync_number' => max(
                (int) $allSyncs->max('sync_number'),
                $legacyDocuments->count()
            ) + 1,
        ];
    }
}
