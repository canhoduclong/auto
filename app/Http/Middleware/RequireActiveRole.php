<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireActiveRole
{
    /**
     * Require the selected workspace role, not merely any role assigned to
     * a multi-role account.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        abort_unless($user, 403);

        if ($user->isAdmin()) {
            return $next($request);
        }

        $allowedRoles = array_map('strtolower', $roles);
        $activeRole = strtolower(trim((string) $request->session()->get('active_role', '')));

        if ($activeRole === '') {
            $user->loadMissing(['defaultRole', 'roles']);
            $activeRole = strtolower(trim((string) ($user->defaultRole?->name ?? '')));

            if ($activeRole === '' && $user->roles->count() === 1) {
                $activeRole = strtolower((string) $user->roles->first()->name);
            }
        }

        abort_unless(
            in_array($activeRole, $allowedRoles, true),
            403,
            'Vui lòng chuyển sang vai trò Đóng hàng để truy cập khu vực này.'
        );

        return $next($request);
    }
}
