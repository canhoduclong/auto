<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminArea
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin', 'admin/*')) {
            $user = $request->user();
            if (!$user) {
                if ($request->expectsJson()) abort(401, 'Vui lòng đăng nhập.');
                return redirect()->guest(route('login'));
            }
            abort_unless($user->isAdmin(), 403, 'Khu vực quản trị chỉ dành cho Admin.');
        }

        return $next($request);
    }
}
