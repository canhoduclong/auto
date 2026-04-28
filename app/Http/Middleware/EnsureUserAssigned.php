<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserAssigned
{
    /**
     * Chặn những user chưa được gán team hoặc kho vào mọi trang,
     * chỉ cho họ truy cập trang "thank-you", logout, và đổi ngôn ngữ.
     */
    private const ALLOWED_ROUTES = [
        'thankyou',
        'logout',
        'locale.switch',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $this->isUnassigned($user)) {
            $routeName = $request->route()?->getName();

            if (!in_array($routeName, self::ALLOWED_ROUTES, true)) {
                return redirect()->route('thankyou');
            }
        }

        return $next($request);
    }

    private function isUnassigned($user): bool
    {
        // Admin luôn được truy cập
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return false;
        }

        // Nếu đã có role bất kỳ → không phải unassigned
        if (method_exists($user, 'roles') && $user->roles->isNotEmpty()) {
            return false;
        }

        // Nếu có team hoặc kho → không phải unassigned
        if (!empty($user->team_id) || !empty($user->warehouse_id)) {
            return false;
        }

        return true;
    }
}
