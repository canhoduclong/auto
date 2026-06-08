<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Setting;
use App\Models\User;
use App\Services\UserWorkspaceService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;


class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login'); // View Limitless
    }
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            return $this->redirectAfterLogin($request, Auth::user());
        }

        return back()
            ->withErrors(['email' => 'Email hoặc mật khẩu không đúng'])
            ->withInput($request->only('email', 'remember'));
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            return redirect()->route('login')
                ->withErrors(['google' => 'Không thể đăng nhập bằng Google. Vui lòng thử lại.']);
        }

        $email = $googleUser->getEmail();

        if (empty($email)) {
            return redirect()->route('login')
                ->withErrors(['google' => 'Tài khoản Google chưa cung cấp email hợp lệ.']);
        }

        $googleId = $googleUser->getId();
        $avatar = $googleUser->getAvatar();

        $user = User::where('google_id', $googleId)->first();

        if ($user === null) {
            $user = User::where('email', $email)->first();

            if ($user !== null) {
                $user->google_id = $googleId;
                if (empty($user->avatar) && !empty($avatar)) {
                    $user->avatar = $avatar;
                }
                $user->google_avatar = $avatar;
                if ($user->email_verified_at === null) {
                    $user->email_verified_at = now();
                }
                $user->save();
            }
        }

        if ($user === null) {
            $user = User::create([
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Google User',
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                'google_id' => $googleId,
                'google_avatar' => $avatar,
                'avatar' => $avatar,
                'email_verified_at' => now(),
            ]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return $this->redirectAfterLogin($request, $user);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function showRegistrationForm()
    {
        if (!Setting::enabled('user_registration_enabled', true)) {
            abort(404);
        }

        return view('auth.register');
    }

    public function register(Request $request)
    {
        if (!Setting::enabled('user_registration_enabled', true)) {
            abort(404);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|confirmed|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Auth::login($user);

        return redirect()->route('pages.my_profile');
    }

    private function redirectAfterLogin(Request $request, User $user)
    {
        $user->loadMissing(['roles', 'defaultRole']);
        $rolesCount = $user->roles->count();

        if ($rolesCount === 0) {
            // User has no role, maybe just redirect to a default safe page
            return redirect()->route('pages.my_profile');
        }

        if ($rolesCount === 1) {
            $role = $user->roles->first();
            $this->setDefaultRole($user, $role);
            return $this->redirectToRoleLayout($request, $role);
        }

        // Multiple roles
        if ($user->defaultRole && $user->roles->contains($user->defaultRole)) {
            return $this->redirectToRoleLayout($request, $user->defaultRole);
        }

        // Invalid or no default role, redirect to selection
        $redirect = redirect()->route('role-selection.show');
        if ($user->default_role_id) {
            $user->update(['default_role_id' => null]);
            $redirect->with('warning', 'Role mặc định không còn hợp lệ. Vui lòng chọn lại.');
        }

        return $redirect;
    }

    private function setDefaultRole(User $user, \App\Models\Role $role)
    {
        if ($user->default_role_id !== $role->id) {
            $user->update(['default_role_id' => $role->id]);
        }
    }

    private function redirectToRoleLayout(Request $request, \App\Models\Role $role)
    {
        if ($this->isMobileRequest($request)) {
            $route = $role->layout_mobile_slug ?? 'mobile.home';
            if (\Illuminate\Support\Facades\Route::has($route)) {
                return redirect()->route($route);
            }
        }

        $route = $role->layout_web_slug ?? 'pages.my_profile';
        if (\Illuminate\Support\Facades\Route::has($route)) {
            return redirect()->route($route);
        }

        return redirect()->route('pages.my_profile');
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
}
