<?php

namespace Tests\Unit;

use App\Models\AccountingReconciliation;
use App\Models\Order;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderAdjustmentEligibilityTest extends TestCase
{
    #[Test]
    public function delivered_order_with_confirmed_revenue_can_request_adjustment(): void
    {
        $order = new Order(['delivered_at' => now()]);
        $order->setRelation('accountingReconciliation', new AccountingReconciliation([
            'status' => AccountingReconciliation::STATUS_CONFIRMED,
        ]));

        $this->assertTrue($order->canRequestAdjustment());
    }

    #[Test]
    public function completed_order_without_delivered_timestamp_can_request_adjustment_after_revenue_confirmation(): void
    {
        $order = new Order([
            'status' => Order::STATUS_COMPLETED,
            'delivered_at' => null,
        ]);
        $order->setRelation('accountingReconciliation', new AccountingReconciliation([
            'status' => AccountingReconciliation::STATUS_CONFIRMED,
        ]));

        $this->assertTrue($order->canRequestAdjustment());
    }

    #[Test]
    public function undelivered_order_cannot_request_adjustment(): void
    {
        $order = new Order(['status' => Order::STATUS_DELIVERING]);
        $order->setRelation('accountingReconciliation', new AccountingReconciliation([
            'status' => AccountingReconciliation::STATUS_CONFIRMED,
        ]));

        $this->assertFalse($order->canRequestAdjustment());
    }

    #[Test]
    public function order_without_confirmed_revenue_cannot_request_adjustment(): void
    {
        $order = new Order(['delivered_at' => now()]);
        $order->setRelation('accountingReconciliation', new AccountingReconciliation([
            'status' => AccountingReconciliation::STATUS_PENDING,
        ]));

        $this->assertFalse($order->canRequestAdjustment());
    }
}
