<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCuttingBatch;
use App\Models\ProductVariant;
use Illuminate\Validation\ValidationException;

class ExternalCuttingReceiptService
{
    public static function componentWeights(float $weight, int $targetId, array $rates): array
    {
        $yield = (float) ($rates[$targetId] ?? 0);
        if ($weight <= 0 || $yield <= 0 || abs(array_sum($rates) - 100) > 0.0001 || min($rates) < 0) {
            throw ValidationException::withMessages(['recipe' => 'Định mức pha lóc không hợp lệ hoặc thiếu tỷ lệ thành phẩm.']);
        }
        $input = $weight * 100 / $yield;
        $components = [];
        foreach ($rates as $productId => $rate) {
            if ((int) $productId !== $targetId && $rate > 0) {
                $components[(int) $productId] = round($input * $rate / 100, 3);
            }
        }

        return ['input_weight' => round($input, 3), 'components' => $components];
    }

    // Caller holds the warehouse and order locks and owns the packing transaction.
    public function receive(Order $order, ProductVariant $variant, Product $recipe, int $quantity, float $weight, int $warehouseId, int $userId): InventoryDocument
    {
        try {
            $rates = $recipe->cuttingPercentagesForTarget((int) $variant->product_id);
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages(['recipe' => $exception->getMessage()]);
        }
        $plan = self::componentWeights($weight, (int) $variant->product_id, $rates ?? []);
        $components = [];
        foreach ($plan['components'] as $productId => $kg) {
            $component = ProductVariant::where('product_id', $productId)->where('status', 1)->orderBy('sort_order')->orderBy('id')->first();
            if (! $component) {
                throw ValidationException::withMessages(['recipe' => 'Thành phần phụ phẩm chưa có biến thể đang hoạt động.']);
            }
            $components[] = ['variant_id' => $component->id, 'quantity' => $kg];
        }
        $document = InventoryDocument::create([
            'type' => 'import', 'warehouse_id' => $warehouseId, 'document_date' => today()->toDateString(),
            'notes' => 'Đóng hàng bù từ bên ngoài cho đơn '.$order->code.'; '.$weight.' kg. Không xuất nguyên liệu.',
            'shipping_fee' => 0, 'user_id' => $userId,
        ]);
        $document->items()->create(['product_variant_id' => $variant->id, 'quantity' => $quantity, 'unit_cost' => 0, 'note' => $weight.' kg thực tế']);
        $inventory = Inventory::firstOrCreate(['warehouse_id' => $warehouseId, 'product_variant_id' => $variant->id], ['quantity' => 0, 'weight_kg' => 0, 'reserved_quantity' => 0, 'low_stock_threshold' => 10]);
        $inventory = Inventory::whereKey($inventory->id)->lockForUpdate()->firstOrFail();
        $inventory->update(['quantity' => $inventory->quantity + $quantity, 'weight_kg' => (float) $inventory->weight_kg + $weight]);
        InventoryMovement::create(['inventory_id' => $inventory->id, 'quantity' => $quantity, 'weight_kg' => $weight, 'type' => 'cutting_in', 'reference_id' => $document->id, 'reference_type' => InventoryDocument::class, 'user_id' => $userId]);
        $variant->update(['stock' => Inventory::where('product_variant_id', $variant->id)->sum('quantity')]);
        $batch = ProductCuttingBatch::create([
            'warehouse_id' => $warehouseId, 'order_id' => $order->id, 'target_product_variant_id' => $variant->id,
            'status' => ProductCuttingBatch::STATUS_COMPLETED, 'source_materials' => [],
            'performed_by' => $userId, 'completed_by' => $userId, 'completed_at' => now(),
            'finished_import_document_id' => $document->id, 'input_weight' => $plan['input_weight'],
            'planned_finished_weight' => $weight, 'actual_finished_weight' => $weight,
            'actual_component_weight' => array_sum(array_column($components, 'quantity')),
            'loss_weight' => 0, 'loss_percent' => 0, 'planned_components' => $components, 'actual_components' => $components,
            'note' => 'Nhập bù từ bên ngoài; định mức '.$recipe->name.' (#'.$recipe->id.'). Khối lượng nguyên liệu quy đổi, không trừ tồn.',
        ]);
        if ($components) {
            app(ProductCuttingService::class)->appendDeferredComponentImportRequest($warehouseId, $components, $batch, $userId, $order->id);
        }

        return $document;
    }
}
