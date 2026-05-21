<?php

namespace App\Http\Middleware;

use App\Models\MobileApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobileApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Authorization', '');
        if (!preg_match('/^Bearer\s+(\S+)$/i', $header, $matches)) {
            return response()->json([
                'message' => 'Unauthorized. Missing bearer token.',
            ], 401);
        }

        $tokenHash = MobileApiToken::hashToken($matches[1]);

        $mobileToken = MobileApiToken::query()
            ->with('user.roles')
            ->where('token_hash', $tokenHash)
            ->first();

        if (!$mobileToken || !$mobileToken->user) {
            return response()->json(['message' => 'Unauthorized token.'], 401);
        }

        if ($mobileToken->expires_at && $mobileToken->expires_at->isPast()) {
            $mobileToken->delete();
            return response()->json(['message' => 'Token expired.'], 401);
        }

        $user = $mobileToken->user;
        $request->setUserResolver(fn () => $user);
        $request->attributes->set('mobile_api_token', $mobileToken);

        $lastUsed = $mobileToken->last_used_at;
        if (!$lastUsed || $lastUsed->lt(Carbon::now()->subMinutes(5))) {
            $mobileToken->forceFill(['last_used_at' => now()])->save();
        }

        return $next($request);
    }
}
