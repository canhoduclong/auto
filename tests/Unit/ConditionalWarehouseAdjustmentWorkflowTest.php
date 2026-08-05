<?php

namespace Tests\Unit;

use App\Models\OrderAdjustment;
use App\Models\OrderAdjustmentItem;
use App\Models\OrderItem;
use PHPUnit\Framework\TestCase;

class ConditionalWarehouseAdjustmentWorkflowTest extends TestCase
{
    public function test_price_only_adjustments_skip_warehouse_and_stock_changes_do_not(): void
    {
        $base = dirname(__DIR__, 2);
        $model = file_get_contents($base.'/app/Models/OrderAdjustment.php');
        $service = file_get_contents($base.'/app/Services/ApprovalService.php');
        $controller = file_get_contents($base.'/app/Http/Controllers/OrderAdjustmentController.php');
        $createView = file_get_contents($base.'/resources/views/site/orders/adjustments/create.blade.php');
        $warehouseLayout = file_get_contents($base.'/resources/views/layouts/warehouse.blade.php');
        $warehouseView = file_get_contents($base.'/resources/views/warehouse/order_adjustments.blade.php');

        $this->assertStringContainsString('requiresWarehouseConfirmation', $model);
        $this->assertStringContainsString('original_quantity', $model);
        $this->assertStringContainsString('product_variant_id', $model);
        $this->assertStringNotContainsString('original_weight - (float) $item->adjusted_weight', $model);
        $this->assertStringContainsString("'status' => 'approved'", $service);
        $this->assertStringContainsString('không thay đổi số lượng hoặc loại hàng', $service);
        $this->assertStringContainsString("\$requiresWarehouse ? 'pending' : 'not_required'", $controller);
        $this->assertStringContainsString('Khối lượng mới (kg)', $createView);
        $this->assertStringContainsString('name="items[{{ $idx }}][adjusted_weight]"', $createView);
        $this->assertStringNotContainsString('type="hidden" name="items[{{ $idx }}][adjusted_weight]"', $createView);
        $this->assertStringContainsString('Bổ sung hàng thiếu', $createView);
        $this->assertStringContainsString('missing-item-search', $createView);
        $this->assertStringContainsString("url.searchParams.set('view', 'products')", $createView);
        $this->assertStringContainsString('.monitor-product-choice', $createView);
        $this->assertStringContainsString('.monitor-variant-option', $createView);
        $this->assertStringContainsString('product_variant_id', $createView);
        $this->assertStringContainsString("'items.*.product_variant_id'", $controller);
        $this->assertStringContainsString('Số lượng hàng thêm mới phải lớn hơn 0', $controller);
        $this->assertStringContainsString("'original_quantity' => 0", $controller);
        $this->assertStringContainsString('warehouse.order-adjustments.index', $warehouseLayout);
        $this->assertStringContainsString('Duyệt bước Kho', $warehouseView);
        $this->assertStringContainsString('Xác nhận và hoàn tất', $warehouseView);
    }

    public function test_only_quantity_or_product_type_requires_final_warehouse_confirmation(): void
    {
        $originalOrderItem = new OrderItem([
            'product_id' => 10,
            'product_variant_id' => 100,
            'quantity' => 5,
        ]);

        $priceOrWeightOnly = new OrderAdjustmentItem([
            'order_item_id' => 1,
            'product_id' => 10,
            'product_variant_id' => 100,
            'original_quantity' => 5,
            'adjusted_quantity' => 5,
            'original_weight' => 5,
            'adjusted_weight' => 6,
            'original_price' => 100,
            'adjusted_price' => 120,
        ]);
        $priceOrWeightOnly->setRelation('orderItem', $originalOrderItem);

        $adjustment = new OrderAdjustment();
        $adjustment->setRelation('items', collect([$priceOrWeightOnly]));
        $this->assertFalse($adjustment->requiresWarehouseConfirmation());

        $quantityChange = clone $priceOrWeightOnly;
        $quantityChange->adjusted_quantity = 6;
        $quantityChange->setRelation('orderItem', $originalOrderItem);
        $adjustment->setRelation('items', collect([$quantityChange]));
        $this->assertTrue($adjustment->requiresWarehouseConfirmation());

        $typeChange = clone $priceOrWeightOnly;
        $typeChange->product_variant_id = 101;
        $typeChange->setRelation('orderItem', $originalOrderItem);
        $adjustment->setRelation('items', collect([$typeChange]));
        $this->assertTrue($adjustment->requiresWarehouseConfirmation());

        $newType = new OrderAdjustmentItem([
            'order_item_id' => null,
            'product_id' => 11,
            'product_variant_id' => 110,
            'original_quantity' => 0,
            'adjusted_quantity' => 2,
        ]);
        $newType->setRelation('orderItem', null);
        $adjustment->setRelation('items', collect([$newType]));
        $this->assertTrue($adjustment->requiresWarehouseConfirmation());
    }
}
