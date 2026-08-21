<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class WarehouseProductionDashboardViewTest extends TestCase
{
    public function test_production_dashboard_has_its_own_warehouse_menu_and_page(): void
    {
        $layout = file_get_contents(__DIR__ . '/../../resources/views/layouts/warehouse.blade.php');
        $dashboard = file_get_contents(__DIR__ . '/../../resources/views/warehouse/dashboard.blade.php');
        $productionDashboard = file_get_contents(__DIR__ . '/../../resources/views/warehouse/production-dashboard.blade.php');
        $routes = file_get_contents(__DIR__ . '/../../routes/web.php');

        $this->assertStringContainsString('Bảng điều khiển', $layout);
        $this->assertStringContainsString("route('warehouse.production-dashboard')", $layout);
        $this->assertStringContainsString("name('production-dashboard')", $routes);
        $this->assertStringNotContainsString('Bảng điều khiển sản xuất nhà máy', $dashboard);
        $this->assertStringContainsString('Tiến độ công việc', $dashboard);
        $this->assertStringContainsString('Bảng điều khiển sản xuất nhà máy', $productionDashboard);
        $this->assertStringContainsString('Sản lượng theo loại và size', $productionDashboard);
        $this->assertStringContainsString('Hao hụt sản xuất', $productionDashboard);
        $this->assertStringContainsString('Hàng loại khi nhập', $productionDashboard);
        $this->assertStringContainsString('Cơ cấu chi phí', $productionDashboard);
        $this->assertStringContainsString("route('warehouse.production-dashboard')", $productionDashboard);
        $this->assertStringNotContainsString('@php(', $productionDashboard);
    }
}
