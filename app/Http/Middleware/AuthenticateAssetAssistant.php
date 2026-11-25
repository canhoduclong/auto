<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAssetAssistant
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = $request->bearerToken();
        $key = is_string($secret) && preg_match('/^asset_[a-f0-9]{64}$/D', $secret)
            ? DB::table('asset_assistant_keys')->where('token_hash', hash('sha256', $secret))->whereNull('revoked_at')->first()
            : null;

        if (! $key) {
            return response()->json(['message' => 'Khóa kết nối tài sản không hợp lệ.'], 401);
        }

        $request->headers->set('Accept', 'application/json');
        $request->attributes->set('asset_assistant_key', $key->id);

        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
