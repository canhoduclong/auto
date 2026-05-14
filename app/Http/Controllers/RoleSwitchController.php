<?php

namespace App\Http\Controllers;

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
        
        // Store the selected role in session (use lowercase for consistency)
        $roleLower = strtolower($role);
        session(['active_role' => $roleLower]);
        
        // Redirect to appropriate dashboard
        return match($roleLower) {
            'admin' => redirect()->route('dashboard'),
            'ceo' => redirect()->route('ceo.dashboard'),
            'accountant', 'accounting' => redirect()->route('accounting.dashboard'),
            'warehouse' => redirect()->route('warehouse.dashboard'),
            'shipper' => redirect()->route('shipper.dashboard'),
            'sale', 'leader', 'leader_sale', 'sale_manager', 'manager', 'manager_sale' => redirect()->route('pages.my_dashboard'),
            default => redirect()->route('dashboard')->with('error', 'Vai trò không hợp lệ: ' . $role),
        };
    }

    /**
     * Clear the active role (fall back to role priority)
     */
    public function clear()
    {
        session()->forget('active_role');
        return redirect()->route('dashboard');
    }
}
