<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class WarehouseProductionDashboardViewTest extends TestCase
{
    public function test_warehouse_menu_and_production_dashboard_are_present(): void
    {
        $layout = file_get_contents(__DIR__ . '/../../resources/views/layouts/warehouse.blade.php');
        $dashboard = file_get_contents(__DIR__ . '/../../resources/views/warehouse/dashboard.blade.php');

        $this->assertStringContainsString('Bảng điều khiển', $layout);
        $this->assertStringContainsString('Bảng điều khiển sản xuất nhà máy', $dashboard);
        $this->assertStringContainsString('Sản lượng theo loại và size', $dashboard);
        $this->assertStringContainsString('Hao hụt sản xuất', $dashboard);
        $this->assertStringContainsString('Hàng loại khi nhập', $dashboard);
        $this->assertStringContainsString('Cơ cấu chi phí', $dashboard);
        $this->assertStringNotContainsString('@php(', $dashboard);
    }
}
