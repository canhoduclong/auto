<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMobileRoleRedirect
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return $next($request);
        }

        $isMobile = $this->isMobileRequest($request);
        $isMobilePath = $request->is('m') || $request->is('m/*');

        if ($isMobile && !$isMobilePath) {
            $mobileRoute = $this->resolveMobileRoute($user);

            if ($mobileRoute !== null && !$request->routeIs($mobileRoute)) {
                return redirect()->route($mobileRoute);
            }
        }

        // Allow users to open /m/* explicitly on desktop (useful for testing and hybrid workflows).
        // We only force mobile redirect when device is mobile and user is on desktop routes.

        return $next($request);
    }

    private function resolveMobileRoute($user): ?string
    {
        if ($user->hasRole('warehouse')) {
            return 'mobile.warehouse.home';
        }

        if ($user->hasRole('shipper') || $user->hasRole('ship')) {
            return 'mobile.shipper.home';
        }

        if ($this->isSalesLikeUser($user)) {
            return 'mobile.sale.home';
        }

        return null;
    }

    private function isSalesLikeUser($user): bool
    {
        return $user->isSalesFlowRole()
            || $user->hasPermission('pages.my_orders')
            || $user->hasPermission('orders.monitoring')
            || $user->hasPermission('work-reports.index')
            || $user->canAccessSalesDailyFeatures();
    }

    private function isMobileRequest(Request $request): bool
    {
        $uaMobile = (string) $request->header('sec-ch-ua-mobile', '');
        if ($uaMobile === '?1') {
            return true;
        }

        $agent = strtolower((string) $request->userAgent());

        return preg_match('/iphone|ipod|android|blackberry|opera mini|windows phone|mobile|webos|iemobile|ipad/', $agent) === 1;
    }
}
