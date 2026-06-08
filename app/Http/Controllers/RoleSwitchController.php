<?php

namespace App\Http\Controllers;

use App\Services\UserWorkspaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleSwitchController extends Controller
{
    /**
     * Switch the active role for the current user
     */
    public function switch(Request $request, string $role)
    {
        $user = Auth::user();
        
        // Validate that user has this role
        if (!$user->hasRole($role)) {
            return redirect()->route('dashboard')->with('error', 'Bạn không có quyền truy cập vai trò này.');
        }

        $roleModel = \App\Models\Role::where('name', $role)->first();
        if ($roleModel) {
            $user->update(['default_role_id' => $roleModel->id]);
        }

        // Web Redirect based on Role config
        $route = $roleModel->layout_web_slug ?? 'pages.my_profile';
        
        if (\Illuminate\Support\Facades\Route::has($route)) {
            return redirect()->route($route);
        }

        return redirect()->route('pages.my_profile');
    }

    public function clear()
    {
        session()->forget(['active_role', 'active_workspace']);
        return redirect()->route('dashboard');
    }
}
