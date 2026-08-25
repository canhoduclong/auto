<?php

namespace Tests\Unit;

use App\Models\ApprovalOrder;
use App\Models\ApprovalStep;
use App\Models\Order;
use App\Models\OrderAdjustment;
use App\Models\Role;
use App\Models\User;
use App\Services\ApprovalService;
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
        $this->assertStringContainsString('Yêu cầu điều chỉnh chờ {{ $adjustmentApprovalRoleLabel }} duyệt', $todayCards);
        $this->assertStringContainsString('pendingAdjustmentRequests', $todayCards);
        $this->assertStringContainsString('không phụ thuộc trang phân trang hiện tại', $controller);
        $this->assertStringContainsString('Tổng đơn sau phí/chiết khấu', $todayCards);
        $this->assertStringContainsString("adjustments._applied_summary", $todayCards);
        $this->assertStringContainsString('monitor-order-fee-row', $todayCards);
        $this->assertStringContainsString("['name' => 'Phí Ship'", $todayCards);

        $appliedSummary = file_get_contents($base.'/resources/views/site/orders/adjustments/_applied_summary.blade.php');
        $this->assertStringContainsString('đã duyệt và áp dụng vào đơn', $appliedSummary);
        $this->assertStringNotContainsString("adjustments._fee_changes", $appliedSummary);

        $leaderReview = file_get_contents($base.'/resources/views/site/orders/adjustments/_leader_review_card.blade.php');
        $this->assertStringContainsString('Tới đơn cần duyệt', $leaderReview);
        $this->assertStringContainsString("'highlight' => \$adjustment->order_id", $leaderReview);
        $this->assertStringContainsString('Nội dung:', $leaderReview);
        $this->assertStringContainsString("@include('site.orders.adjustments._fee_changes'", $leaderReview);
        $this->assertStringContainsString('Duyệt yêu cầu', $leaderReview);
        $this->assertStringContainsString('Lý do từ chối', $leaderReview);
    }

    public function test_leader_and_manager_dashboard_show_pending_adjustment_links(): void
    {
        $base = dirname(__DIR__, 2);
        $controller = file_get_contents($base.'/app/Http/Controllers/MyDashboardController.php');
        $dashboard = file_get_contents($base.'/resources/views/site/my_dashboard_sales.blade.php');

        $this->assertStringContainsString('pendingSalesAdjustmentApprovals', $controller);
        $this->assertStringContainsString("'#leader-adjustment-review-'", $controller);
        $this->assertStringContainsString('Yêu cầu điều chỉnh đơn chờ duyệt', $dashboard);
        $this->assertStringContainsString('Bấm để tới đơn cần duyệt', $dashboard);

        $approvalService = file_get_contents($base.'/app/Services/ApprovalService.php');
        $this->assertStringContainsString('$user->hasRole($this->leaderRoleSlugs())', $approvalService);
        $this->assertStringContainsString('$user->hasRole($this->managerRoleSlugs())', $approvalService);
        $this->assertStringContainsString('leaderCanReviewAdjustment', $approvalService);
        $this->assertStringContainsString('$leader->hasRole($this->leaderRoleSlugs())', $approvalService);
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

    public function test_sale_can_only_delete_own_unreviewed_adjustment(): void
    {
        $sale = new User();
        $sale->id = 10;
        $sale->setRelation('roles', collect([new Role(['name' => 'sale'])]));

        $adjustment = new OrderAdjustment([
            'requested_by' => 10,
            'status' => OrderAdjustment::STATUS_PENDING_APPROVAL,
        ]);
        $pending = new ApprovalOrder(['status' => 'pending']);
        $adjustment->setRelation('approvalSteps', collect([$pending]));

        $this->assertTrue($adjustment->canBeDeletedBy($sale));

        $pending->approved_by = 20;
        $pending->status = 'approved';
        $this->assertFalse($adjustment->canBeDeletedBy($sale));

        $pending->approved_by = null;
        $adjustment->requested_by = 99;
        $this->assertFalse($adjustment->canBeDeletedBy($sale));
    }

    public function test_leader_can_review_a_pending_adjustment_across_sale_teams(): void
    {
        $leader = new User(['team_id' => 10]);
        $leader->setRelation('roles', collect([new Role(['name' => 'leader'])]));

        $requester = new User(['team_id' => 10]);
        $orderOwner = new User(['team_id' => 99]);
        $order = new Order();
        $order->setRelation('user', $orderOwner);

        $adjustment = new OrderAdjustment();
        $adjustment->setRelation('requester', $requester);
        $adjustment->setRelation('order', $order);

        $requester->team_id = 77;
        $this->assertTrue((new ApprovalService())->leaderCanReviewAdjustment($leader, $adjustment));

        $sale = new User(['team_id' => 10]);
        $sale->setRelation('roles', collect([new Role(['name' => 'sale'])]));
        $this->assertFalse((new ApprovalService())->leaderCanReviewAdjustment($sale, $adjustment));
    }
}
