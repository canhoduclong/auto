<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;


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

            $user = Auth::user();

            if ($user->hasRole('admin')) {
                return redirect()->route('dashboard');
            }

            if ($user->hasRole('ceo')) {
                return redirect()->route('ceo.dashboard');
            }

            if ($user->hasRole('warehouse')) {
                return redirect()->route('warehouse.dashboard');
            }

            if ($user->hasRole('shipper')) {
                return redirect()->route('shipper.dashboard');
            }

            if ($user->hasRole('accountant') || $user->hasRole('accounting')) {
                return redirect()->route('accounting.dashboard');
            }

            $isSalesLikeUser = $user->isSalesFlowRole()
                || $user->hasPermission('pages.my_orders')
                || $user->hasPermission('orders.monitoring')
                || $user->hasPermission('work-reports.index')
                || $user->canAccessSalesDailyFeatures();

            if ($isSalesLikeUser) {
                if ($user->hasPermission('orders.monitoring')) {
                    return redirect()->route('pages.my_orders.monitoring');
                }

                return redirect()->route('pages.my_orders');
            }

            return redirect()->route('pages.my_dashboard');
        }

        return back()
            ->withErrors(['email' => 'Email hoặc mật khẩu không đúng'])
            ->withInput($request->only('email', 'remember'));
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
        return view('auth.register');
    }

    public function register(Request $request)
    {
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

        return redirect()->route('pages.my_dashboard');
    }
}
