<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TrackUserOnlineStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $cacheKey = 'user:last_seen:write:' . $user->id;

            // Limit writes to once per minute per user.
            if (!Cache::has($cacheKey)) {
                $user->forceFill([
                    'last_seen_at' => now(),
                ])->saveQuietly();

                Cache::put($cacheKey, true, now()->addMinute());
            }
        }

        return $next($request);
    }
}
