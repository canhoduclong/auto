<?php

namespace App\Http\Controllers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; 
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    use AuthorizesRequests;
    public function index()
    {
        $this->authorize('viewAny', Role::class); 
        
        $roles = Role::with('permissions')->get();
        return view('roles.index', compact('roles'));
    }
    

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $permissions = Permission::all();
        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'description' => 'nullable|string',
        ]);
        $role = Role::create($request->only(['name', 'description']));
        $role->permissions()->sync($request->permissions ?? []);
        
        return redirect()->route('roles.index')->with('success', 'Thêm role thành công!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $role = Role::findOrFail($id);
        return view('roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified resource.
     */

    public function edit($id)
    {
        $role = Role::findOrFail($id); 
        $permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('id')->toArray();  
        return view('roles.edit', compact('role', 'permissions', 'rolePermissions'));

    }

    public function update(Request $request, $id)
    {
       $role = Role::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'group' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
        ]);

        $role->update([
            'name' => $request->name,
        ]);

        // gán quyền cho role
        $role->permissions()->sync($request->permissions ?? []);

        return redirect()->route('roles.index')->with('success', 'Cập nhật vai trò thành công!');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $role->permissions()->detach();
        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Xóa role thành công');
    }
}
