<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Team;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles', 'warehouse', 'team');

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('team_id')) {
            $query->where('team_id', $request->input('team_id'));
        }

        if ($request->filled('role_id')) {
            $roleId = (int) $request->input('role_id');
            $query->whereHas('roles', function ($sub) use ($roleId) {
                $sub->where('roles.id', $roleId);
            });
        }

        $users = $query->orderBy('name')->paginate(15)->appends($request->query());
        $teams = Team::orderBy('name')->get(['id', 'name']);
        $roles = Role::orderBy('name')->get(['id', 'name']);

        return view('users.index', compact('users', 'teams', 'roles'));
    }

    public function bulkAssignTeamForm(Request $request)
    {
        $query = User::with('roles', 'team')->orderBy('name');

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('role_id')) {
            $roleId = (int) $request->input('role_id');
            $query->whereHas('roles', function ($sub) use ($roleId) {
                $sub->where('roles.id', $roleId);
            });
        }

        if ($request->filled('team_id')) {
            $query->where('team_id', $request->input('team_id'));
        }

        $users = $query->paginate(20)->appends($request->query());
        $teams = Team::orderBy('name')->get(['id', 'name']);
        $roles = Role::orderBy('name')->get(['id', 'name']);

        return view('users.bulk_assign_team', compact('users', 'teams', 'roles'));
    }

    public function bulkAssignTeam(Request $request)
    {
        $validated = $request->validate([
            'team_id' => 'nullable|exists:teams,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|exists:users,id',
        ]);

        User::whereIn('id', $validated['user_ids'])->update([
            'team_id' => $validated['team_id'] ?? null,
        ]);

        $message = empty($validated['team_id'])
            ? 'Đã bỏ gán team cho các user đã chọn.'
            : 'Đã gán team cho các user đã chọn.';

        return redirect()->route('users.bulk-assign-team.form')->with('success', $message);
    }

    public function create()
    {
        $roles = Role::all();
        $teams = Team::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        return view('users.create', compact('roles', 'teams', 'warehouses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'roles' => 'array',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'team_id' => 'nullable|exists:teams,id',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'warehouse_id' => $request->warehouse_id,
            'team_id' => $request->team_id,
        ]);

        if ($request->roles) {
            $user->roles()->attach($request->roles);
        }
        /*
        if (!empty($validated['roles'])) {
            $user->roles()->sync($validated['roles']);
        }
        */

        return redirect()->route('users.index')->with('success', 'Tạo user thành công');
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $teams = Team::orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        return view('users.edit', compact('user','roles', 'teams', 'warehouses'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|min:6|confirmed',
            'roles' => 'array',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'team_id' => 'nullable|exists:teams,id',
        ]);

        $user->update([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password ? Hash::make($request->password) : $user->password,
            'warehouse_id' => $request->warehouse_id,
            'team_id' => $request->team_id,
        ]);

        $user->roles()->sync($request->roles ?? []);

        return redirect()->route('users.index')->with('success', 'Cập nhật user thành công');
    }

    public function destroy(User $user)
    {
        $user->roles()->detach();
        $user->delete();
        return redirect()->route('users.index')->with('success', 'Xóa user thành công');
    }
}
