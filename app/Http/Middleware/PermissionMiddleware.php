<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, $permission = null)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Bạn chưa đăng nhập.');
        }

        // Admin luôn được phép truy cập các route có middleware permission.
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $next($request);
        }

        // If a permission name is passed as an argument, check for that permission
        if ($permission) {
            if (!$user->hasPermission($permission)) {
                abort(403, 'Bạn không có quyền truy cập: ' . $permission);
            }
            return $next($request);
        }

        // Lấy route name hiện tại (vd: products.index, roles.edit, ...)
        $routeName = $request->route()->getName(); 

        // Nếu không có route name thì bỏ qua
        if (!$routeName) {
            return $next($request);
        }

        $permissionsToCheck = [$routeName];

        // Hỗ trợ map quyền tương đương theo REST để tránh lệch tên quyền.
        if (str_contains($routeName, '.')) {
            [$resource, $action] = explode('.', $routeName, 2);

            $aliases = [
                'store' => 'create',
                'create' => 'store',
                'update' => 'edit',
                'edit' => 'update',
                'destroy' => 'delete',
                'delete' => 'destroy',
                'show' => 'view',
                'view' => 'show',
            ];

            if (isset($aliases[$action])) {
                $permissionsToCheck[] = $resource . '.' . $aliases[$action];
            }
        }

        foreach (array_unique($permissionsToCheck) as $permissionName) {
            if ($user->hasPermission($permissionName)) {
                return $next($request);
            }
        }

        abort(403, 'Bạn không có quyền truy cập route: ' . $routeName);

    }

}
