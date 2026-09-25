<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserWorkspaceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit(UserWorkspaceService $workspaceService)
    {
        $user = Auth::user();

        return view('profile.edit', [
            'user' => $user,
            'workspaceOptions' => $workspaceService->availableForUser($user),
        ]);
    }

    public function update(Request $request, UserWorkspaceService $workspaceService)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'default_workspace' => ['nullable', 'string', 'max:120'],
        ]);

        $requestedWorkspace = $request->filled('default_workspace')
            ? (string) $request->input('default_workspace')
            : null;

        if ($requestedWorkspace !== null && $workspaceService->findForUser($user, $requestedWorkspace) === null) {
            return back()
                ->withInput()
                ->withErrors(['default_workspace' => 'Layout mac dinh duoc chon khong con hop le voi vai tro hien tai.']);
        }

        $data = $request->only('name', 'email');
        $data['default_workspace'] = $requestedWorkspace;

        if ($request->hasFile('avatar')) {
            $avatarName = time().'.'.$request->avatar->getClientOriginalExtension();
            $request->avatar->move(public_path('avatars'), $avatarName);
            $data['avatar'] = 'avatars/' . $avatarName;
        }

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('profile.edit')->with('success', 'Hồ sơ đã được cập nhật.');
    }
}