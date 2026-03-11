<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles', 'warehouse')->paginate(10);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        $warehouses = Warehouse::orderBy('name')->get();
        return view('users.create', compact('roles', 'warehouses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'roles' => 'array',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'warehouse_id' => $request->warehouse_id,
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
        $warehouses = Warehouse::orderBy('name')->get();
        return view('users.edit', compact('user','roles', 'warehouses'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|min:6|confirmed',
            'roles' => 'array',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        $user->update([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password ? Hash::make($request->password) : $user->password,
            'warehouse_id' => $request->warehouse_id,
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
