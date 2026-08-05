<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MyOrdersMonitoringAdjustmentActionTest extends TestCase
{
    public function test_completed_owned_orders_expose_adjustment_request_action(): void
    {
        $view = file_get_contents(
            dirname(__DIR__, 2).'/resources/views/site/orders/partials/orders_listing_monitoring.blade.php'
        );

        $this->assertStringContainsString('Order::STATUS_COMPLETED', $view);
        $this->assertStringContainsString('$order->canRequestAdjustment()', $view);
        $this->assertStringContainsString("route('site.order-adjustments.create', \$order)", $view);
        $this->assertStringContainsString('Gửi yêu cầu điều chỉnh', $view);
    }
}
