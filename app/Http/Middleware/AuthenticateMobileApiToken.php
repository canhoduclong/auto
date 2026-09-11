<?php

namespace App\Http\Middleware;

use App\Models\MobileApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Services\UserPresenceService;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobileApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $header = (string) $request->header('Authorization', '');
        $plainTextToken = preg_match('/^Bearer\s+(\S+)$/i', $header, $matches)
            ? $matches[1]
            : trim((string) ($request->header('X-Mobile-Token', '') ?: $request->input('mobile_token', '')));
        if ($plainTextToken === '') {
            return response()->json([
                'message' => 'Unauthorized. Missing bearer token.',
            ], 401);
        }

        $tokenHash = MobileApiToken::hashToken($plainTextToken);

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

        app(UserPresenceService::class)->record($user, $request, 'mobile');

        return $next($request);
    }
}
