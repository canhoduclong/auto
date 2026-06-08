<?php

namespace App\Http\Controllers\Api\Mobile\Concerns;

use App\Models\User;

trait ResolvesMobileRole
{
    private function resolvePrimaryRole(User $user): string
    {
        if ($user->defaultRole && $user->roles->contains($user->defaultRole)) {
            return $user->defaultRole->name;
        }

        $role = $user->roles->first();
        return $role ? $role->name : 'user';
    }

    private function resolveSelectedMobileRole(User $user): string
    {
        if ($user->defaultRole && $user->roles->contains($user->defaultRole)) {
            return $user->defaultRole->name;
        }

        return $this->resolvePrimaryRole($user);
    }

    private function mobileWorkspaces(User $user): array
    {
        $workspaces = [];

        foreach ($user->roles as $role) {
            $layout = $role->layout_mobile_slug ?? 'unsupported';
            if (!isset($workspaces[$layout]) && $layout !== 'unsupported') {
                $workspaces[$layout] = [
                    'role' => $role->name,
                    'layout' => $layout,
                    'label' => $role->layout_mobile_name ?? $role->name,
                    'menu' => $this->mobileMenuByLayout($layout, $user->hasRole('manager_shipper') || $user->hasRole('admin')),
                ];
            }
        }

        return array_values($workspaces);
    }

    private function resolveLayout(string $roleName): string
    {
        $role = \App\Models\Role::where('name', $roleName)->first();
        return $role->layout_mobile_slug ?? 'unsupported';
    }

