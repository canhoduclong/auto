<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class OrderAdjustmentMonitoringVisibilityTest extends TestCase
{
    public function test_monitoring_cards_show_submitted_adjustments_and_current_approval_stage(): void
    {
        $base = dirname(__DIR__, 2);
        $controller = file_get_contents($base.'/app/Http/Controllers/PageController.php');
        $view = file_get_contents($base.'/resources/views/site/orders/partials/orders_listing_monitoring.blade.php');

        $this->assertStringContainsString("'adjustments' => function", $controller);
        $this->assertStringContainsString("'approvalSteps.step:id,role_slug,step_order'", $controller);
        $this->assertStringContainsString('Yêu cầu thay đổi đã gửi', $view);
        $this->assertStringContainsString('Đang chờ ', $view);
        $this->assertStringContainsString('Kế toán', $view);
        $this->assertStringContainsString('Xem tiến trình', $view);
        $this->assertStringContainsString('$adjustment->reject_reason', $view);
    }

    public function test_all_accounting_role_aliases_can_open_and_approve_adjustment_details(): void
    {
        $controller = file_get_contents(dirname(__DIR__, 2).'/app/Http/Controllers/OrderAdjustmentController.php');

        $this->assertGreaterThanOrEqual(
            2,
            substr_count($controller, "['account', 'accountant', 'accounting']")
        );
    }
}
