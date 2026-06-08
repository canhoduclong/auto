<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LayoutPreferenceController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $user->loadMissing('roles');
        $roles = $user->roles;

        if ($roles->isEmpty()) {
            return redirect()->route('pages.my_profile')
                ->withErrors(['role' => 'Tài khoản này chưa có role hợp lệ.']);
        }

        if ($roles->count() === 1) {
            $role = $roles->first();
            $user->update(['default_role_id' => $role->id]);
            return $this->redirectToRoleLayout($request, $role);
        }

        return view('auth.select-role', [
            'user' => $user,
            'roles' => $roles,
            'currentRoleId' => old('role_id', $user->default_role_id),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $user = $request->user();
        $user->loadMissing('roles');
        
        $role = $user->roles->firstWhere('id', $validated['role_id']);

        if (!$role) {
            return back()
                ->withInput()
                ->withErrors(['role_id' => 'Role được chọn không hợp lệ hoặc bạn không có quyền.']);
        }

        $user->update(['default_role_id' => $role->id]);

        return $this->redirectToRoleLayout($request, $role);
    }

    private function redirectToRoleLayout(Request $request, \App\Models\Role $role): RedirectResponse
    {
        // For web, redirect to layout_web_slug
        $route = $role->layout_web_slug ?? 'pages.my_profile';
        
        if (\Illuminate\Support\Facades\Route::has($route)) {
            return redirect()->route($route);
        }

        return redirect()->route('pages.my_profile');
    }
}