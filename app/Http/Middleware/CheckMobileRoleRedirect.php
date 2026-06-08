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

        if (!$this->shouldApplyRedirect($request)) {
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

    private function shouldApplyRedirect(Request $request): bool
    {
        if (!in_array($request->method(), ['GET', 'HEAD'], true)) {
            return false;
        }

        if ($request->ajax() || $request->expectsJson() || $request->is('api/*')) {
            return false;
        }

        // Only redirect for real page navigations, not sub-resource/fetch requests.
        $fetchDest = strtolower((string) $request->header('sec-fetch-dest', ''));
        if ($fetchDest !== '' && !in_array($fetchDest, ['document', 'iframe'], true)) {
            return false;
        }

        $fetchMode = strtolower((string) $request->header('sec-fetch-mode', ''));
        if ($fetchMode !== '' && $fetchMode !== 'navigate') {
            return false;
        }

        return true;
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
        $user->loadMissing(['roles', 'defaultRole']);
        
        if ($user->roles->isEmpty()) {
            return null;
        }

        if ($user->roles->count() === 1) {
            return $user->roles->first()->layout_mobile_slug;
        }

        if ($user->defaultRole && $user->roles->contains($user->defaultRole)) {
            return $user->defaultRole->layout_mobile_slug;
        }

        return null;
    }
}
