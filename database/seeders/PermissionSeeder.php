<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $routes = Route::getRoutes();

        foreach ($routes as $route) {
            $name = $route->getName();
            if (!$name) { continue;}
            $group = explode('.', $name)[0] ?? null;
            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                [
                    'description' => $route->uri(),
                    'group' => $group,
                    'uri' => $route->uri(),
                    'method' => implode('|', $route->methods()),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // Bổ sung quyền giao dịch
        $transactionPermissions = [
            ['name' => 'transaction.view', 'description' => 'Xem giao dịch', 'group' => 'transaction'],
            ['name' => 'transaction.create', 'description' => 'Tạo giao dịch', 'group' => 'transaction'],
            ['name' => 'transaction.refund', 'description' => 'Hoàn trả giao dịch', 'group' => 'transaction'],
        ];
        foreach ($transactionPermissions as $perm) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $perm['name']],
                [
                    'description' => $perm['description'],
                    'group' => $perm['group'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // Bổ sung quyền quản lý giá
        $pricingPermissions = [
            ['name' => 'products.price-management.index', 'description' => 'Xem danh sách quản lý giá sản phẩm', 'group' => 'pricing'],
            ['name' => 'products.price-management.show', 'description' => 'Xem chi tiết quản lý giá sản phẩm', 'group' => 'pricing'],
            ['name' => 'products.price-management.update', 'description' => 'Cập nhật quản lý giá sản phẩm', 'group' => 'pricing'],
            ['name' => 'variants.edit-price', 'description' => 'Mở form điều chỉnh giá biến thể', 'group' => 'pricing'],
            ['name' => 'variants.update-price', 'description' => 'Cập nhật giá biến thể', 'group' => 'pricing'],
            ['name' => 'variants.price-history', 'description' => 'Xem lịch sử giá biến thể', 'group' => 'pricing'],
            ['name' => 'variants.update-price-ajax', 'description' => 'Cập nhật giá biến thể qua AJAX', 'group' => 'pricing'],
        ];

        foreach ($pricingPermissions as $perm) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $perm['name']],
                [
                    'description' => $perm['description'],
                    'group' => $perm['group'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // Bổ sung quyền báo cáo doanh thu
        $reportPermissions = [
            ['name' => 'reports.revenue', 'description' => 'Xem báo cáo doanh thu', 'group' => 'reports'],
            ['name' => 'orders.monitoring', 'description' => 'Xem theo dõi đơn hàng theo thời gian thực', 'group' => 'orders'],
            ['name' => 'orders.monitoring.data', 'description' => 'Lấy dữ liệu theo dõi đơn hàng theo thời gian thực', 'group' => 'orders'],
        ];

        foreach ($reportPermissions as $perm) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $perm['name']],
                [
                    'description' => $perm['description'],
                    'group' => $perm['group'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $modulePermissions = [
            ['name' => 'warehouse.dashboard', 'description' => 'Xem dashboard kho', 'group' => 'warehouse'],
            ['name' => 'warehouse.orders', 'description' => 'Xem danh sách đơn cần đóng gói', 'group' => 'warehouse'],
            ['name' => 'warehouse.orders.start-packing', 'description' => 'Bắt đầu đóng gói đơn hàng', 'group' => 'warehouse'],
            ['name' => 'warehouse.orders.complete-packing', 'description' => 'Hoàn thành đóng gói đơn hàng', 'group' => 'warehouse'],
            ['name' => 'warehouse.returns', 'description' => 'Xem danh sách đơn trả về kho', 'group' => 'warehouse'],
            ['name' => 'warehouse.returns.confirm', 'description' => 'Xác nhận nhập kho hàng trả', 'group' => 'warehouse'],
            ['name' => 'shipper.dashboard', 'description' => 'Xem dashboard shipper', 'group' => 'shipper'],
            ['name' => 'shipper.available', 'description' => 'Xem danh sách đơn có thể nhận', 'group' => 'shipper'],
            ['name' => 'shipper.accept', 'description' => 'Nhận đơn để giao', 'group' => 'shipper'],
            ['name' => 'shipper.my-orders', 'description' => 'Xem danh sách đơn đang giao', 'group' => 'shipper'],
            ['name' => 'shipper.delivered-form', 'description' => 'Mở form xác nhận giao hàng', 'group' => 'shipper'],
            ['name' => 'shipper.mark-delivered', 'description' => 'Xác nhận giao hàng thành công', 'group' => 'shipper'],
            ['name' => 'shipper.return-form', 'description' => 'Mở form trả hàng', 'group' => 'shipper'],
            ['name' => 'shipper.store-return', 'description' => 'Tạo yêu cầu trả hàng về kho', 'group' => 'shipper'],
            ['name' => 'shipper.history', 'description' => 'Xem lịch sử giao hàng', 'group' => 'shipper'],
        ];

        foreach ($modulePermissions as $perm) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $perm['name']],
                [
                    'description' => $perm['description'],
                    'group' => $perm['group'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // Add manage-settings permission
        DB::table('permissions')->updateOrInsert(
            ['name' => 'manage-settings'],
            [
                'description' => 'Manage website settings',
                'group' => 'settings',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        // Add inventories and warehouses permissions
        $newPermissions = [
            // Inventories
            ['name' => 'inventories.view', 'description' => 'View inventories', 'group' => 'inventories'],
            ['name' => 'inventories.create', 'description' => 'Create inventories', 'group' => 'inventories'],
            ['name' => 'inventories.edit', 'description' => 'Edit inventories', 'group' => 'inventories'],
            ['name' => 'inventories.delete', 'description' => 'Delete inventories', 'group' => 'inventories'],
            // Warehouses
            ['name' => 'warehouses.view', 'description' => 'View warehouses', 'group' => 'warehouses'],
            ['name' => 'warehouses.create', 'description' => 'Create warehouses', 'group' => 'warehouses'],
            ['name' => 'warehouses.edit', 'description' => 'Edit warehouses', 'group' => 'warehouses'],
            ['name' => 'warehouses.delete', 'description' => 'Delete warehouses', 'group' => 'warehouses'],
        ];

        foreach ($newPermissions as $perm) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $perm['name']],
                [
                    'description' => $perm['description'],
                    'group' => $perm['group'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $this->syncRolePermissions();
    }

    private function syncRolePermissions(): void
    {
        $snapshot = $this->loadSnapshot();
        $rolePermissionMap = $snapshot['role_permissions'] ?? [];

        if (!is_array($rolePermissionMap) || $rolePermissionMap === []) {
            return;
        }

        foreach ($rolePermissionMap as $roleName => $permissionNames) {
            if (!is_string($roleName) || $roleName === '') {
                continue;
            }

            $role = Role::firstOrCreate(['name' => $roleName]);

            if (!is_array($permissionNames) || $permissionNames === []) {
                $role->permissions()->sync([]);
                continue;
            }

            $permissionIds = Permission::query()
                ->whereIn('name', $permissionNames)
                ->pluck('id')
                ->all();

            $role->permissions()->sync($permissionIds);
        }
    }

    private function loadSnapshot(): array
    {
        $snapshotPath = database_path('seeders/data/rbac_snapshot.json');

        if (!file_exists($snapshotPath)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($snapshotPath), true);

        return is_array($decoded) ? $decoded : [];
    }
}
