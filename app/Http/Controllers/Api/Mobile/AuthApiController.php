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
            ->with(['roles', 'warehouse:id,name'])
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

        if ($layout === 'unsupported' && !$user->hasRole('admin')) {
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
            'id_token' => ['nullable', 'string'],
            'access_token' => ['nullable', 'string'],
            'server_auth_code' => ['nullable', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'platform' => ['nullable', 'string', 'max:32'],
            'app_version' => ['nullable', 'string', 'max:32'],
        ]);

        $idToken = trim((string) ($validated['id_token'] ?? ''));
        $accessToken = trim((string) ($validated['access_token'] ?? ''));

        if ($idToken === '' && $accessToken === '') {
            return $this->fail('Thieu thong tin xac thuc Google.', 422);
        }

        $googlePayload = $this->verifyGoogleCredential($idToken, $accessToken);
        if ($googlePayload === null) {
            return $this->fail('Khong the xac thuc tai khoan Google.', 401);
        }

        $email = strtolower(trim((string) ($googlePayload['email'] ?? '')));
        $googleId = trim((string) ($googlePayload['sub'] ?? ''));
        $emailVerified = filter_var($googlePayload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($email === '' || $googleId === '' || !$emailVerified) {
            return $this->fail('Tai khoan Google chua cung cap email hop le hoac chua xac minh email.', 422);
        }

            $user = User::query()->with(['roles', 'warehouse:id,name'])->where('google_id', $googleId)->first();

        if (!$user) {
            $user = User::query()->with(['roles', 'warehouse:id,name'])->where('email', $email)->first();
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

            $user->load(['roles', 'warehouse:id,name']);
        } else {
            $user->forceFill([
                'google_id' => $user->google_id ?: $googleId,
                'google_avatar' => (string) ($googlePayload['picture'] ?? $user->google_avatar),
                'avatar' => $user->avatar ?: (string) ($googlePayload['picture'] ?? ''),
                'email_verified_at' => $user->email_verified_at ?: now(),
            ])->save();
            $user->loadMissing(['roles', 'warehouse:id,name']);
        }

        $role = $this->resolvePrimaryRole($user);
        $layout = $this->resolveLayout($role);

        if ($layout === 'unsupported' && !$user->hasRole('admin')) {
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

        $user->loadMissing(['roles', 'warehouse:id,name']);
        $role = $this->resolveSelectedMobileRole($user);
        $layout = $this->resolveLayout($role);

        return $this->ok([
            'user' => $this->mobileUserPayload($user, $role, $layout),
        ]);
    }

    public function switchRole(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->fail('Unauthorized', 401);
        }

        $validated = $request->validate([
            'role' => ['required', 'string', 'max:64'],
        ]);

        $user->loadMissing(['roles', 'warehouse:id,name']);
        $role = strtolower(trim((string) $validated['role']));
        $workspace = collect($this->mobileWorkspaces($user))
            ->first(fn (array $item) => strtolower((string) $item['role']) === $role);

        if (!$workspace) {
            return $this->fail('Role khong duoc cap quyen cho tai khoan nay.', 403);
        }

        $roleRecord = Role::query()
            ->whereRaw('LOWER(name) = ?', [$role])
            ->first();
        if ($roleRecord) {
            $user->update(['default_role_id' => $roleRecord->id]);
        } else {
            $user->forceFill(['mobile_selected_role' => $role])->save();
        }

        return $this->ok([
            'user' => $this->mobileUserPayload($user->fresh(['roles', 'warehouse:id,name']), $role, (string) $workspace['layout']),
        ], 'Da chuyen vai tro');
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

    private function verifyGoogleCredential(string $idToken, string $accessToken): ?array
    {
        if ($idToken !== '') {
            $idTokenPayload = $this->verifyGoogleIdToken($idToken);
            if ($idTokenPayload !== null) {
                return $idTokenPayload;
            }
        }

        if ($accessToken !== '') {
            return $this->verifyGoogleAccessToken($accessToken);
        }

        return null;
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

    private function verifyGoogleAccessToken(string $accessToken): ?array
    {
        try {
            $tokenInfoResponse = Http::timeout(10)
                ->acceptJson()
                ->get('https://www.googleapis.com/oauth2/v1/tokeninfo', [
                    'access_token' => $accessToken,
                ]);
        } catch (Throwable) {
            return null;
        }

        if (!$tokenInfoResponse->ok()) {
            return null;
        }

        $tokenInfo = $tokenInfoResponse->json();
        if (!is_array($tokenInfo)) {
            return null;
        }

        $allowedClientIds = collect(explode(',', (string) env('MOBILE_GOOGLE_CLIENT_IDS', (string) config('services.google.client_id'))))
            ->map(fn ($clientId) => trim($clientId))
            ->filter()
            ->values();

        $audience = trim((string) ($tokenInfo['audience'] ?? ''));
        $issuedTo = trim((string) ($tokenInfo['issued_to'] ?? ''));
        if ($allowedClientIds->isEmpty() || (!$allowedClientIds->contains($audience) && !$allowedClientIds->contains($issuedTo))) {
            return null;
        }

        try {
            $userInfoResponse = Http::timeout(10)
                ->acceptJson()
                ->withToken($accessToken)
                ->get('https://www.googleapis.com/oauth2/v3/userinfo');
        } catch (Throwable) {
            return null;
        }

        if (!$userInfoResponse->ok()) {
            return null;
        }

        $userInfo = $userInfoResponse->json();
        if (!is_array($userInfo)) {
            return null;
        }

        if ($audience !== '' && !isset($userInfo['aud'])) {
            $userInfo['aud'] = $audience;
        }

        return $userInfo;
    }

    private function mobileUserPayload(User $user, string $role, string $layout): array
    {
        $workspaces = $this->mobileWorkspaces($user);
        if (empty($workspaces) && $layout !== 'unsupported') {
            $workspaces[] = [
                'role' => $role,
                'layout' => $layout,
                'label' => $this->mobileWorkspaceLabel($layout),
                'menu' => $this->mobileMenuByLayout($layout, $user->hasRole('manager_shipper') || $user->hasRole('admin'), $user->roles->pluck('name')->all()),
            ];
        }

        $roleLabel = (string) ($user->job_title ?: ($user->roles
            ->first(fn ($item) => strtolower((string) $item->name) === strtolower($role))?->display_name
            ?: $user->roles->first(fn ($item) => strtolower((string) $item->name) === strtolower($role))?->name
            ?: $role));
        $avatarUrl = $this->mobileAvatarUrl($user);

        return [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'short_name' => (string) ($user->short_name ?? ''),
            'email' => (string) $user->email,
            'phone' => (string) ($user->phone ?? ''),
            'avatar_url' => $avatarUrl,
            'warehouse_id' => $user->warehouse_id ? (int) $user->warehouse_id : null,
            'warehouse_name' => (string) ($user->warehouse?->name ?? ''),
            'roles' => $user->roles->pluck('name')->values(),
            'role' => $role,
            'role_label' => $roleLabel,
            'layout' => $layout,
            'theme' => $this->mobileThemePayload($layout),
            'profile_card' => $this->mobileProfileCardPayload($user, $roleLabel, $layout, $avatarUrl),
            'menu' => $this->mobileMenuByLayout($layout, $user->hasRole('manager_shipper') || $user->hasRole('admin'), $user->roles->pluck('name')->all()),
            'workspaces' => $workspaces,
        ];
    }

    private function mobileAvatarUrl(User $user): string
    {
        $avatar = trim((string) ($user->avatar ?: $user->google_avatar ?: ''));
        if ($avatar === '') {
            return '';
        }

        if (str_starts_with($avatar, 'http://') || str_starts_with($avatar, 'https://')) {
            return $avatar;
        }

        if (str_starts_with($avatar, 'avatars/') || str_starts_with($avatar, 'storage/')) {
            return asset(ltrim($avatar, '/'));
        }

        return asset('storage/' . ltrim($avatar, '/'));
    }

    private function mobileThemePayload(string $layout): array
    {
        $accent = match ($layout) {
            'ceo', 'accounting' => '#F2C66D',
            'warehouse' => '#74D6C7',
            'shipper', 'manager_shipper' => '#F5B86A',
            'sale' => '#F4D27C',
            default => '#F4D27C',
        };

        return [
            'name' => 'heritage_navy',
            'background' => '#031B32',
            'background_secondary' => '#052846',
            'foreground' => '#FFFFFF',
            'muted_foreground' => '#D7E0EA',
            'accent' => $accent,
            'card_radius' => 28,
            'pattern' => [
                'type' => 'dong_son_lotus',
                'opacity' => 0.18,
                'stroke' => '#F4D27C',
                'placement' => 'top_right',
            ],
        ];
    }

    private function mobileProfileCardPayload(User $user, string $roleLabel, string $layout, string $avatarUrl): array
    {
        return [
            'style' => 'heritage_staff_card',
            'layout' => $layout,
            'brand_text' => 'HL',
            'name' => (string) $user->name,
            'role_label' => $roleLabel,
            'email' => (string) $user->email,
            'phone' => (string) ($user->phone ?? ''),
            'avatar_url' => $avatarUrl,
            'use_user_avatar' => true,
            'background' => [
                'gradient' => ['#031B32', '#052846', '#061F38'],
                'pattern' => 'dong_son_lotus',
                'pattern_color' => '#F4D27C',
                'pattern_opacity' => 0.18,
            ],
            'text_shadow' => true,
        ];
    }

    private function mobileWorkspaceLabel(string $layout): string
    {
        return match ($layout) {
            'warehouse' => 'Kho',
            'manager_shipper' => 'Shipper Manager',
            'shipper' => 'Shipper',
            'sale' => 'Sale',
            'accounting' => 'Accounting',
            'ceo' => 'CEO',
            default => $layout,
        };
    }
}
