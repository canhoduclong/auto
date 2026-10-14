<?php

namespace App\Http\Middleware;

use App\Support\SalesTaxPolicy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class RequireSalesTaxPolicyAcceptance
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! SalesTaxPolicy::appliesTo($user) || SalesTaxPolicy::isAccepted($user)) {
            return $next($request);
        }

        if ($request->routeIs('sales-tax-policy.*', 'logout')) {
            return $next($request);
        }

        if (! $request->isMethodSafe()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Bạn phải xác nhận nội quy về thuế trước khi tiếp tục.'], 428);
            }
            return redirect()->route('sales-tax-policy.show')->with('error', 'Vui lòng đọc và xác nhận nội quy trước khi tiếp tục.');
        }

        View::share('showSalesTaxPolicyPrompt', true);
        return $next($request);
    }
}