    private function mobileMenuByLayout(string $layout, bool $canManageShipper = false): array
    {
        if ($layout === 'shipper') {
            return [
                ['key' => 'dashboard', 'label' => 'Dashboard', 'route' => '/shipper', 'api' => '/shipper/delivery-schedules', 'icon' => 'dashboard'],
                ['key' => 'my_orders', 'label' => 'Đơn hàng', 'route' => '/shipper/my-orders', 'api' => '/shipper/my-orders', 'icon' => 'local_shipping'],
                ['key' => 'available_orders', 'label' => 'Đơn có thể nhận', 'route' => '/shipper/available', 'api' => '/shipper/available-orders', 'icon' => 'inventory_2'],
                ['key' => 'delivery_schedules', 'label' => 'Thống kê', 'route' => '/shipper/delivery-schedules', 'api' => '/shipper/delivery-schedules', 'icon' => 'route'],
            ];
        }

        if ($layout === 'manager_shipper') {
            return [
                ['key' => 'dashboard', 'label' => 'Dashboard Giao hàng', 'route' => '/shipper', 'api' => '/shipper/delivery-schedules', 'icon' => 'dashboard'],
                ['key' => 'manage_assignments', 'label' => 'Điều phối đơn hàng', 'route' => '/shipper/manage-assignments', 'api' => '/screens/manager_shipper/manage_assignments', 'icon' => 'route'],
                ['key' => 'shipper_team', 'label' => 'Quản lý Ship', 'route' => '/shipper/team-report', 'api' => '/screens/manager_shipper/shipper_team', 'icon' => 'local_shipping'],
                ['key' => 'manage_fees', 'label' => 'Quản lý phí ship', 'route' => '/shipper/manage-fees', 'api' => '/screens/manager_shipper/manage_fees', 'icon' => 'payments'],
                ['key' => 'shipping_fee_report', 'label' => 'Báo cáo chi phí ship', 'route' => '/shipper/shipping-fee-report', 'api' => '/screens/manager_shipper/shipping_fee_report', 'icon' => 'receipt_long'],
                ['key' => 'team_report', 'label' => 'Báo cáo đội hình ship', 'route' => '/shipper/team-report', 'api' => '/screens/manager_shipper/team_report', 'icon' => 'query_stats'],
                ['key' => 'route_planning', 'label' => 'Lịch trình giao hàng', 'route' => '/shipper/route-planning', 'api' => '/screens/manager_shipper/route_planning', 'icon' => 'swap_horiz'],
                ['key' => 'history', 'label' => 'Lịch sử giao hàng', 'route' => '/shipper/history', 'api' => '/shipper/history', 'icon' => 'assignment_returned'],
            ];
        }

        if ($layout === 'warehouse') {
            return [
                ['group' => 'Xử lý chính', 'key' => 'dashboard', 'label' => 'Dashboard', 'route' => '/warehouse', 'api' => '/warehouse/dashboard', 'icon' => 'dashboard'],
                ['group' => 'Xử lý chính', 'key' => 'orders', 'label' => 'Đơn cần đóng gói', 'route' => '/warehouse/orders', 'api' => '/warehouse/orders', 'icon' => 'inventory_2'],
                ['group' => 'Xử lý chính', 'key' => 'supplier_prices', 'label' => 'Giá thu mua', 'route' => '/warehouse/supplier-prices', 'api' => '/screens/warehouse/supplier_prices', 'icon' => 'payments'],
                ['group' => 'Xử lý chính', 'key' => 'incoming_transfers', 'label' => 'Nhận đơn chuyển tới', 'route' => '/warehouse/transfers/incoming', 'api' => '/screens/warehouse/incoming_transfers', 'icon' => 'assignment_returned'],
                ['group' => 'Xử lý chính', 'key' => 'incoming_inventory_transfers', 'label' => 'Nhận hàng chuyển tới', 'route' => '/warehouse/inventory-transfers/incoming', 'api' => '/screens/warehouse/incoming_inventory_transfers', 'icon' => 'move_to_inbox'],
                ['group' => 'Xử lý chính', 'key' => 'stock_in_create', 'label' => 'Nhập kho', 'route' => '/warehouse/stock-in/create', 'api' => '/screens/warehouse/stock_in_create', 'icon' => 'add_box'],
                ['group' => 'Xử lý chính', 'key' => 'returns', 'label' => 'Đơn trả về', 'route' => '/warehouse/returns', 'api' => '/warehouse/returns', 'icon' => 'assignment_return'],
                ['group' => 'Điều chuyển / xuất kho', 'key' => 'order_transfers', 'label' => 'Điều chuyển đơn', 'route' => '/warehouse/order-transfers', 'api' => '/screens/warehouse/order_transfers', 'icon' => 'swap_horiz'],
                ['group' => 'Điều chuyển / xuất kho', 'key' => 'inventory_transfers', 'label' => 'Điều chuyển hàng', 'route' => '/warehouse/inventory-transfers', 'api' => '/screens/warehouse/inventory_transfers', 'icon' => 'sync_alt'],
                ['group' => 'Điều chuyển / xuất kho', 'key' => 'stock_out', 'label' => 'Xuất kho', 'route' => '/warehouse/stock-out', 'api' => '/screens/warehouse/stock_out', 'icon' => 'outbox'],
                ['group' => 'Thống kê', 'key' => 'stock_out_orders', 'label' => 'Đơn xuất kho', 'route' => '/warehouse/stock-out/orders', 'api' => '/screens/warehouse/stock_out_orders', 'icon' => 'receipt_long'],
                ['group' => 'Thống kê', 'key' => 'inventory', 'label' => 'Tồn kho', 'route' => '/warehouse/inventory', 'api' => '/warehouse/inventory', 'icon' => 'inventory'],
            ];
        }

        if ($layout === 'sale') {
            return [
                ['key' => 'my_orders', 'label' => 'Đơn hàng của tôi', 'route' => '/my-orders', 'api' => '/screens/sale/my_orders', 'icon' => 'receipt_long'],
            ];
        }

        if ($layout === 'accounting') {
            return [
                ['key' => 'dashboard', 'label' => 'Accounting', 'route' => '/accounting', 'api' => '/screens/accounting/dashboard', 'icon' => 'account_balance'],
            ];
        }

        if ($layout === 'ceo') {
            return [
                ['key' => 'dashboard', 'label' => 'CEO', 'route' => '/ceo', 'api' => '/screens/ceo/dashboard', 'icon' => 'query_stats'],
            ];
        }

        return [];
    }
}
