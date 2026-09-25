<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class SyncPermissionsFromRoutes extends Command
{
    protected $signature = 'permissions:sync-from-routes';
    protected $description = 'Tạo dữ liệu cho bảng permissions dựa vào route:list và gán full quyền cho vai trò admin';

    public function handle()
    {
        $routes = app('router')->getRoutes();
        $permissions = [];
        foreach ($routes as $route) {
            $name = $route->getName();
            if ($name) {
                $permissions[$name] = [
                    'name' => $name,
                ];
            }
        }

        // Insert or update permissions (chỉ dùng cột name)
        foreach ($permissions as $perm) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $perm['name']],
                []
            );
        }

        // Gán tất cả quyền cho role admin
        $adminRole = DB::table('roles')->where('name', 'admin')->first();
        if (!$adminRole) {
            $this->error('Không tìm thấy vai trò admin!');
            return 1;
        }
        $permissionIds = DB::table('permissions')->pluck('id')->toArray();
        foreach ($permissionIds as $pid) {
            DB::table('permission_role')->updateOrInsert([
                'role_id' => $adminRole->id,
                'permission_id' => $pid
            ]);
        }
        $this->info('Đã đồng bộ permissions và gán full quyền cho admin!');
        return 0;
    }
}
