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

    public function test_today_cards_load_and_submit_adjustments_with_ajax(): void
    {
        $view = file_get_contents(
            dirname(__DIR__, 2).'/resources/views/site/orders/monitoring.blade.php'
        );
        $inlineForm = file_get_contents(
            dirname(__DIR__, 2).'/resources/views/site/orders/adjustments/_inline_form.blade.php'
        );

        $this->assertStringContainsString('class="btn btn-sm btn-warning monitor-adjustment-open"', $view);
        $this->assertStringContainsString("'X-Requested-With': 'XMLHttpRequest'", $view);
        $this->assertStringContainsString('new FormData(form)', $view);
        $this->assertStringContainsString('data-monitor-adjustment-form', $inlineForm);
        $this->assertStringContainsString('monitor-adjustment-products-toggle', $inlineForm);
        $this->assertStringContainsString('monitor-adjustment-fees-toggle', $inlineForm);
        $this->assertStringContainsString('name="recipient_name"', $inlineForm);
        $this->assertStringContainsString('name="recipient_phone"', $inlineForm);
        $this->assertStringContainsString('name="delivery_time"', $inlineForm);
        $this->assertStringContainsString('evidence_images[]', $inlineForm);
    }
}
