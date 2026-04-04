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

        if ($request->is('m') || $request->is('m/*')) {
            return $next($request);
        }

        if ($request->ajax() || $request->expectsJson() || $request->is('api/*')) {
            return $next($request);
        }

        if ($request->query('desktop') === '1') {
            return $next($request);
        }

        if (!$this->isMobileRequest($request)) {
            return $next($request);
        }

        $mobileRoute = $this->resolveMobileRoute($user);
        if ($mobileRoute !== null) {
            return redirect()->route($mobileRoute);
        }

        return $next($request);
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

    private function resolveMobileRoute($user): ?string
    {
        if ($user->hasRole('warehouse')) {
            return 'mobile.warehouse.home';
        }

        if ($user->hasRole('shipper') || $user->hasRole('ship')) {
            return 'mobile.shipper.home';
        }

        $isSalesLikeUser = $user->isSalesFlowRole()
            || $user->hasPermission('pages.my_orders')
            || $user->hasPermission('orders.monitoring')
            || $user->hasPermission('work-reports.index')
            || $user->canAccessSalesDailyFeatures();

        if ($isSalesLikeUser) {
            return 'mobile.sale.home';
        }

        return null;
    }
}
