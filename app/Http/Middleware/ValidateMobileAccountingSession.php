<?php

namespace App\Http\Middleware;

use App\Models\MobileApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ValidateMobileAccountingSession
{
    public function handle(Request $request, Closure $next)
    {
        // A fresh ticket replaces an expired embedded session after signing in again.
        if ($request->routeIs('mobile.accounting.session')) {
            return $next($request);
        }

        if ($request->session()->get('mobile_accounting')) {
            $tokenId = $request->session()->get('mobile_accounting_token_id');
            $token = $tokenId ? MobileApiToken::find($tokenId) : null;
            if (! $token || ($token->expires_at && $token->expires_at->isPast())
                || (int) $token->user_id !== (int) Auth::guard('web')->id()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                abort(401, 'Phiên ứng dụng đã hết hạn. Vui lòng mở lại màn hình từ app.');
            }
        }

        return $next($request);
    }
}
