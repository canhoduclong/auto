<?php

namespace Tests\Unit;

use App\Http\Controllers\Warehouse\WarehouseDispatchSlipController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CeoWarehouseDispatchSlipMenuTest extends TestCase
{
    public function test_ceo_menu_groups_warehouse_overview_and_read_only_dispatch_slips_under_warehouse(): void
    {
        $layout = file_get_contents(__DIR__.'/../../resources/views/layouts/ceo.blade.php');
        $routes = file_get_contents(__DIR__.'/../../routes/web.php');
        $index = file_get_contents(__DIR__.'/../../resources/views/ceo/warehouse-dispatch-slips/index.blade.php');

        $this->assertStringContainsString('>Kho</div>', $layout);
        $this->assertStringContainsString("route('ceo.warehouse')", $layout);
        $this->assertStringContainsString('Tổng quan kho', $layout);
        $this->assertStringContainsString("route('ceo.warehouse-dispatch-slips.index')", $layout);
        $this->assertStringContainsString('Phiếu xuất kho tổng', $layout);

        $warehouseHeadingPosition = strpos($layout, '>Kho</div>');
        $warehouseOverviewPosition = strpos($layout, "route('ceo.warehouse')", $warehouseHeadingPosition);
        $dispatchSlipsPosition = strpos($layout, "route('ceo.warehouse-dispatch-slips.index')", $warehouseOverviewPosition);
        $reportsHeadingPosition = strpos($layout, '>Báo Cáo</div>', $dispatchSlipsPosition);

        $this->assertIsInt($warehouseHeadingPosition);
        $this->assertIsInt($warehouseOverviewPosition);
        $this->assertIsInt($dispatchSlipsPosition);
        $this->assertIsInt($reportsHeadingPosition);
        $this->assertLessThan($warehouseOverviewPosition, $warehouseHeadingPosition);
        $this->assertLessThan($dispatchSlipsPosition, $warehouseOverviewPosition);
        $this->assertLessThan($reportsHeadingPosition, $dispatchSlipsPosition);
        $this->assertStringContainsString("'ceoIndex'", $routes);
        $this->assertStringContainsString("'ceoShow'", $routes);
        $this->assertStringContainsString("'ceoPrintExport'", $routes);
        $this->assertStringContainsString('Tra cứu, xem chi tiết và in phiếu xuất kho tổng', $index);
        $this->assertStringNotContainsString('Lập phiếu tổng', $index);

        $indexRoute = Route::getRoutes()->getByName('ceo.warehouse-dispatch-slips.index');
        $showRoute = Route::getRoutes()->getByName('ceo.warehouse-dispatch-slips.show');
        $printRoute = Route::getRoutes()->getByName('ceo.warehouse-dispatch-slips.print-export');

        $this->assertSame('ceo/warehouse/dispatch-slips', $indexRoute?->uri());
        $this->assertSame(WarehouseDispatchSlipController::class.'@ceoIndex', $indexRoute?->getActionName());
        $this->assertSame(WarehouseDispatchSlipController::class.'@ceoShow', $showRoute?->getActionName());
        $this->assertSame(WarehouseDispatchSlipController::class.'@ceoPrintExport', $printRoute?->getActionName());
        $this->assertContains('role:ceo,admin', $indexRoute?->gatherMiddleware() ?? []);
    }
}
