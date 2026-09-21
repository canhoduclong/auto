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
        $groupedPermissions = $permissions->groupBy(fn (Permission $permission) => $permission->group ?: 'other');
        $groupOptions = $groupedPermissions->keys()->sort()->values();

        return view('permissions.index', compact('permissions', 'stats', 'groupedPermissions', 'groupOptions'));
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
                $group = $this->permissionGroupForRoute($name, $uri);

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
        $groupOptions = Permission::query()->whereNotNull('group')->distinct()->orderBy('group')->pluck('group');

        return view('permissions.create', compact('groupOptions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions',
            'description' => 'nullable',
            'group' => 'required|string|max:100',
        ]);

        Permission::create($request->all());

        return redirect()->route('permissions.index')->with('success', __('permissions.messages.created'));
    }

    public function edit(Permission $permission)
    {
        $groupOptions = Permission::query()->whereNotNull('group')->distinct()->orderBy('group')->pluck('group');

        return view('permissions.edit', compact('permission', 'groupOptions'));
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name,' . $permission->id,
            'description' => 'nullable',
            'group' => 'required|string|max:100',
        ]);

        $permission->update($request->all());

        return redirect()->route('permissions.index')->with('success', __('permissions.messages.updated'));
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return redirect()->route('permissions.index')->with('success', __('permissions.messages.deleted'));
    }

    private function permissionGroupForRoute(string $name, string $uri): string
    {
        $segments = explode('.', $name);
        $root = $segments[0] ?? 'other';
        $second = $segments[1] ?? null;

        if (in_array($root, ['admin', 'site', 'pages'], true) && $second) {
            $root = $second;
        }

        return match (true) {
            Str::contains($root, ['notification']) => 'notifications',
            Str::contains($root, ['permission', 'role']) => 'access-control',
            Str::contains($root, ['user', 'profile', 'password']) => 'users',
            Str::contains($root, ['order', 'cart', 'checkout']) => 'orders',
            Str::contains($root, ['customer']) => 'customers',
            Str::contains($root, ['product', 'variant', 'categor']) => 'products',
            Str::contains($root, ['warehouse', 'inventory', 'stock', 'dispatch']) => 'warehouse',
            Str::contains($root, ['shipper', 'delivery']) => 'shipping',
            Str::contains($root, ['account', 'cashflow', 'transaction', 'debt', 'commission']) => 'accounting',
            Str::contains($root, ['report', 'statistic', 'dashboard', 'revenue']) => 'reports',
            Str::contains($root, ['media', 'upload', 'file']) => 'media',
            default => Str::slug($root ?: trim(explode('/', trim($uri, '/'))[0] ?? 'other')) ?: 'other',
        };
    }
}
