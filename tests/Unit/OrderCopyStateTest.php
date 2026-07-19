<?php

namespace Tests\Unit;

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
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

    public function test_copied_order_resets_operational_state_to_new_unconfirmed_order(): void
    {
        $sourceOrder = new Order([
            'status' => Order::STATUS_DELIVERED,
            'payment_status' => 'paid',
            'delivery_status' => 'delivered',
            'daily_sequence' => 9,
            'warehouse_id' => 2,
            'amount_paid' => 150000,
            'amount_due' => 50000,
            'collected_amount' => 200000,
            'proof_images' => ['proof.jpg'],
            'stock_sufficient' => true,
            'stock_alert_status' => 'ready',
        ]);
        $sourceOrder->forceFill(['id' => 123]);

        $copiedOrder = $sourceOrder->replicate()->resetForCopiedOrder((int) $sourceOrder->id);

        $this->assertSame(OrderStatus::Pending->value, $copiedOrder->status);
        $this->assertSame(PaymentStatus::Unpaid->value, $copiedOrder->payment_status);
        $this->assertSame(DeliveryStatus::NotShipped->value, $copiedOrder->delivery_status);
        $this->assertSame(123, $copiedOrder->copied_from_order_id);
        $this->assertNull($copiedOrder->daily_sequence);
        $this->assertNull($copiedOrder->warehouse_id);
        $this->assertSame(0, $copiedOrder->amount_paid);
        $this->assertSame(0, $copiedOrder->amount_due);
        $this->assertNull($copiedOrder->collected_amount);
        $this->assertNull($copiedOrder->proof_images);
        $this->assertNull($copiedOrder->stock_sufficient);
        $this->assertNull($copiedOrder->stock_alert_status);
    }

    public function test_owner_can_directly_edit_active_order_before_delivery(): void
    {
        foreach ([
            'draft',
            'pending',
            Order::STATUS_ORDER_PLACED,
            Order::STATUS_PENDING_LEADER_APPROVAL,
            Order::STATUS_PENDING_MANAGER_APPROVAL,
            Order::STATUS_APPROVED,
            Order::STATUS_PACKING,
            Order::STATUS_DELIVERING,
        ] as $status) {
            $order = new Order(['status' => $status]);

            $this->assertTrue($order->canBeDirectlyEditedByOwner(), $status);
        }
    }

    public function test_owner_cannot_directly_edit_delivered_or_closed_order(): void
    {
        $deliveredOrder = new Order([
            'status' => Order::STATUS_COMPLETED,
            'delivered_at' => now(),
        ]);
        $cancelledOrder = new Order(['status' => Order::STATUS_CANCELLED]);

        $this->assertFalse($deliveredOrder->canBeDirectlyEditedByOwner());
        $this->assertFalse($cancelledOrder->canBeDirectlyEditedByOwner());
    }

    public function test_copied_order_remains_directly_editable_for_its_owner(): void
    {
        $order = new Order([
            'status' => Order::STATUS_COMPLETED,
            'copied_from_order_id' => 99,
        ]);

        $this->assertTrue($order->canBeDirectlyEditedByOwner());
    }
}
