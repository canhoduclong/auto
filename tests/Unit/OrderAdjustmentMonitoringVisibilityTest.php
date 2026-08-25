<?php

namespace Tests\Unit;

use App\Models\ApprovalOrder;
use App\Models\ApprovalStep;
use App\Models\OrderAdjustment;
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

        $todayCards = file_get_contents($base.'/resources/views/site/orders/monitoring.blade.php');
        $this->assertStringContainsString('Sale đã gửi yêu cầu điều chỉnh', $todayCards);
        $this->assertStringContainsString('data-sent-adjustments', $todayCards);
        $this->assertStringContainsString('$adjustment->progressLabel()', $todayCards);
        $this->assertStringContainsString("statusItems.insertAdjacentHTML('afterbegin'", $todayCards);
    }

    public function test_all_accounting_role_aliases_can_open_and_approve_adjustment_details(): void
    {
        $controller = file_get_contents(dirname(__DIR__, 2).'/app/Http/Controllers/OrderAdjustmentController.php');

        $this->assertGreaterThanOrEqual(
            2,
            substr_count($controller, "['account', 'accountant', 'accounting']")
        );
    }

    public function test_sale_status_summary_names_the_department_currently_reviewing(): void
    {
        $adjustment = new OrderAdjustment(['status' => OrderAdjustment::STATUS_PENDING_APPROVAL]);
        $approval = new ApprovalOrder(['status' => 'pending']);
        $approval->setRelation('step', new ApprovalStep(['step_order' => 3, 'role_slug' => 'accountant']));
        $adjustment->setRelation('approvalSteps', collect([$approval]));

        $this->assertSame('Đang chờ Kế toán duyệt', $adjustment->progressLabel());
        $this->assertSame('warning', $adjustment->progressTone());

        $adjustment->status = OrderAdjustment::STATUS_REJECTED;
        $this->assertSame('Đã bị từ chối', $adjustment->progressLabel());
        $this->assertSame('danger', $adjustment->progressTone());
    }
}
