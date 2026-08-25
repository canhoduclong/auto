<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AccountingDailySalesAdjustmentVisibilityTest extends TestCase
{
    public function test_report_uses_completed_adjustments_and_exposes_the_audit_section(): void
    {
        $controller = file_get_contents(__DIR__.'/../../app/Http/Controllers/AccountingDashboardController.php');
        $journal = file_get_contents(__DIR__.'/../../app/Services/CompletedSalesJournalService.php');
        $view = file_get_contents(__DIR__.'/../../resources/views/accounting/daily_sales.blade.php');
        $partial = file_get_contents(__DIR__.'/../../resources/views/accounting/partials/_completed_adjustments.blade.php');

        $this->assertStringContainsString("->where('status', OrderAdjustment::STATUS_COMPLETED)", $controller);
        $this->assertStringContainsString("->where('status', OrderAdjustment::STATUS_COMPLETED)", $journal);
        $this->assertStringContainsString("@include('accounting.partials._completed_adjustments')", $view);
        $this->assertStringContainsString('Điều chỉnh đã duyệt và áp dụng', $partial);
        $this->assertStringContainsString('Theo ngày nghiệp vụ của đơn', $partial);
        $this->assertStringContainsString('Đ/C #{{ $row->adjustment_id }}', $view);
    }
}
