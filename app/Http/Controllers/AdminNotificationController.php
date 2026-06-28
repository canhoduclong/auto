<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\DepartmentBroadcastNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminNotificationController extends Controller
{
    private const NOTIFICATION_MANAGER_ROLES = [
        'admin',
        'CEO',
        'ceo',
        'Director',
        'director',
        'leader',
        'leader_sale',
        'sale_manager',
        'manager',
        'manager_sale',
        'warehouse',
        'account',
        'accountant',
        'accounting',
        'Shipper',
        'shipper',
        'manager_shipper',
    ];

    private const DEPARTMENT_ROLE_OPTIONS = [
        'CEO' => 'CEO',
        'warehouse' => 'Warehouse',
        'accountant' => 'Account',
        'Shipper' => 'Shipper',
        'leader' => 'Leader',
        'manager' => 'Manager',
        'Director' => 'Director',
        'sale' => 'Sale',
    ];

    public function index(Request $request): View
    {
        abort_unless($this->canManageDepartmentNotifications($request), 403);

        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        $departmentRoleOptions = self::DEPARTMENT_ROLE_OPTIONS;
        $viewContext = $this->resolveNotificationViewContext($request);

        return view('admin.notifications.index', array_merge(
            compact('notifications', 'departmentRoleOptions'),
            $viewContext
        ));
    }

    public function storeDepartmentBroadcast(Request $request): RedirectResponse
    {
        abort_unless($this->canManageDepartmentNotifications($request), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:2000'],
            'target_roles' => ['required', 'array', 'min:1'],
            'target_roles.*' => ['required', Rule::in(array_keys(self::DEPARTMENT_ROLE_OPTIONS))],
            'url' => ['nullable', 'string', 'max:500'],
        ]);

        $targetRoles = array_values(array_unique($validated['target_roles']));
        $users = User::query()
            ->whereHas('roles', function ($query) use ($targetRoles) {
                $query->whereIn('name', $targetRoles);
            })
            ->get();

        if ($users->isEmpty()) {
            return back()->with('error', 'Không tìm thấy user thuộc phòng ban đã chọn.')->withInput();
        }

        Notification::send($users, new DepartmentBroadcastNotification(
            trim($validated['title']),
            trim($validated['message']),
            $targetRoles,
            !empty($validated['url']) ? trim($validated['url']) : null,
            $request->user()->id,
        ));

        return back()->with('success', 'Đã gửi thông báo tới ' . $users->count() . ' người nhận.');
    }

    public function markAsRead(Request $request, string $notificationId): RedirectResponse
    {
        abort_unless($this->canManageDepartmentNotifications($request), 403);

        $notification = $request->user()
            ->notifications()
            ->where('id', $notificationId)
            ->firstOrFail();

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        $targetUrl = $notification->data['url'] ?? route('dashboard');

        return redirect($targetUrl);
    }

    public function show(Request $request, string $notificationId): View
    {
        abort_unless($this->canManageDepartmentNotifications($request), 403);

        $notification = $request->user()
            ->notifications()
            ->where('id', $notificationId)
            ->firstOrFail();

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        $viewContext = $this->resolveNotificationViewContext($request);

        return view('admin.notifications.show', array_merge(
            compact('notification'),
            $viewContext
        ));
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        abort_unless($this->canManageDepartmentNotifications($request), 403);

        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Da danh dau tat ca thong bao la da doc.');
    }

    private function canManageDepartmentNotifications(Request $request): bool
    {
        return (bool) $request->user()?->hasRole(self::NOTIFICATION_MANAGER_ROLES);
    }

    private function resolveNotificationViewContext(Request $request): array
    {
        $layout = (string) $request->query('layout', '');

        $map = [
            'ceo' => ['layouts.ceo', 'content', 'ceo'],
            'director' => ['layouts.director', 'content', 'director'],
            'accounting' => ['layouts.accounting', 'accounting_content', 'accounting'],
            'warehouse' => ['layouts.warehouse', 'content', 'warehouse'],
            'shipper' => ['layouts.shipper', 'content', 'shipper'],
            'site' => ['layouts.site', 'content', 'site'],
            'admin' => ['layouts.admin', 'content', 'admin'],
        ];

        if (!isset($map[$layout])) {
            $user = $request->user();
            $layout = match (true) {
                $user?->hasRole(['CEO', 'ceo']) => 'ceo',
                $user?->hasRole(['Director', 'director']) => 'director',
                $user?->hasRole(['account', 'accountant', 'accounting']) => 'accounting',
                $user?->hasRole('warehouse') => 'warehouse',
                $user?->hasRole(['Shipper', 'shipper', 'manager_shipper']) => 'shipper',
                $user?->hasRole('admin') => 'admin',
                default => 'site',
            };
        }

        [$viewLayout, $section, $layoutKey] = $map[$layout];

        return [
            'notificationLayout' => $viewLayout,
            'notificationSection' => $section,
            'notificationLayoutKey' => $layoutKey,
            'notificationIndexRouteName' => $layoutKey === 'admin' ? 'admin.notifications.index' : 'department-notifications.index',
            'notificationBroadcastRouteName' => $layoutKey === 'admin' ? 'admin.notifications.department_broadcast' : 'department-notifications.department_broadcast',
            'notificationReadAllRouteName' => $layoutKey === 'admin' ? 'admin.notifications.read_all' : 'department-notifications.read_all',
            'notificationReadRouteName' => $layoutKey === 'admin' ? 'admin.notifications.read' : 'department-notifications.read',
            'notificationShowRouteName' => $layoutKey === 'admin' ? 'admin.notifications.show' : 'department-notifications.show',
        ];
    }
}
