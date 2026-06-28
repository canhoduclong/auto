<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\DepartmentBroadcastNotification;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
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

    private const TARGET_ROLE_ALIASES = [
        'CEO' => ['CEO', 'ceo'],
        'warehouse' => ['warehouse'],
        'accountant' => ['account', 'accountant', 'accounting'],
        'Shipper' => ['Shipper', 'shipper', 'manager_shipper'],
        'leader' => ['leader', 'leader_sale', 'sale_manager'],
        'manager' => ['manager', 'manager_sale'],
        'Director' => ['Director', 'director'],
        'sale' => ['sale'],
    ];

    private const LAYOUT_ROLE_SCOPE = [
        'ceo' => ['CEO', 'ceo'],
        'director' => ['Director', 'director'],
        'accounting' => ['account', 'accountant', 'accounting'],
        'warehouse' => ['warehouse'],
        'shipper' => ['Shipper', 'shipper', 'manager_shipper'],
        'site' => ['sale', 'leader', 'leader_sale', 'sale_manager', 'manager', 'manager_sale'],
    ];

    public function index(Request $request): View
    {
        abort_unless($this->canManageDepartmentNotifications($request), 403);

        $viewContext = $this->resolveNotificationViewContext($request);
        $layoutKey = $viewContext['notificationLayoutKey'] ?? null;

        $filteredNotifications = $request->user()
            ->notifications()
            ->where('type', DepartmentBroadcastNotification::class)
            ->latest()
            ->get()
            ->filter(fn ($notification) => $this->notificationMatchesLayout($notification->data ?? [], $layoutKey))
            ->filter(fn ($notification) => !$this->notificationIsExpired($notification->data ?? []))
            ->values();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 20;
        $notifications = new LengthAwarePaginator(
            $filteredNotifications->forPage($page, $perPage)->values(),
            $filteredNotifications->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $departmentRoleOptions = self::DEPARTMENT_ROLE_OPTIONS;
        $sentBroadcasts = $this->buildSentBroadcasts($request->user());

        return view('admin.notifications.index', array_merge(
            compact('notifications', 'departmentRoleOptions', 'sentBroadcasts'),
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
            'expires_at' => ['nullable', 'date'],
        ]);

        $targetRoles = array_values(array_unique($validated['target_roles']));
        $targetRoleNames = $this->expandTargetRoles($targetRoles);
        $expiresAt = $this->normalizeExpiresAt($validated['expires_at'] ?? null);
        $broadcastId = (string) Str::uuid();
        $users = User::query()
            ->whereHas('roles', function ($query) use ($targetRoleNames) {
                $query->whereIn('name', $targetRoleNames);
            })
            ->get();

        if ($users->isEmpty()) {
            return back()->with('error', 'Không tìm thấy user thuộc phòng ban đã chọn.')->withInput();
        }

        Notification::send($users, new DepartmentBroadcastNotification(
            trim($validated['title']),
            trim($validated['message']),
            $targetRoles,
            $targetRoleNames,
            $expiresAt,
            $broadcastId,
            !empty($validated['url']) ? trim($validated['url']) : null,
            $request->user()->id,
        ));

        return back()->with('success', 'Đã gửi thông báo tới ' . $users->count() . ' người nhận.');
    }

    public function updateDepartmentBroadcast(Request $request, string $broadcastId): RedirectResponse
    {
        abort_unless($this->canManageDepartmentNotifications($request), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:2000'],
            'target_roles' => ['required', 'array', 'min:1'],
            'target_roles.*' => ['required', Rule::in(array_keys(self::DEPARTMENT_ROLE_OPTIONS))],
            'url' => ['nullable', 'string', 'max:500'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $existing = $this->broadcastNotificationsFor($request->user(), $broadcastId)->get();
        abort_if($existing->isEmpty(), 404);

        $targetRoles = array_values(array_unique($validated['target_roles']));
        $targetRoleNames = $this->expandTargetRoles($targetRoles);
        $expiresAt = $this->normalizeExpiresAt($validated['expires_at'] ?? null);
        $users = User::query()
            ->whereHas('roles', function ($query) use ($targetRoleNames) {
                $query->whereIn('name', $targetRoleNames);
            })
            ->get();

        if ($users->isEmpty()) {
            return back()->with('error', 'Không tìm thấy user thuộc phòng ban đã chọn.')->withInput();
        }

        $existing->each->delete();

        Notification::send($users, new DepartmentBroadcastNotification(
            trim($validated['title']),
            trim($validated['message']),
            $targetRoles,
            $targetRoleNames,
            $expiresAt,
            $broadcastId,
            !empty($validated['url']) ? trim($validated['url']) : null,
            $request->user()->id,
        ));

        return back()->with('success', 'Đã cập nhật thông báo và gửi tới ' . $users->count() . ' người nhận.');
    }

    public function destroyDepartmentBroadcast(Request $request, string $broadcastId): RedirectResponse
    {
        abort_unless($this->canManageDepartmentNotifications($request), 403);

        $notifications = $this->broadcastNotificationsFor($request->user(), $broadcastId)->get();
        abort_if($notifications->isEmpty(), 404);

        $count = $notifications->count();
        $notifications->each->delete();

        return back()->with('success', 'Đã xóa thông báo khỏi ' . $count . ' người nhận.');
    }

    public function markAsRead(Request $request, string $notificationId): RedirectResponse
    {
        abort_unless($this->canManageDepartmentNotifications($request), 403);

        $notification = $request->user()
            ->notifications()
            ->where('id', $notificationId)
            ->firstOrFail();

        $viewContext = $this->resolveNotificationViewContext($request);
        abort_unless($this->notificationMatchesLayout($notification->data ?? [], $viewContext['notificationLayoutKey'] ?? null), 403);
        abort_unless(!$this->notificationIsExpired($notification->data ?? []), 404);

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

        $viewContext = $this->resolveNotificationViewContext($request);
        abort_unless($this->notificationMatchesLayout($notification->data ?? [], $viewContext['notificationLayoutKey'] ?? null), 403);
        abort_unless(!$this->notificationIsExpired($notification->data ?? []), 404);

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return view('admin.notifications.show', array_merge(
            compact('notification'),
            $viewContext
        ));
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        abort_unless($this->canManageDepartmentNotifications($request), 403);

        $viewContext = $this->resolveNotificationViewContext($request);
        $layoutKey = $viewContext['notificationLayoutKey'] ?? null;

        $request->user()
            ->unreadNotifications()
            ->where('type', DepartmentBroadcastNotification::class)
            ->get()
            ->filter(fn ($notification) => $this->notificationMatchesLayout($notification->data ?? [], $layoutKey))
            ->filter(fn ($notification) => !$this->notificationIsExpired($notification->data ?? []))
            ->each->markAsRead();

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
            'notificationUpdateRouteName' => $layoutKey === 'admin' ? 'admin.notifications.update' : 'department-notifications.update',
            'notificationDestroyRouteName' => $layoutKey === 'admin' ? 'admin.notifications.destroy' : 'department-notifications.destroy',
        ];
    }

    private function expandTargetRoles(array $targetRoles): array
    {
        return collect($targetRoles)
            ->flatMap(fn (string $role) => self::TARGET_ROLE_ALIASES[$role] ?? [$role])
            ->map(fn ($role) => (string) $role)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function notificationMatchesLayout(array $data, ?string $layoutKey): bool
    {
        if ($layoutKey === 'admin') {
            return true;
        }

        $layoutRoles = self::LAYOUT_ROLE_SCOPE[strtolower((string) $layoutKey)] ?? [];
        if ($layoutRoles === []) {
            return true;
        }

        $targetRoles = $data['target_role_names'] ?? [];
        if (empty($targetRoles)) {
            $targetRoles = $this->expandTargetRoles((array) ($data['target_roles'] ?? []));
        }
        $targetRoles = collect($targetRoles)
            ->map(fn ($role) => strtolower((string) $role))
            ->filter()
            ->values();

        if ($targetRoles->isEmpty()) {
            return true;
        }

        $layoutRoles = collect($layoutRoles)->map(fn ($role) => strtolower((string) $role))->all();

        return $targetRoles->intersect($layoutRoles)->isNotEmpty();
    }

    private function notificationIsExpired(array $data): bool
    {
        $expiresAt = $data['expires_at'] ?? null;
        if (blank($expiresAt)) {
            return false;
        }

        try {
            return Carbon::parse($expiresAt)->isPast();
        } catch (\Throwable) {
            return false;
        }
    }

    private function normalizeExpiresAt(?string $expiresAt): ?string
    {
        if (blank($expiresAt)) {
            return null;
        }

        return Carbon::parse($expiresAt)->toDateTimeString();
    }

    private function broadcastNotificationsFor(User $sender, string $broadcastId)
    {
        return DatabaseNotification::query()
            ->where('type', DepartmentBroadcastNotification::class)
            ->where('data->broadcast_id', $broadcastId)
            ->where('data->sender_id', $sender->id);
    }

    private function buildSentBroadcasts(User $sender): Collection
    {
        return DatabaseNotification::query()
            ->where('type', DepartmentBroadcastNotification::class)
            ->where('data->sender_id', $sender->id)
            ->latest()
            ->limit(300)
            ->get()
            ->groupBy(fn (DatabaseNotification $notification) => $notification->data['broadcast_id'] ?? $notification->id)
            ->map(function (Collection $group, string $broadcastId) {
                $first = $group->sortByDesc('created_at')->first();
                $data = $first->data ?? [];

                return [
                    'broadcast_id' => $broadcastId,
                    'title' => $data['title'] ?? 'Thông báo',
                    'message' => $data['message'] ?? '',
                    'url' => $data['url'] ?? '',
                    'target_roles' => (array) ($data['target_roles'] ?? []),
                    'target_role_names' => (array) ($data['target_role_names'] ?? []),
                    'expires_at' => $data['expires_at'] ?? null,
                    'created_at' => $first->created_at,
                    'updated_at' => $first->updated_at,
                    'recipient_count' => $group->count(),
                    'read_count' => $group->whereNotNull('read_at')->count(),
                    'is_expired' => $this->notificationIsExpired($data),
                    'can_edit' => !empty($data['broadcast_id']),
                ];
            })
            ->sortByDesc('created_at')
            ->values();
    }
}
