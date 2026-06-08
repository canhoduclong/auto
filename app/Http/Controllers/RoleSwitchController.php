<?php

namespace App\Http\Controllers;

use App\Services\UserWorkspaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class RoleSwitchController extends Controller
{
    /**
     * Switch the active role for the current user
     */
    public function switch(Request $request, string $role, UserWorkspaceService $workspaceService)
    {
        $user = Auth::user();
        $role = strtolower(trim($role));
        
        // Validate that user has this role
        if (!$user->hasRole($role)) {
            return redirect()->route('dashboard')->with('error', 'Bạn không có quyền truy cập vai trò này.');
        }

        $user->loadMissing('roles');
        $roleModel = $user->roles->first(function ($assignedRole) use ($role) {
            return strcasecmp((string) $assignedRole->name, $role) === 0;
        });

        if (!$roleModel) {
            return redirect()->route('dashboard')->with('error', 'Không tìm thấy vai trò hợp lệ.');
        }

        $workspace = collect($workspaceService->availableForUser($user))
            ->first(function (array $workspace) use ($role): bool {
                return in_array($role, $workspace['matched_roles'], true);
            });

        $updateData = ['default_role_id' => $roleModel->id];
        if ($workspace !== null) {
            $updateData['default_workspace'] = $workspace['key'];
        }

        $user->update($updateData);

        session(['active_role' => $role]);

        if ($workspace !== null) {
            $workspaceService->syncSession(array_replace($workspace, ['active_role' => $role]));

            return redirect()->route($workspace['route']);
        }

        $catalogRoute = config('workspaces.catalog.' . $roleModel->layout_web_slug . '.route');
        $route = $catalogRoute ?: $roleModel->layout_web_slug ?: $this->fallbackRouteForRole($role);
        
        if (Route::has($route)) {
            return redirect()->route($route);
        }

        return redirect()->route('pages.my_profile');
    }

    private function fallbackRouteForRole(string $role): string
    {
        if (in_array($role, ['accountant', 'accounting'], true)) {
            return 'accounting.dashboard';
        }

        if (in_array($role, ['sale', 'leader', 'leader_sale', 'sale_manager', 'manager', 'manager_sale'], true)) {
            return 'pages.my_dashboard';
        }

        return match ($role) {
            'admin' => 'dashboard',
            'ceo' => 'ceo.dashboard',
            'manager_shipper', 'shipper', 'ship' => 'shipper.dashboard',
            'package' => 'package.dashboard',
            'warehouse' => 'warehouse.dashboard',
            default => 'pages.my_profile',
        };
    }

    public function clear()
    {
        session()->forget(['active_role', 'active_workspace']);
        return redirect()->route('dashboard');
    }
}
