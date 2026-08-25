<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AccountingAdjustmentNotificationTest extends TestCase
{
    public function test_accounting_adjustment_notifications_are_wired(): void
    {
        $base = dirname(__DIR__, 2);
        $routes = file_get_contents($base.'/routes/web.php');
        $layout = file_get_contents($base.'/resources/views/layouts/accounting.blade.php');
        $view = file_get_contents($base.'/resources/views/accounting/order_adjustments.blade.php');
        $service = file_get_contents($base.'/app/Services/ApprovalService.php');

        $this->assertStringContainsString("'/order-adjustments'", $routes);
        $this->assertStringContainsString("name('order-adjustments')", $routes);
        $this->assertStringContainsString('pendingAccountingAdjustmentCount', $layout);
        $this->assertStringContainsString('Duyệt điều chỉnh đơn', $layout);
        $this->assertStringContainsString('Xác nhận và duyệt', $view);
        $this->assertStringContainsString('site.order-adjustments.approve', $view);
        $this->assertStringContainsString('site.order-adjustments.reject', $view);
        $this->assertStringContainsString('pendingAccountingAdjustments', $service);
        $this->assertStringContainsString('reviewedAccountingAdjustments', $service);
        $this->assertStringContainsString('sortBy(fn (ApprovalOrder $approval)', $service);
        $this->assertStringContainsString('Chờ duyệt', $view);
        $this->assertStringContainsString('Đã xử lý', $view);
        $this->assertStringContainsString('Kế toán đã duyệt', $view);
        $this->assertStringContainsString('Kế toán đã từ chối', $view);
        $this->assertStringContainsString('@if($isPendingAccounting)', $view);
    }
}
