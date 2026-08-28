<?php

namespace Tests\Unit;

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MonitoringSalesJournalViewTest extends TestCase
{
    public function test_monitoring_has_daily_sales_journal_modal_and_google_sheets_action(): void
    {
        $monitoring = file_get_contents(resource_path('views/site/orders/monitoring.blade.php'));
        $journal = file_get_contents(resource_path('views/site/orders/partials/sales_journal_modal.blade.php'));

        $this->assertStringContainsString('bi-clock-history', $monitoring);
        $this->assertStringContainsString('monitorSalesJournalModal', $monitoring);
        $this->assertStringContainsString('@if($canAccessMonitoringSalesJournal)', $monitoring);
        $this->assertStringContainsString("canAccessMonitoringSalesJournal(\$user)", file_get_contents(app_path('Http/Controllers/PageController.php')));
        $this->assertStringContainsString('>Hàng Hóa', $monitoring);
        $this->assertStringNotContainsString('Hàng - Số lượng', $monitoring);
        $this->assertStringContainsString('Duyệt PKD', $monitoring);
        $this->assertStringContainsString('Duyệt All', $monitoring);
        $this->assertStringNotContainsString('Duyệt đơn PKD', $monitoring);
        $this->assertStringNotContainsString('Duyệt tất cả', $monitoring);
        $this->assertStringContainsString("- {{ \\Carbon\\Carbon::parse(\$selectedDate)->format('d/m') }}", $monitoring);
        $this->assertStringContainsString("route('pages.my_orders.monitoring.sales_journal.google_sheets')", $journal);
        $this->assertStringContainsString('Báo cáo lên File Điều Hành', $journal);
        $this->assertStringNotContainsString('Ghi lên Google Sheets', $journal);

        $route = Route::getRoutes()->getByName('pages.my_orders.monitoring.sales_journal.google_sheets');
        $this->assertSame(['POST'], $route?->methods());
        $this->assertSame(PageController::class.'@myOrdersMonitoringSyncSalesJournal', $route?->getActionName());
    }
}
