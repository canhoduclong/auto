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
        $layoutCatalog = config('workspaces.catalog', []);

        return view('roles.create', compact('permissions', 'layoutCatalog'));
    }

    public function store(Request $request)
    {
        $webLayoutSlugs = implode(',', $this->layoutSlugsForPlatform('website'));
        $mobileLayoutSlugs = implode(',', $this->layoutSlugsForPlatform('my_app'));

        $request->validate([
            'name' => 'required|unique:roles,name',
            'description' => 'nullable|string',
            'layout_web_name' => 'nullable|string|max:255',
            'layout_web_slug' => 'required|string|max:120|in:' . $webLayoutSlugs,
            'layout_mobile_name' => 'nullable|string|max:255',
            'layout_mobile_slug' => 'required|string|max:120|in:' . $mobileLayoutSlugs,
        ]);

        $catalog = config('workspaces.catalog', []);
        
        $layoutWebSlug = trim((string) $request->input('layout_web_slug', ''));
        $layoutWebName = trim((string) $request->input('layout_web_name', ''));
        
        $layoutMobileSlug = trim((string) $request->input('layout_mobile_slug', ''));
        $layoutMobileName = trim((string) $request->input('layout_mobile_name', ''));

        $compatibilityWebError = $this->layoutCompatibilityError((string) $request->input('name'), $layoutWebSlug, $catalog);
        if ($compatibilityWebError !== null) {
            return back()->withInput()->withErrors(['layout_web_slug' => $compatibilityWebError]);
        }
        
        $compatibilityMobileError = $this->layoutCompatibilityError((string) $request->input('name'), $layoutMobileSlug, $catalog);
        if ($compatibilityMobileError !== null) {
            return back()->withInput()->withErrors(['layout_mobile_slug' => $compatibilityMobileError]);
        }

        if ($layoutWebSlug !== '' && $layoutWebName === '' && isset($catalog[$layoutWebSlug]['label'])) {
            $layoutWebName = (string) $catalog[$layoutWebSlug]['label'];
        }
        
        if ($layoutMobileSlug !== '' && $layoutMobileName === '' && isset($catalog[$layoutMobileSlug]['label'])) {
            $layoutMobileName = (string) $catalog[$layoutMobileSlug]['label'];
        }

        $role = Role::create([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'layout_web_name' => $layoutWebName !== '' ? $layoutWebName : null,
            'layout_web_slug' => $layoutWebSlug !== '' ? $layoutWebSlug : null,
            'layout_mobile_name' => $layoutMobileName !== '' ? $layoutMobileName : null,
            'layout_mobile_slug' => $layoutMobileSlug !== '' ? $layoutMobileSlug : null,
        ]);
        $role->permissions()->sync($request->permissions ?? []);
        
        return redirect()->route('roles.index')->with('success', __('roles.messages.created'));
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
        $layoutCatalog = config('workspaces.catalog', []);

        return view('roles.edit', compact('role', 'permissions', 'rolePermissions', 'layoutCatalog'));

    }

    public function update(Request $request, $id)
    {
       $role = Role::findOrFail($id);
       $webLayoutSlugs = implode(',', $this->layoutSlugsForPlatform('website'));
       $mobileLayoutSlugs = implode(',', $this->layoutSlugsForPlatform('my_app'));

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'group' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'layout_web_name' => 'nullable|string|max:255',
            'layout_web_slug' => 'required|string|max:120|in:' . $webLayoutSlugs,
            'layout_mobile_name' => 'nullable|string|max:255',
            'layout_mobile_slug' => 'required|string|max:120|in:' . $mobileLayoutSlugs,
        ]);

        $catalog = config('workspaces.catalog', []);
        $layoutWebSlug = trim((string) $request->input('layout_web_slug', ''));
        $layoutWebName = trim((string) $request->input('layout_web_name', ''));
        
        $layoutMobileSlug = trim((string) $request->input('layout_mobile_slug', ''));
        $layoutMobileName = trim((string) $request->input('layout_mobile_name', ''));

        $compatibilityWebError = $this->layoutCompatibilityError((string) $request->input('name'), $layoutWebSlug, $catalog);
        if ($compatibilityWebError !== null) {
            return back()->withInput()->withErrors(['layout_web_slug' => $compatibilityWebError]);
        }

        $compatibilityMobileError = $this->layoutCompatibilityError((string) $request->input('name'), $layoutMobileSlug, $catalog);
        if ($compatibilityMobileError !== null) {
            return back()->withInput()->withErrors(['layout_mobile_slug' => $compatibilityMobileError]);
        }

        if ($layoutWebSlug !== '' && $layoutWebName === '' && isset($catalog[$layoutWebSlug]['label'])) {
            $layoutWebName = (string) $catalog[$layoutWebSlug]['label'];
        }
        
        if ($layoutMobileSlug !== '' && $layoutMobileName === '' && isset($catalog[$layoutMobileSlug]['label'])) {
            $layoutMobileName = (string) $catalog[$layoutMobileSlug]['label'];
        }

        $role->update([
            'name' => $request->name,
            'description' => $request->description,
            'layout_web_name' => $layoutWebName !== '' ? $layoutWebName : null,
            'layout_web_slug' => $layoutWebSlug !== '' ? $layoutWebSlug : null,
            'layout_mobile_name' => $layoutMobileName !== '' ? $layoutMobileName : null,
            'layout_mobile_slug' => $layoutMobileSlug !== '' ? $layoutMobileSlug : null,
        ]);

        // gán quyền cho role
        $role->permissions()->sync($request->permissions ?? []);

        return redirect()->route('roles.index')->with('success', __('roles.messages.updated'));
    }

    private function layoutSlugsForPlatform(string $platform): array
    {
        return collect(config('workspaces.catalog', []))
            ->filter(fn (array $layout) => ($layout['platform'] ?? 'website') === $platform)
            ->keys()
            ->all();
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);
        $role->permissions()->detach();
        $role->delete();
        return redirect()->route('roles.index')->with('success', __('roles.messages.deleted'));
    }

    private function layoutCompatibilityError(string $roleName, string $layoutSlug, array $catalog): ?string
    {
        if ($layoutSlug === '' || !isset($catalog[$layoutSlug])) {
            return null;
        }

        $hints = collect($catalog[$layoutSlug]['role_hints'] ?? [])
            ->map(fn ($item) => strtolower(trim((string) $item)))
            ->filter()
            ->values()
            ->all();

        if ($hints === []) {
            return null;
        }

        $normalizedRole = strtolower(trim($roleName));
        if ($normalizedRole !== '' && !in_array($normalizedRole, $hints, true)) {
            return 'Layout slug khong phu hop voi ten role hien tai. Vui long chon slug dung vai tro.';
        }

        return null;
    }
}
