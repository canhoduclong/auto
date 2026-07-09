<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\CuttingComponentImportRequest;
use App\Models\InventoryDocument;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductComponentRatio;
use App\Models\ProductCuttingBatch;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductCuttingService
{
    public function missingCutProducts(?int $warehouseId): Collection
    {
        $cutVariants = ProductVariant::query()
            ->with(['product', 'inventories' => fn ($query) => $query->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))])
            ->whereHas('product', fn ($query) => $query->where('product_type', Product::TYPE_CUT))
            ->where('status', true)
            ->get();

        $demandByVariant = $this->openDemandByVariant($warehouseId);

        return $cutVariants->map(function (ProductVariant $variant) use ($demandByVariant) {
            $current = (float) $variant->inventories->sum(fn (Inventory $inventory) => max(0, (float) $inventory->quantity - (float) $inventory->reserved_quantity));
            $minimum = (float) $variant->inventories->max('low_stock_threshold');
            $demand = max((float) ($demandByVariant[$variant->id] ?? 0), $minimum);
            $shortage = max(0, $demand - $current);

            return [
                'variant_id' => (int) $variant->id,
                'product_id' => (int) $variant->product_id,
                'name' => trim(($variant->product?->name ?? 'Sản phẩm') . ' ' . ($variant->name ?: '')),
                'current_stock' => $current,
                'demand' => $demand,
                'shortage' => $shortage,
                'unit' => (string) ($variant->product?->weight_unit_label ?? $variant->product?->unit_label ?? 'Kg'),
            ];
        })
            ->filter(fn (array $row) => $row['shortage'] > 0)
            ->sortByDesc('shortage')
            ->values();
    }

    public function sourceMaterials(ProductVariant $targetVariant, ?int $warehouseId): Collection
    {
        return $this->sourceMaterialRows($targetVariant, $warehouseId)
            ->filter(fn (array $row) => $row['output_component_ids']->isNotEmpty())
            ->values();
    }

    public function sourceMaterialOptions(ProductVariant $targetVariant, ?int $warehouseId): Collection
    {
        return $this->sourceMaterialRows($targetVariant, $warehouseId)
            ->filter(fn (array $row) => collect($row['components'] ?? [])->isNotEmpty() && (float) ($row['available'] ?? 0) > 0)
            ->map(function (array $material) use ($targetVariant) {
                $singlePreview = $this->preview($targetVariant, [
                    ['variant_id' => (int) $material['variant_id'], 'quantity' => 1],
                ]);

                $material['output_per_unit'] = (float) ($singlePreview['finished_weight'] ?? 0);

                return $material;
            })
            ->values();
    }

    private function sourceMaterialRows(ProductVariant $targetVariant, ?int $warehouseId): Collection
    {
        $targetVariant->loadMissing('product');
        $removedNames = $this->removedComponentNames($targetVariant);
        $targetVariantId = (int) $targetVariant->id;

        return ProductVariant::query()
            ->with([
                'product',
                'inventories' => fn ($query) => $query->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId)),
                'componentRatios.componentVariant.product',
            ])
            ->whereHas('product', fn ($query) => $query
                ->whereNull('product_type')
                ->orWhere('product_type', '!=', Product::TYPE_CUT))
            ->where('status', true)
            ->orderBy('product_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (ProductVariant $variant) use ($removedNames, $targetVariantId) {
                $available = (float) $variant->inventories->sum(fn (Inventory $inventory) => max(0, (float) $inventory->quantity - (float) $inventory->reserved_quantity));
                $unitWeight = (float) $variant->effective_kg;
                $components = $this->componentRowsForVariant($variant);
                $targetComponentIds = $components
                    ->filter(fn (array $component) => (int) ($component['variant_id'] ?? 0) === $targetVariantId)
                    ->pluck('variant_id')
                    ->values();
                $removedComponentIds = $components
                    ->filter(fn (array $component) => $this->matchesRemovedComponent($component['name'], $removedNames))
                    ->pluck('variant_id')
                    ->values();

                return [
                    'variant_id' => (int) $variant->id,
                    'label' => trim(($variant->product?->name ?? 'Sản phẩm') . ' ' . ($variant->name ?: '')),
                    'size' => (float) ($variant->kg ?: $unitWeight),
                    'available' => $available,
                    'unit_weight' => $unitWeight,
                    'components' => $components,
                    'target_component_ids' => $targetComponentIds,
                    'removed_component_ids' => $removedComponentIds,
                    'output_component_ids' => $targetComponentIds->isNotEmpty()
                        ? $targetComponentIds
                        : $removedComponentIds,
                ];
            });
    }

    public function planForDemand(ProductVariant $targetVariant, ?int $warehouseId, float $demand): array
    {
        $targetVariant->loadMissing('product');

        $materials = $this->sourceMaterials($targetVariant, $warehouseId)
            ->map(function (array $material) use ($targetVariant) {
                $singlePreview = $this->preview($targetVariant, [
                    ['variant_id' => (int) $material['variant_id'], 'quantity' => 1],
                ]);

                $material['output_per_unit'] = (float) ($singlePreview['finished_weight'] ?? 0);
                $material['suggested_quantity'] = 0;

                return $material;
            })
            ->filter(fn (array $material) => (float) ($material['output_per_unit'] ?? 0) > 0)
            ->values()
            ->all();

        $remainingDemand = max(0, $demand);
        $selectedMaterials = [];

        foreach ($materials as $index => $material) {
            if ($remainingDemand <= 0) {
                break;
            }

            $availableUnits = (int) floor((float) ($material['available'] ?? 0));
            $outputPerUnit = (float) ($material['output_per_unit'] ?? 0);
            if ($availableUnits <= 0 || $outputPerUnit <= 0) {
                continue;
            }

            $quantity = min($availableUnits, (int) ceil($remainingDemand / $outputPerUnit));
            if ($quantity <= 0) {
                continue;
            }

            $materials[$index]['suggested_quantity'] = $quantity;
            $selectedMaterials[] = [
                'variant_id' => (int) $material['variant_id'],
                'quantity' => $quantity,
            ];
            $remainingDemand -= $quantity * $outputPerUnit;
        }

        $preview = $this->preview($targetVariant, $selectedMaterials);

        return [
            'target_variant_id' => (int) $targetVariant->id,
            'target_name' => $this->componentName($targetVariant),
            'demand' => round(max(0, $demand), 3),
            'materials' => collect($materials)->values(),
            'selected_materials' => collect($selectedMaterials)->keyBy('variant_id')->all(),
            'preview' => $preview,
            'can_execute' => !empty($selectedMaterials) && (float) ($preview['finished_weight'] ?? 0) > 0,
        ];
    }

    public function preview(ProductVariant $targetVariant, array $materials): array
    {
        $sourceVariants = ProductVariant::query()
            ->with(['product', 'componentRatios.componentVariant.product'])
            ->whereIn('id', collect($materials)->pluck('variant_id')->all())
            ->get()
            ->keyBy('id');

        $targetVariant->loadMissing('product');
        $removedNames = $this->removedComponentNames($targetVariant);
        $targetVariantId = (int) $targetVariant->id;
        $totalInputWeight = 0.0;
        $targetOutputWeight = 0.0;
        $legacyRemovedWeight = 0.0;
        $usesDirectTargetComponent = false;
        $plannedComponents = [];

        foreach ($materials as $material) {
            $variant = $sourceVariants->get((int) $material['variant_id']);
            $quantity = max(0, (float) ($material['quantity'] ?? 0));
            if (!$variant || $quantity <= 0) {
                continue;
            }

            $totalInputWeight += $quantity * (float) $variant->effective_kg;
            $componentRows = $this->componentRowsForVariant($variant);
            $targetRows = $componentRows
                ->filter(fn (array $component) => (int) ($component['variant_id'] ?? 0) === $targetVariantId);

            if ($targetRows->isNotEmpty()) {
                $usesDirectTargetComponent = true;

                foreach ($componentRows as $component) {
                    $componentWeight = $quantity * (float) $component['standard_weight'];
                    if ((int) $component['variant_id'] === $targetVariantId) {
                        $targetOutputWeight += $componentWeight;
                        continue;
                    }

                    $this->addPlannedComponentRow($plannedComponents, $component, $componentWeight);
                }

                continue;
            }

            foreach ($componentRows as $component) {
                if (!$this->matchesRemovedComponent((string) $component['name'], $removedNames)) {
                    continue;
                }

                $componentWeight = $quantity * (float) $component['standard_weight'];
                $legacyRemovedWeight += $componentWeight;
                $this->addPlannedComponentRow($plannedComponents, $component, $componentWeight);
            }
        }

        $components = collect($plannedComponents)->map(function (array $row) {
            $row['weight'] = round((float) $row['weight'], 3);
            return $row;
        })->values();

        return [
            'input_weight' => round($totalInputWeight, 3),
            'finished_weight' => round(max(0, $usesDirectTargetComponent ? $targetOutputWeight : $totalInputWeight - $legacyRemovedWeight), 3),
            'components' => $components,
        ];
    }

    private function componentRowsForVariant(ProductVariant $variant): Collection
    {
        $variant->loadMissing(['product', 'componentRatios.componentVariant.product']);
        $ratios = $variant->componentRatios;
        $scale = 1.0;

        if ($ratios->isEmpty()) {
            $templateVariant = ProductVariant::query()
                ->with(['componentRatios.componentVariant.product'])
                ->where('product_id', $variant->product_id)
                ->where('id', '!=', $variant->id)
                ->whereHas('componentRatios')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first();

            if (!$templateVariant) {
                return collect();
            }

            $templateWeight = (float) $templateVariant->effective_kg;
            $currentWeight = (float) $variant->effective_kg;
            $scale = $templateWeight > 0 && $currentWeight > 0 ? $currentWeight / $templateWeight : 1.0;
            $ratios = $templateVariant->componentRatios;
        }

        return $ratios->map(function (ProductComponentRatio $ratio) use ($scale) {
            return [
                'variant_id' => (int) $ratio->component_product_variant_id,
                'name' => $this->componentName($ratio->componentVariant),
                'standard_weight' => round((float) $ratio->standard_weight * $scale, 3),
                'percentage' => (float) $ratio->percentage,
            ];
        })->values();
    }

    private function addPlannedComponentRow(array &$plannedComponents, array $component, float $componentWeight): void
    {
        if ($componentWeight <= 0) {
            return;
        }

        $componentId = (int) $component['variant_id'];
        $plannedComponents[$componentId] ??= [
            'variant_id' => $componentId,
            'name' => (string) $component['name'],
            'weight' => 0.0,
        ];
        $plannedComponents[$componentId]['weight'] += $componentWeight;
    }

    public function execute(
        int $warehouseId,
        ProductVariant $targetVariant,
        array $materials,
        float $actualFinishedWeight,
        array $actualComponents,
        string $note,
        int $userId,
        bool $deferComponents = false,
        ?int $orderId = null
    ): ProductCuttingBatch
    {
        return DB::transaction(function () use ($warehouseId, $targetVariant, $materials, $actualFinishedWeight, $actualComponents, $note, $userId, $deferComponents, $orderId) {
            $preview = $this->preview($targetVariant, $materials);

            $exportDocument = InventoryDocument::create([
                'type' => 'export',
                'warehouse_id' => $warehouseId,
                'document_date' => now()->toDateString(),
                'notes' => trim($note) !== '' ? $note : 'Xuất kho để thực hiện pha lóc.',
                'shipping_fee' => 0,
                'user_id' => $userId,
            ]);

            foreach ($materials as $material) {
                $variantId = (int) $material['variant_id'];
                $quantity = max(0, (float) ($material['quantity'] ?? 0));
                if ($quantity <= 0) {
                    continue;
                }

                $inventory = Inventory::query()
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_variant_id', $variantId)
                    ->lockForUpdate()
                    ->first();

                $available = $inventory ? max(0, (float) $inventory->quantity - (float) $inventory->reserved_quantity) : 0;
                if (!$inventory || $available < $quantity) {
                    $variant = ProductVariant::query()->find($variantId);
                    throw new \RuntimeException('Không đủ tồn kho cho ' . ($variant?->name ?? ('biến thể #' . $variantId)) . '.');
                }

                $exportDocument->items()->create(['product_variant_id' => $variantId, 'quantity' => $quantity, 'unit_cost' => 0]);
                InventoryMovement::create([
                    'inventory_id' => $inventory->id,
                    'quantity' => -$quantity,
                    'type' => 'cutting_out',
                    'reference_id' => $exportDocument->id,
                    'reference_type' => InventoryDocument::class,
                    'user_id' => $userId,
                ]);
                $inventory->decrement('quantity', $quantity);
                $this->syncVariantStock($variantId);
            }

            $finishedDocument = $this->importRows($warehouseId, [
                ['variant_id' => (int) $targetVariant->id, 'quantity' => $actualFinishedWeight],
            ], 'Nhập kho thành phẩm pha lóc.', $userId);

            $componentRows = collect($actualComponents)
                ->map(fn ($row) => ['variant_id' => (int) ($row['variant_id'] ?? 0), 'quantity' => max(0, (float) ($row['weight'] ?? 0))])
                ->filter(fn ($row) => $row['variant_id'] > 0 && $row['quantity'] > 0)
                ->values()
                ->all();
            $inputWeight = (float) ($preview['input_weight'] ?? 0);
            $actualComponentWeight = round((float) collect($componentRows)->sum('quantity'), 3);
            $lossWeight = round(max(0, $inputWeight - $actualFinishedWeight - $actualComponentWeight), 3);
            $lossPercent = $inputWeight > 0 ? round($lossWeight / $inputWeight * 100, 3) : 0;
            $componentDocument = $deferComponents
                ? null
                : $this->importRows($warehouseId, $componentRows, 'Nhập kho thành phần phát sinh từ pha lóc.', $userId);

            $batch = ProductCuttingBatch::create([
                'warehouse_id' => $warehouseId,
                'target_product_variant_id' => (int) $targetVariant->id,
                'performed_by' => $userId,
                'export_document_id' => $exportDocument->id,
                'finished_import_document_id' => $finishedDocument->id,
                'component_import_document_id' => $componentDocument?->id,
                'input_weight' => $inputWeight,
                'planned_finished_weight' => (float) $preview['finished_weight'],
                'actual_finished_weight' => $actualFinishedWeight,
                'actual_component_weight' => $actualComponentWeight,
                'loss_weight' => $lossWeight,
                'loss_percent' => $lossPercent,
                'planned_components' => $preview['components']->all(),
                'actual_components' => $componentRows,
                'note' => $note,
            ]);

            if ($deferComponents && !empty($componentRows)) {
                $this->appendDeferredComponentImportRequest($warehouseId, $componentRows, $batch, $userId, $orderId);
            }

            return $batch;
        });
    }

    private function appendDeferredComponentImportRequest(int $warehouseId, array $componentRows, ProductCuttingBatch $batch, int $userId, ?int $orderId = null): void
    {
        $order = $orderId ? Order::query()->find($orderId) : null;
        $request = CuttingComponentImportRequest::query()->firstOrCreate(
            [
                'warehouse_id' => $warehouseId,
                'request_date' => now()->toDateString(),
                'status' => CuttingComponentImportRequest::STATUS_OPEN,
            ],
            [
                'created_by' => $userId,
                'note' => 'Yêu cầu nhập kho thành phần còn lại từ pha lóc trong ngày.',
            ]
        );

        foreach ($componentRows as $row) {
            $quantity = round(max(0, (float) ($row['quantity'] ?? 0)), 3);
            $variantId = (int) ($row['variant_id'] ?? 0);
            if ($variantId <= 0 || $quantity <= 0) {
                continue;
            }

            $request->items()->create([
                'cutting_batch_id' => (int) $batch->id,
                'order_id' => $order?->id,
                'product_variant_id' => $variantId,
                'quantity' => $quantity,
                'source_order_code' => $order?->code,
            ]);
        }
    }

    private function importRows(int $warehouseId, array $rows, string $note, int $userId): ?InventoryDocument
    {
        if (empty($rows)) {
            return null;
        }

        $document = InventoryDocument::create([
            'type' => 'import',
            'warehouse_id' => $warehouseId,
            'document_date' => now()->toDateString(),
            'notes' => $note,
            'shipping_fee' => 0,
            'user_id' => $userId,
        ]);

        foreach ($rows as $row) {
            $variantId = (int) $row['variant_id'];
            $quantity = max(0, (float) $row['quantity']);
            if ($variantId <= 0 || $quantity <= 0) {
                continue;
            }

            $document->items()->create(['product_variant_id' => $variantId, 'quantity' => $quantity, 'unit_cost' => 0]);
            $inventory = Inventory::query()->firstOrCreate(
                ['warehouse_id' => $warehouseId, 'product_variant_id' => $variantId],
                ['quantity' => 0, 'reserved_quantity' => 0, 'low_stock_threshold' => 10]
            );
            InventoryMovement::create([
                'inventory_id' => $inventory->id,
                'quantity' => $quantity,
                'type' => 'cutting_in',
                'reference_id' => $document->id,
                'reference_type' => InventoryDocument::class,
                'user_id' => $userId,
            ]);
            $inventory->increment('quantity', $quantity);
            $this->syncVariantStock($variantId);
        }

        return $document;
    }

    private function openDemandByVariant(?int $warehouseId): Collection
    {
        return Order::query()
            ->with('items')
            ->whereIn('status', ['approved', Order::STATUS_READY_TO_PACK, Order::STATUS_PACKING])
            ->when($warehouseId, fn ($query) => $query->where(function ($scope) use ($warehouseId) {
                $scope->where('warehouse_id', $warehouseId)->orWhereNull('warehouse_id');
            }))
            ->get()
            ->flatMap->items
            ->groupBy('product_variant_id')
            ->map(fn ($items) => (float) $items->sum('quantity'));
    }

    private function removedComponentNames(ProductVariant $targetVariant): array
    {
        $name = mb_strtolower($targetVariant->product?->name . ' ' . $targetVariant->name);
        if (preg_match('/không\s+(.+)$/u', $name, $matches) !== 1) {
            return [];
        }

        return preg_split('/[\s,\/\-\+]+/u', trim($matches[1])) ?: [];
    }

    private function componentName(?ProductVariant $variant): string
    {
        return trim(($variant?->product?->name ?? '') . ' ' . ($variant?->name ?: ''));
    }

    private function matchesRemovedComponent(string $componentName, array $removedNames): bool
    {
        $componentName = mb_strtolower($componentName);
        foreach ($removedNames as $removedName) {
            $removedName = trim((string) $removedName);
            if ($removedName !== '' && str_contains($componentName, $removedName)) {
                return true;
            }
        }

        return false;
    }

    private function syncVariantStock(int $variantId): void
    {
        ProductVariant::query()
            ->whereKey($variantId)
            ->update(['stock' => (float) Inventory::query()->where('product_variant_id', $variantId)->sum('quantity')]);
    }
}
