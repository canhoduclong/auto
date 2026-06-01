<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\Concerns\ResolvesMobileRole;
use App\Http\Controllers\Controller;
use App\Models\MobileApiToken;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class AuthApiController extends BaseApiController
{
    use ResolvesMobileRole;

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'platform' => ['nullable', 'string', 'max:32'],
            'app_version' => ['nullable', 'string', 'max:32'],
        ]);

        $login = trim((string) $validated['email']);
        $user = User::query()
            ->with('roles')
            ->where(function ($query) use ($login): void {
                $query->where('email', $login)
                    ->orWhere('phone', $login);
            })
            ->first();

        if (!$user || !Hash::check($validated['password'], (string) $user->password)) {
            return $this->fail('Sai email hoac mat khau.', 401);
        }

        $role = $this->resolvePrimaryRole($user);
        $layout = $this->resolveLayout($role);

        if (!in_array($layout, ['shipper', 'warehouse'], true) && !$user->hasRole('admin')) {
            return $this->fail('Tai khoan nay khong thuoc role mobile duoc ho tro.', 403);
        }

        $plainToken = MobileApiToken::generatePlainTextToken();

        $token = MobileApiToken::query()->create([
            'user_id' => $user->id,
            'name' => 'flutter-mobile',
            'token_hash' => MobileApiToken::hashToken($plainToken),
            'device_name' => $validated['device_name'] ?? null,
            'platform' => $validated['platform'] ?? null,
            'app_version' => $validated['app_version'] ?? null,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'last_used_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        return $this->ok([
            'token' => $plainToken,
            'token_type' => 'Bearer',
            'expires_at' => optional($token->expires_at)->toIso8601String(),
            'user' => $this->mobileUserPayload($user, $role, $layout),
        ], 'Dang nhap thanh cong');
    }

    public function googleLogin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_token' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'platform' => ['nullable', 'string', 'max:32'],
            'app_version' => ['nullable', 'string', 'max:32'],
        ]);

        $googlePayload = $this->verifyGoogleIdToken((string) $validated['id_token']);
        if ($googlePayload === null) {
            return $this->fail('Khong the xac thuc tai khoan Google.', 401);
        }

        $email = strtolower(trim((string) ($googlePayload['email'] ?? '')));
        $googleId = trim((string) ($googlePayload['sub'] ?? ''));
        $emailVerified = filter_var($googlePayload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($email === '' || $googleId === '' || !$emailVerified) {
            return $this->fail('Tai khoan Google chua cung cap email hop le hoac chua xac minh email.', 422);
        }

        $user = User::query()->with('roles')->where('google_id', $googleId)->first();

        if (!$user) {
            $user = User::query()->with('roles')->where('email', $email)->first();
        }

        if (!$user) {
            $user = User::query()->create([
                'name' => (string) ($googlePayload['name'] ?? $googlePayload['given_name'] ?? 'Google User'),
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                'google_id' => $googleId,
                'google_avatar' => (string) ($googlePayload['picture'] ?? ''),
                'avatar' => (string) ($googlePayload['picture'] ?? ''),
                'email_verified_at' => now(),
            ]);

            $defaultRole = (string) env('MOBILE_DEFAULT_ROLE', 'shipper');
            $role = Role::query()->where('name', $defaultRole)->first();
            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }

            $user->load('roles');
        } else {
            $user->forceFill([
                'google_id' => $user->google_id ?: $googleId,
                'google_avatar' => (string) ($googlePayload['picture'] ?? $user->google_avatar),
                'avatar' => $user->avatar ?: (string) ($googlePayload['picture'] ?? ''),
                'email_verified_at' => $user->email_verified_at ?: now(),
            ])->save();
            $user->loadMissing('roles');
        }

        $role = $this->resolvePrimaryRole($user);
        $layout = $this->resolveLayout($role);

        if (!in_array($layout, ['shipper', 'warehouse'], true) && !$user->hasRole('admin')) {
            return $this->fail('Tai khoan Google nay chua duoc gan role mobile duoc ho tro.', 403);
        }

        $plainToken = MobileApiToken::generatePlainTextToken();
        $token = MobileApiToken::query()->create([
            'user_id' => $user->id,
            'name' => 'flutter-mobile-google',
            'token_hash' => MobileApiToken::hashToken($plainToken),
            'device_name' => $validated['device_name'] ?? null,
            'platform' => $validated['platform'] ?? null,
            'app_version' => $validated['app_version'] ?? null,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'last_used_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        return $this->ok([
            'token' => $plainToken,
            'token_type' => 'Bearer',
            'expires_at' => optional($token->expires_at)->toIso8601String(),
            'user' => $this->mobileUserPayload($user, $role, $layout),
        ], 'Dang nhap Google thanh cong');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->fail('Unauthorized', 401);
        }

        $user->loadMissing('roles');
        $role = $this->resolvePrimaryRole($user);
        $layout = $this->resolveLayout($role);

        return $this->ok([
            'user' => $this->mobileUserPayload($user, $role, $layout),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $mobileToken = $request->attributes->get('mobile_api_token');
        if ($mobileToken instanceof MobileApiToken) {
            $mobileToken->delete();
        }

        return $this->ok(null, 'Logged out');
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $request->attributes->get('mobile_api_token');

        if (!$user || !$currentToken instanceof MobileApiToken) {
            return $this->fail('Unauthorized', 401);
        }

        $plainToken = MobileApiToken::generatePlainTextToken();

        $newToken = MobileApiToken::query()->create([
            'user_id' => $user->id,
            'name' => 'flutter-mobile-refresh',
            'token_hash' => MobileApiToken::hashToken($plainToken),
            'device_name' => $currentToken->device_name,
            'platform' => $currentToken->platform,
            'app_version' => $currentToken->app_version,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'last_used_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $currentToken->delete();

        return $this->ok([
            'token' => $plainToken,
            'token_type' => 'Bearer',
            'expires_at' => optional($newToken->expires_at)->toIso8601String(),
        ], 'Token refreshed');
    }

    public function sessions(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->fail('Unauthorized', 401);
        }

        $tokens = MobileApiToken::query()
            ->where('user_id', $user->id)
            ->latest('last_used_at')
            ->get()
            ->map(fn (MobileApiToken $token) => [
                'id' => (int) $token->id,
                'device_name' => (string) ($token->device_name ?? ''),
                'platform' => (string) ($token->platform ?? ''),
                'app_version' => (string) ($token->app_version ?? ''),
                'last_used_at' => optional($token->last_used_at)->toIso8601String(),
                'expires_at' => optional($token->expires_at)->toIso8601String(),
            ])
            ->values();

        return $this->ok($tokens);
    }

    public function revokeSession(Request $request, int $sessionId): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->fail('Unauthorized', 401);
        }

        MobileApiToken::query()
            ->where('user_id', $user->id)
            ->where('id', $sessionId)
            ->delete();

        return $this->ok(null, 'Session revoked');
    }

    private function verifyGoogleIdToken(string $idToken): ?array
    {
        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->get('https://oauth2.googleapis.com/tokeninfo', [
                    'id_token' => $idToken,
                ]);
        } catch (Throwable) {
            return null;
        }

        if (!$response->ok()) {
            return null;
        }

        $payload = $response->json();
        if (!is_array($payload)) {
            return null;
        }

        $allowedClientIds = collect(explode(',', (string) env('MOBILE_GOOGLE_CLIENT_IDS', (string) config('services.google.client_id'))))
            ->map(fn ($clientId) => trim($clientId))
            ->filter()
            ->values();

        if ($allowedClientIds->isEmpty() || !$allowedClientIds->contains((string) ($payload['aud'] ?? ''))) {
            return null;
        }

        return $payload;
    }

    private function mobileUserPayload(User $user, string $role, string $layout): array
    {
        return [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'phone' => (string) ($user->phone ?? ''),
            'warehouse_id' => $user->warehouse_id ? (int) $user->warehouse_id : null,
            'roles' => $user->roles->pluck('name')->values(),
            'role' => $role,
            'layout' => $layout,
            'menu' => $this->mobileMenuByLayout($layout, $user->hasRole('manager_shipper') || $user->hasRole('admin')),
        ];
    }
}
