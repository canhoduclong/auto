<?php 
namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class PermissionController extends Controller
{
    private const GROUP_CATALOG = [
        'access-control' => ['Phân quyền & vai trò', 'Quản lý vai trò, quyền truy cập và phạm vi sử dụng.', 10],
        'users' => ['Người dùng', 'Quản lý tài khoản, hồ sơ và thông tin đăng nhập.', 20],
        'customers' => ['Khách hàng', 'Quản lý hồ sơ, công nợ và dữ liệu khách hàng.', 30],
        'products' => ['Sản phẩm & giá', 'Quản lý sản phẩm, biến thể, danh mục và giá bán.', 40],
        'orders' => ['Đơn hàng', 'Tạo, xem và xử lý vòng đời đơn hàng.', 50],
        'warehouse' => ['Kho & tồn kho', 'Đóng hàng, nhập xuất, điều chuyển và kiểm soát tồn kho.', 60],
        'shipping' => ['Giao hàng', 'Phân công shipper, giao nhận và chi phí vận chuyển.', 70],
        'accounting' => ['Kế toán & tài chính', 'Đối soát, thu chi, công nợ và nghiệp vụ kế toán.', 80],
        'notifications' => ['Thông báo', 'Tạo, sửa, gửi và quản trị thông báo hệ thống.', 90],
        'reports' => ['Báo cáo & thống kê', 'Xem báo cáo điều hành, doanh thu và thống kê.', 100],
        'media' => ['Tệp & hình ảnh', 'Quản lý tệp tải lên và thư viện hình ảnh.', 110],
        'other' => ['Khác', 'Các quyền chưa thuộc phân hệ chuyên biệt.', 999],
    ];

    private const ACTION_CATALOG = [
        'index' => ['Xem danh sách', 10], 'show' => ['Xem chi tiết', 20],
        'create' => ['Mở form thêm', 30], 'store' => ['Thêm mới', 40],
        'edit' => ['Mở form sửa', 50], 'update' => ['Cập nhật', 60],
        'confirm' => ['Xác nhận', 70], 'approve' => ['Duyệt', 71],
        'reject' => ['Từ chối', 72], 'cancel' => ['Hủy', 73],
        'destroy' => ['Xóa', 90], 'delete' => ['Xóa', 90],
        'export' => ['Xuất dữ liệu', 100], 'import' => ['Nhập dữ liệu', 101],
        'print' => ['In', 102], 'sync-routes' => ['Đồng bộ route', 110],
    ];

    public function index()
    {
        $permissions = Permission::query()
            ->get()
            ->sortBy(fn (Permission $permission) => sprintf(
                '%04d-%04d-%s',
                $this->groupMeta($permission->group)['order'],
                $this->actionMeta($permission->name)['order'],
                $permission->name
            ))
            ->values();

        $stats = [
            'total' => (int) $permissions->count(),
            'with_route_meta' => (int) $permissions->filter(fn (Permission $p) => !empty($p->uri) || !empty($p->method))->count(),
            'groups' => (int) $permissions->pluck('group')->filter()->unique()->count(),
        ];
        $groupedPermissions = $permissions->groupBy(fn (Permission $permission) => $permission->group ?: 'other');
        $groupOptions = $groupedPermissions->keys()->sort()->values();
        $groupCatalog = collect($groupedPermissions->keys())->mapWithKeys(fn ($group) => [$group => $this->groupMeta($group)]);
        $permissionCatalog = $permissions->mapWithKeys(fn (Permission $permission) => [$permission->id => $this->actionMeta($permission->name)]);

        return view('permissions.index', compact('permissions', 'stats', 'groupedPermissions', 'groupOptions', 'groupCatalog', 'permissionCatalog'));
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

    private function groupMeta(?string $group): array
    {
        $key = $group ?: 'other';
        [$label, $description, $order] = self::GROUP_CATALOG[$key]
            ?? [Str::headline($key), 'Nhóm quyền '.$key.'.', 500];

        return compact('label', 'description', 'order');
    }

    private function actionMeta(string $permissionName): array
    {
        $segments = explode('.', $permissionName);
        $action = (string) end($segments);
        [$label, $order] = self::ACTION_CATALOG[$action] ?? [Str::headline($action), 80];
        $resource = count($segments) > 1 ? $segments[count($segments) - 2] : $segments[0];

        return [
            'key' => $action,
            'label' => $label,
            'order' => $order,
            'explanation' => $label.' đối với chức năng '.Str::lower(Str::headline($resource)).'.',
        ];
    }
}
