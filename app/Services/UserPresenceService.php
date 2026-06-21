<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPresenceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class UserPresenceService
{
    public function record(User $user, Request $request, string $source): void
    {
        if (!Schema::hasTable('user_presence_logs')) {
            return;
        }

        $cacheKey = 'user:presence:point:' . $source . ':' . $user->id;
        if (!Cache::add($cacheKey, true, now()->addMinutes(5))) {
            return;
        }

        $routeName = $request->route()?->getName();
        $method = strtoupper($request->method());

        UserPresenceLog::create([
            'user_id' => $user->id,
            'source' => $source,
            'observed_at' => now(),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000),
            'reason' => $method . ' ' . ($routeName ?: '/' . ltrim($request->path(), '/')),
            'route' => $routeName ?: $request->path(),
        ]);
    }
}
