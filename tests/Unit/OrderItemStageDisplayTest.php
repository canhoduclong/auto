<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use PHPUnit\Framework\TestCase;

class OrderItemStageDisplayTest extends TestCase
{
    public function test_it_uses_ordered_weight_before_warehouse_processing(): void
    {
        $item = $this->weightedItem();

        $this->assertSame(10.0, $item->displayValueForStage(Order::STATUS_APPROVED));
        $this->assertSame('10 kg', $item->displayLabelForStage(Order::STATUS_APPROVED));
        $this->assertSame('Theo đơn', $item->displaySourceForStage(Order::STATUS_APPROVED));
    }

    public function test_it_uses_warehouse_weight_during_packing_and_delivery(): void
    {
        $item = $this->weightedItem([
            'packed_weight' => 9.75,
            'actual_weight' => 8.4,
        ]);

        $this->assertSame(9.75, $item->displayValueForStage(Order::STATUS_PACKING));
        $this->assertSame(9.75, $item->displayValueForStage(Order::STATUS_DELIVERING));
        $this->assertSame('Kho cân', $item->displaySourceForStage(Order::STATUS_READY_TO_SHIP));
        $this->assertSame(975000.0, $item->lineTotalForStage(Order::STATUS_DELIVERING));
    }

    public function test_it_uses_customer_delivery_weight_after_delivery_is_completed(): void
    {
        $item = $this->weightedItem([
            'packed_weight' => 9.75,
            'actual_weight' => 8.4,
        ]);

        $this->assertSame(8.4, $item->displayValueForStage(Order::STATUS_DELIVERED));
        $this->assertSame('8,4 kg', $item->displayLabelForStage(Order::STATUS_COMPLETED));
        $this->assertSame('Thực giao / khách cân', $item->displaySourceForStage(Order::STATUS_DELIVERED));
        $this->assertSame(840000.0, $item->lineTotalForStage(Order::STATUS_COMPLETED));
    }

    private function weightedItem(array $attributes = []): OrderItem
    {
        return new OrderItem(array_merge([
            'quantity' => 4,
            'unit_weight' => 2.5,
            'is_priced_by_kg' => true,
            'price' => 100000,
            'total' => 1000000,
        ], $attributes));
    }
}
