<?php

namespace Tests\Unit;

use App\Models\Order;
use Tests\TestCase;

class OrderCopyStateTest extends TestCase
{
    public function test_copied_order_can_clear_all_warehouse_adjustment_state(): void
    {
        $sourceOrder = new Order([
            'warehouse_adjustment_status' => Order::WAREHOUSE_ADJUSTMENT_STATUS_SALE_REJECTED,
            'warehouse_adjustment_note' => 'Thay doi so luong',
            'warehouse_adjustment_changes' => [['type' => 'quantity']],
            'warehouse_adjustment_requested_by' => 10,
            'warehouse_adjustment_requested_at' => now(),
            'warehouse_adjustment_confirmed_by' => 11,
            'warehouse_adjustment_confirmed_at' => now(),
            'warehouse_adjustment_rejected_by' => 12,
            'warehouse_adjustment_rejected_at' => now(),
            'warehouse_adjustment_rejected_reason' => 'Khong dong y',
        ]);

        $copiedOrder = $sourceOrder->replicate()->clearWarehouseAdjustmentState();

        $this->assertSame(Order::WAREHOUSE_ADJUSTMENT_STATUS_NONE, $copiedOrder->warehouse_adjustment_status);
        $this->assertNull($copiedOrder->warehouse_adjustment_note);
        $this->assertNull($copiedOrder->warehouse_adjustment_changes);
        $this->assertNull($copiedOrder->warehouse_adjustment_requested_by);
        $this->assertNull($copiedOrder->warehouse_adjustment_requested_at);
        $this->assertNull($copiedOrder->warehouse_adjustment_confirmed_by);
        $this->assertNull($copiedOrder->warehouse_adjustment_confirmed_at);
        $this->assertNull($copiedOrder->warehouse_adjustment_rejected_by);
        $this->assertNull($copiedOrder->warehouse_adjustment_rejected_at);
        $this->assertNull($copiedOrder->warehouse_adjustment_rejected_reason);
    }
}
