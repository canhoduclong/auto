<?php

namespace App\Http\Controllers\Api\Mobile\Concerns;

use App\Models\User;

trait ResolvesMobileRole
{
    private function resolvePrimaryRole(User $user): string
    {
        if ($user->hasRole('warehouse')) {
            return 'warehouse';
        }

        if ($user->hasRole('shipper') || $user->hasRole('ship')) {
            return 'shipper';
        }

        if ($user->hasRole('manager_shipper')) {
            return 'manager_shipper';
        }

        if ($user->hasRole('admin')) {
            return 'admin';
        }

        return (string) strtolower((string) optional($user->roles->first())->name ?: 'user');
    }

    private function resolveLayout(string $role): string
    {
        return match ($role) {
            'warehouse' => 'warehouse',
            'shipper', 'manager_shipper' => 'shipper',
            default => 'unsupported',
        };
    }

    private function mobileMenuByLayout(string $layout, bool $canManageShipper = false): array
    {
        if ($layout === 'shipper') {
            $menus = [
                ['key' => 'dashboard', 'label' => 'Dashboard', 'route' => '/shipper/dashboard', 'icon' => 'dashboard'],
                ['key' => 'available_orders', 'label' => 'Don co the nhan', 'route' => '/shipper/available-orders', 'icon' => 'inventory_2'],
                ['key' => 'delivery_routes', 'label' => 'Lo trinh giao hang', 'route' => '/shipper/routes', 'icon' => 'route'],
                ['key' => 'my_orders', 'label' => 'Don giao cua toi', 'route' => '/shipper/my-orders', 'icon' => 'local_shipping'],
                ['key' => 'history', 'label' => 'Lich su giao hang', 'route' => '/shipper/history', 'icon' => 'history'],
            ];

            if ($canManageShipper) {
                $menus[] = ['key' => 'assign_shipper', 'label' => 'Giao don cho ship', 'route' => '/shipper/assignments', 'icon' => 'assignment_ind'];
                $menus[] = ['key' => 'manage_fee', 'label' => 'Quan ly phi ship', 'route' => '/shipper/fees', 'icon' => 'payments'];
                $menus[] = ['key' => 'fee_report', 'label' => 'Bao cao chi phi ship', 'route' => '/shipper/fee-report', 'icon' => 'receipt_long'];
                $menus[] = ['key' => 'team_report', 'label' => 'Bao cao doi hinh ship', 'route' => '/shipper/team-report', 'icon' => 'groups'];
            }

            return $menus;
        }

        if ($layout === 'warehouse') {
            return [
                ['key' => 'dashboard', 'label' => 'Dashboard', 'route' => '/warehouse/dashboard', 'icon' => 'dashboard'],
                ['key' => 'stock_in', 'label' => 'Nhap kho', 'route' => '/warehouse/stock-in', 'icon' => 'move_to_inbox'],
                ['key' => 'stock_out', 'label' => 'Xuat kho', 'route' => '/warehouse/stock-out', 'icon' => 'outbox'],
                ['key' => 'inventory', 'label' => 'Ton kho', 'route' => '/warehouse/inventory', 'icon' => 'inventory'],
                ['key' => 'products', 'label' => 'San pham', 'route' => '/warehouse/products', 'icon' => 'category'],
                ['key' => 'orders', 'label' => 'Don can xu ly', 'route' => '/warehouse/orders', 'icon' => 'receipt'],
                ['key' => 'packing', 'label' => 'Dong goi don hang', 'route' => '/warehouse/packing', 'icon' => 'inventory_2'],
                ['key' => 'tasks', 'label' => 'Nhiem vu kho', 'route' => '/warehouse/tasks', 'icon' => 'task_alt'],
                ['key' => 'task_execute', 'label' => 'Thuc hien nhiem vu', 'route' => '/warehouse/tasks/execute', 'icon' => 'play_circle'],
                ['key' => 'returns', 'label' => 'Don tra ve', 'route' => '/warehouse/returns', 'icon' => 'assignment_return'],
                ['key' => 'reports', 'label' => 'Thong ke bao cao', 'route' => '/warehouse/reports', 'icon' => 'insights'],
            ];
        }

        return [];
    }
}
