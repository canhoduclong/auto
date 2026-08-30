<?php

namespace Tests\Unit;

use App\Http\Controllers\WarehouseDashboardController;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use ReflectionMethod;
use Tests\TestCase;

class WarehousePackingWeightRequirementTest extends TestCase
{
    public function test_unit_priced_items_do_not_require_actual_kg_to_complete_packing(): void
    {
        $setItem = new OrderItem;
        $setItem->setRawAttributes([
            'id' => 1,
            // Reproduce legacy data: the old default says priced by kg even
            // though the product unit is Bộ.
            'is_priced_by_kg' => true,
            'actual_weight' => null,
        ]);
        $setProduct = new Product;
        $setProduct->setRawAttributes(['id' => 1, 'unit' => 'bo']);
        $setItem->setRelation('product', $setProduct);
        $order = new Order;
        $order->setRelation('items', collect([$setItem]));

        $this->assertTrue($this->missingItems($order)->isEmpty());
    }

    public function test_only_kg_priced_items_without_actual_weight_are_blocked(): void
    {
        $setItem = new OrderItem;
        $setItem->setRawAttributes([
            'id' => 1,
            'is_priced_by_kg' => false,
            'actual_weight' => null,
        ]);
        $missingKgItem = new OrderItem;
        $missingKgItem->setRawAttributes([
            'id' => 2,
            'is_priced_by_kg' => true,
            'actual_weight' => null,
        ]);
        $weighedKgItem = new OrderItem;
        $weighedKgItem->setRawAttributes([
            'id' => 3,
            'is_priced_by_kg' => true,
            'actual_weight' => 12.5,
        ]);
        $order = new Order;
        $order->setRelation('items', collect([$setItem, $missingKgItem, $weighedKgItem]));

        $missing = $this->missingItems($order);

        $this->assertCount(1, $missing);
        $this->assertSame(2, (int) $missing->first()->id);
    }

    private function missingItems(Order $order)
    {
        $method = new ReflectionMethod(WarehouseDashboardController::class, 'packingItemsMissingActualWeight');

        return $method->invoke(app(WarehouseDashboardController::class), $order);
    }
}
