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

        // Nếu user không có quyền theo route name -> chặn
        if (!$user->hasPermission($routeName)) {
            abort(403, 'Bạn không có quyền truy cập route: ' . $routeName);
        }

        return $next($request);
    }

}
