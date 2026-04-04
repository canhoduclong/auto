<?php 
namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::query()
            ->orderBy('group')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => (int) $permissions->count(),
            'with_route_meta' => (int) $permissions->filter(fn (Permission $p) => !empty($p->uri) || !empty($p->method))->count(),
            'groups' => (int) $permissions->pluck('group')->filter()->unique()->count(),
        ];

        return view('permissions.index', compact('permissions', 'stats'));
    }

    public function syncFromRoutes()
    {
        $namedRoutes = collect(Route::getRoutes())
            ->map(function ($route) {
                $name = $route->getName();
                if (!$name) {
                    return null;
                }

                if (Str::startsWith($name, ['ignition.', 'debugbar.'])) {
                    return null;
                }

                $methods = collect($route->methods())
                    ->reject(fn ($method) => in_array($method, ['HEAD', 'OPTIONS'], true))
                    ->values();

                $methodText = $methods->isNotEmpty() ? $methods->implode('|') : null;
                $uri = '/' . ltrim($route->uri(), '/');
                $group = Str::before($name, '.');

                return [
                    'name' => $name,
                    'description' => 'Quyền truy cập route ' . $name,
                    'group' => $group !== $name ? $group : null,
                    'uri' => $uri,
                    'method' => $methodText,
                ];
            })
            ->filter()
            ->unique('name')
            ->values();

        $existing = Permission::query()
            ->whereIn('name', $namedRoutes->pluck('name')->all())
            ->get()
            ->keyBy('name');

        $created = 0;
        $updated = 0;

        foreach ($namedRoutes as $routePermission) {
            /** @var Permission|null $current */
            $current = $existing->get($routePermission['name']);

            if (!$current) {
                Permission::create($routePermission);
                $created++;
                continue;
            }

            $payload = [
                'group' => $routePermission['group'],
                'uri' => $routePermission['uri'],
                'method' => $routePermission['method'],
            ];

            if (empty($current->description)) {
                $payload['description'] = $routePermission['description'];
            }

            $isChanged = false;
            foreach ($payload as $key => $value) {
                if ($current->{$key} !== $value) {
                    $isChanged = true;
                    break;
                }
            }

            if ($isChanged) {
                $current->update($payload);
                $updated++;
            }
        }

        $total = $namedRoutes->count();
        $unchanged = max(0, $total - $created - $updated);

        return redirect()
            ->route('permissions.index')
            ->with('success', "Đã đồng bộ quyền theo Route. Tổng: {$total}, thêm mới: {$created}, cập nhật: {$updated}, giữ nguyên: {$unchanged}.");
    }

    public function create()
    {
        return view('permissions.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions',
            'description' => 'nullable',
        ]);

        Permission::create($request->all());

        return redirect()->route('permissions.index')->with('success', __('permissions.messages.created'));
    }

    public function edit(Permission $permission)
    {
        return view('permissions.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name,' . $permission->id,
            'description' => 'nullable',
            'group' => 'nullable',
        ]);

        $permission->update($request->all());

        return redirect()->route('permissions.index')->with('success', __('permissions.messages.updated'));
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return redirect()->route('permissions.index')->with('success', __('permissions.messages.deleted'));
    }
}
