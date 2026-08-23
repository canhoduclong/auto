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

    public function test_today_cards_can_add_an_order_to_customer_samples_with_ajax(): void
    {
        $view = file_get_contents(
            dirname(__DIR__, 2).'/resources/views/site/orders/monitoring.blade.php'
        );
        $routes = file_get_contents(dirname(__DIR__, 2).'/routes/web.php');

        $this->assertStringContainsString('monitor-add-to-sample', $view);
        $this->assertStringContainsString('Cho vào đơn mẫu', $view);
        $this->assertStringContainsString('$sampleDraftCustomerIds', $view);
        $this->assertStringContainsString('data-sample-customer-id', $view);
        $this->assertStringContainsString('removeCustomerSampleActions();', $view);
        $this->assertStringContainsString('Đã có đơn mẫu của khách hàng này rồi.', $view);
        $this->assertStringContainsString('pages.my_order_drafts.add_from_order', $routes);
    }

    public function test_my_orders_hides_sample_action_for_customers_with_a_sample(): void
    {
        $view = file_get_contents(
            dirname(__DIR__, 2).'/resources/views/site/orders/partials/orders_listing_monitoring.blade.php'
        );

        $this->assertStringContainsString('$hasSampleDraft', $view);
        $this->assertStringContainsString('!$hasSampleDraft', $view);
        $this->assertStringContainsString('pages.my_order_drafts.add_from_order', $view);
    }

    public function test_today_notes_footer_contains_priority_color_legend(): void
    {
        $view = file_get_contents(
            dirname(__DIR__, 2).'/resources/views/site/orders/monitoring.blade.php'
        );

        $this->assertStringContainsString('Bảng màu số thứ tự ưu tiên', $view);
        $this->assertStringContainsString('Số trong vòng tròn là thứ tự xử lý trong ngày', $view);
        foreach (['pending', 'approved', 'packed', 'transit', 'delivered', 'accounted', 'cancelled'] as $state) {
            $this->assertStringContainsString("'{$state}' =>", $view);
            $this->assertStringContainsString(".monitor-sequence.status-{$state}", $view);
        }
    }
}
