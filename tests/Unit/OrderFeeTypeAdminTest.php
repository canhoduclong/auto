<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class OrderFeeTypeAdminTest extends TestCase
{
    public function test_admin_fee_management_is_wired_to_routes_and_sidebar(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 2).'/routes/web.php');
        $sidebar = file_get_contents(dirname(__DIR__, 2).'/resources/views/layouts/sidebar.blade.php');
        $view = file_get_contents(dirname(__DIR__, 2).'/resources/views/admin/order-fee-types/index.blade.php');

        $this->assertStringContainsString('OrderFeeTypeController', $routes);
        $this->assertStringContainsString('admin.order-fee-types.index', $sidebar);
        $this->assertStringContainsString('Thêm loại phí mới', $view);
        $this->assertStringContainsString('Ngừng sử dụng', $view);
        $this->assertStringContainsString('calculation_type', $view);
        $this->assertStringContainsString('direction', $view);
    }
}
