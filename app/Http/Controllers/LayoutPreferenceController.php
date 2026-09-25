<?php

namespace App\Http\Controllers;

use App\Services\UserWorkspaceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LayoutPreferenceController extends Controller
{
    public function show(Request $request, UserWorkspaceService $workspaceService): View|RedirectResponse
    {
        $user = $request->user();
        $user->loadMissing('roles');
        $availableWorkspaces = $workspaceService->availableForUser($user);

        if ($user->roles->isEmpty() || empty($availableWorkspaces)) {
            return redirect()->route('pages.my_profile')
                ->withErrors(['workspace' => 'Tài khoản này chưa có layout hợp lệ.']);
        }

        if (count($availableWorkspaces) === 1) {
            $workspace = $availableWorkspaces[0];
            $role = $user->roles->first(function ($assignedRole) use ($workspace) {
                return strcasecmp((string) $assignedRole->name, (string) $workspace['active_role']) === 0;
            });

            $user->update([
                'default_workspace' => $workspace['key'],
                'default_role_id' => $role?->id ?? $user->default_role_id,
            ]);
            $workspaceService->syncSession($workspace);

            return redirect()->route($workspace['route']);
        }

        return view('auth.select-role', [
            'user' => $user,
            'availableWorkspaces' => $availableWorkspaces,
            'currentWorkspaceKey' => old('workspace', session('active_workspace', $user->default_workspace)),
        ]);
    }

    public function store(Request $request, UserWorkspaceService $workspaceService): RedirectResponse
    {
        $validated = $request->validate([
            'workspace' => ['required', 'string', 'max:120'],
            'remember_default' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $user->loadMissing('roles');
        $workspace = $workspaceService->findForUser($user, $validated['workspace']);

        if (!$workspace) {
            return back()
                ->withInput()
                ->withErrors(['workspace' => 'Layout được chọn không hợp lệ hoặc bạn không có quyền.']);
        }

        $role = $user->roles->first(function ($assignedRole) use ($workspace) {
            return in_array(strtolower((string) $assignedRole->name), $workspace['matched_roles'], true);
        });

        $updateData = ['default_role_id' => $role?->id ?? $user->default_role_id];
        if ($request->boolean('remember_default')) {
            $updateData['default_workspace'] = $workspace['key'];
        }

        $user->update($updateData);
        $workspaceService->syncSession($workspace);

        return redirect()->route($workspace['route']);
    }
}
