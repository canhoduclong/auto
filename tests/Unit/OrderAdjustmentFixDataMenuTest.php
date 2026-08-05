<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class OrderAdjustmentFixDataMenuTest extends TestCase
{
    public function test_fix_data_menu_and_approval_page_are_wired(): void
    {
        $routes = file_get_contents(dirname(__DIR__, 2).'/routes/web.php');
        $monitoring = file_get_contents(dirname(__DIR__, 2).'/resources/views/site/orders/monitoring.blade.php');
        $sidebar = file_get_contents(dirname(__DIR__, 2).'/resources/views/site/orders/partials/monitor_sidebar_nav.blade.php');
        $sharedNavigation = file_get_contents(dirname(__DIR__, 2).'/resources/views/site/orders/partials/order_navigation_links.blade.php');
        $dashboard = file_get_contents(dirname(__DIR__, 2).'/resources/views/site/my_dashboard_sales.blade.php');
        $header = file_get_contents(dirname(__DIR__, 2).'/resources/views/layouts/partials/site_header.blade.php');
        $userModel = file_get_contents(dirname(__DIR__, 2).'/app/Models/User.php');
        $controller = file_get_contents(dirname(__DIR__, 2).'/app/Http/Controllers/OrderAdjustmentController.php');
        $index = file_get_contents(dirname(__DIR__, 2).'/resources/views/site/orders/adjustments/index.blade.php');
        $accountingOrders = file_get_contents(dirname(__DIR__, 2).'/resources/views/accounting/orders.blade.php');

        $this->assertStringContainsString("/my-orders/fix-data", $routes);
        $this->assertStringContainsString("site.orders.partials.monitor_sidebar_nav", $monitoring);
        $this->assertStringContainsString('site.orders.partials.order_navigation_links', $sidebar);
        $this->assertStringContainsString('site.orders.partials.order_navigation_links', $dashboard);
        $this->assertStringContainsString("site.order-adjustments.index", $sharedNavigation);
        $this->assertStringContainsString("'key' => 'fix_data'", $sharedNavigation);
        $this->assertStringContainsString("['tab' => 'my_orders']", $sharedNavigation);
        $this->assertStringContainsString("['tab' => 'customers']", $sharedNavigation);
        $this->assertStringContainsString('canManageOrderAdjustments', $sharedNavigation);
        $this->assertStringContainsString('canManageOrderAdjustments', $header);
        $this->assertStringContainsString('canManageOrderAdjustments', $controller);
        $this->assertStringContainsString('public function canManageOrderAdjustments', $userModel);
        $this->assertStringContainsString("'leader_sale'", $userModel);
        $this->assertStringContainsString("'manager_sale'", $userModel);
        $this->assertStringContainsString("middleware('role:leader,leader_sale,sale_manager,manager,manager_sale,admin')", $routes);
        $this->assertStringContainsString("['activeTab' => 'fix_data']", $index);
        $this->assertStringContainsString('Fix số liệu', $header);
        $this->assertStringContainsString('Leader → Manager → Kế toán → Kho', $index);
        $this->assertStringContainsString("site.order-adjustments.approve", $index);
        $this->assertStringContainsString("site.order-adjustments.reject", $index);
        $this->assertStringNotContainsString("\$authUser?->hasRole('accountant') ||", $accountingOrders);
    }

    public function test_only_management_roles_can_access_fix_data(): void
    {
        $userWithRole = static function (string $roleName): User {
            $user = new User();
            $user->setRelation('roles', collect([new Role(['name' => $roleName])]));

            return $user;
        };

        $this->assertFalse($userWithRole('sale')->canManageOrderAdjustments());
        $this->assertTrue($userWithRole('leader')->canManageOrderAdjustments());
        $this->assertTrue($userWithRole('leader_sale')->canManageOrderAdjustments());
        $this->assertTrue($userWithRole('manager')->canManageOrderAdjustments());
        $this->assertTrue($userWithRole('manager_sale')->canManageOrderAdjustments());
        $this->assertTrue($userWithRole('admin')->canManageOrderAdjustments());
    }
}
