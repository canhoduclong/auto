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
use Illuminate\Validation\ValidationException;
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
        'all' => 'Tất cả nhân sự',
        'admin' => 'Quản trị viên',
        'CEO' => 'CEO',
        'warehouse' => 'Warehouse',
        'accountant' => 'Account',
        'Shipper' => 'Shipper',
        'leader' => 'Leader',
        'manager' => 'Manager',
        'Director' => 'Director',
        'sale' => 'Sale',
        'package' => 'Đóng hàng',
        'procurement_manager' => 'Thu mua',
    ];

    private const TARGET_ROLE_ALIASES = [
        'all' => ['*'],
        'admin' => ['admin'],
        'CEO' => ['CEO', 'ceo'],
        'warehouse' => ['warehouse'],
        'accountant' => ['account', 'accountant', 'accounting'],
        'Shipper' => ['Shipper', 'shipper', 'manager_shipper'],
        'leader' => ['leader', 'leader_sale', 'sale_manager'],
        'manager' => ['manager', 'manager_sale'],
        'Director' => ['Director', 'director'],
        'sale' => ['sale'],
        'package' => ['package'],
        'procurement_manager' => ['procurement_manager'],
    ];

    private const PRIORITY_OPTIONS = [
        'info' => 'Thông tin',
        'success' => 'Tích cực',
        'warning' => 'Quan trọng',
        'danger' => 'Khẩn cấp',
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
            ->filter(fn ($notification) => !$this->notificationIsScheduled($notification->data ?? []))
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

        $isAdminNotificationCenter = $this->isAdminNotificationCenter($request);
        $departmentRoleOptions = $isAdminNotificationCenter
            ? self::DEPARTMENT_ROLE_OPTIONS
            : array_intersect_key(self::DEPARTMENT_ROLE_OPTIONS, array_flip(['CEO', 'warehouse', 'accountant', 'Shipper', 'leader', 'manager', 'Director', 'sale']));
        $priorityOptions = self::PRIORITY_OPTIONS;
        $allSentBroadcasts = $this->buildSentBroadcasts($request->user(), $isAdminNotificationCenter);
        $broadcastMetrics = $this->broadcastMetrics($allSentBroadcasts);
        $filteredBroadcasts = $this->filterBroadcasts($allSentBroadcasts, $request);
        $broadcastPage = max(1, (int) $request->query('broadcast_page', 1));
        $sentBroadcasts = new LengthAwarePaginator(
            $filteredBroadcasts->forPage($broadcastPage, 15)->values(),
            $filteredBroadcasts->count(),
            15,
            $broadcastPage,
            ['path' => $request->url(), 'pageName' => 'broadcast_page', 'query' => $request->query()]
        );
        $notificationUsers = $isAdminNotificationCenter
            ? User::query()->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        return view('admin.notifications.index', array_merge(
            compact(
                'notifications',
                'departmentRoleOptions',
                'priorityOptions',
                'sentBroadcasts',
                'broadcastMetrics',
                'notificationUsers',
                'isAdminNotificationCenter'
            ),
            $viewContext
        ));
    }

    public function storeDepartmentBroadcast(Request $request): RedirectResponse
    {
        abort_unless($this->canManageDepartmentNotifications($request), 403);

        $validated = $this->validateBroadcast($request);

        $targetRoles = array_values(array_unique($validated['target_roles'] ?? []));
        $targetUserIds = $this->isAdminNotificationCenter($request)
            ? array_values(array_unique(array_map('intval', $validated['target_user_ids'] ?? [])))
            : [];
        $targetRoleNames = $this->expandTargetRoles($targetRoles);
        $expiresAt = $this->normalizeExpiresAt($validated['expires_at'] ?? null);
        $scheduledAt = $this->normalizeScheduledAt($validated['scheduled_at'] ?? null);
        $broadcastId = (string) Str::uuid();
        $users = $this->broadcastRecipients($targetRoleNames, $targetUserIds);

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
            $validated['priority'] ?? 'info',
            $scheduledAt,
            $targetUserIds,
        ));

        $message = $scheduledAt
            ? 'Đã lên lịch thông báo cho ' . $users->count() . ' người nhận.'
            : 'Đã gửi thông báo tới ' . $users->count() . ' người nhận.';

        return back()->with('success', $message);
    }

    public function updateDepartmentBroadcast(Request $request, string $broadcastId): RedirectResponse
    {
        abort_unless($this->canManageDepartmentNotifications($request), 403);

        $validated = $this->validateBroadcast($request);

        $existing = $this->broadcastNotificationsFor($request->user(), $broadcastId)->get();
        abort_if($existing->isEmpty(), 404);

        $targetRoles = array_values(array_unique($validated['target_roles'] ?? []));
        $targetUserIds = $this->isAdminNotificationCenter($request)
            ? array_values(array_unique(array_map('intval', $validated['target_user_ids'] ?? [])))
            : [];
        $targetRoleNames = $this->expandTargetRoles($targetRoles);
        $expiresAt = $this->normalizeExpiresAt($validated['expires_at'] ?? null);
        $scheduledAt = $this->normalizeScheduledAt($validated['scheduled_at'] ?? null);
        $users = $this->broadcastRecipients($targetRoleNames, $targetUserIds);

        if ($users->isEmpty()) {
            return back()->with('error', 'Không tìm thấy user thuộc phòng ban đã chọn.')->withInput();
        }

        $originalSenderId = (int) ($existing->first()?->data['sender_id'] ?? $request->user()->id);
        $updatedNotification = new DepartmentBroadcastNotification(
            trim($validated['title']),
            trim($validated['message']),
            $targetRoles,
            $targetRoleNames,
            $expiresAt,
            $broadcastId,
            !empty($validated['url']) ? trim($validated['url']) : null,
            $originalSenderId,
            $validated['priority'] ?? 'info',
            $scheduledAt,
            $targetUserIds,
            $request->user()->id,
        );

        $recipientsById = $users->keyBy('id');
        $existingByRecipient = $existing->groupBy(fn (DatabaseNotification $item) => (int) $item->notifiable_id);

        foreach ($recipientsById as $recipientId => $recipient) {
            $recipientNotifications = $existingByRecipient->get((int) $recipientId, collect());
            $currentNotification = $recipientNotifications->shift();

            if ($currentNotification) {
                $currentNotification->forceFill(['data' => $updatedNotification->toArray($recipient)])->save();
                $recipientNotifications->each->delete();
            } else {
                $recipient->notify($updatedNotification);
            }
        }

        $existing
            ->reject(fn (DatabaseNotification $item) => $recipientsById->has((int) $item->notifiable_id))
            ->each->delete();

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

    public function destroyNotification(Request $request, string $notificationId): RedirectResponse
    {
        abort_unless($this->canManageDepartmentNotifications($request), 403);

        $notification = $request->user()
            ->notifications()
            ->where('type', DepartmentBroadcastNotification::class)
            ->where('id', $notificationId)
            ->firstOrFail();

        $viewContext = $this->resolveNotificationViewContext($request);
        abort_unless(
            $this->notificationMatchesLayout($notification->data ?? [], $viewContext['notificationLayoutKey'] ?? null),
            403
        );

        $notification->delete();

        return back()->with('success', 'Đã xóa thông báo khỏi hộp thư.');
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
        abort_unless(!$this->notificationIsScheduled($notification->data ?? []), 404);

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
        abort_unless(!$this->notificationIsScheduled($notification->data ?? []), 404);

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
            ->filter(fn ($notification) => !$this->notificationIsScheduled($notification->data ?? []))
            ->each->markAsRead();

        return back()->with('success', 'Da danh dau tat ca thong bao la da doc.');
    }

    private function canManageDepartmentNotifications(Request $request): bool
    {
        return (bool) $request->user()?->hasRole(self::NOTIFICATION_MANAGER_ROLES);
    }

    private function isAdminNotificationCenter(Request $request): bool
    {
        return $request->routeIs('admin.notifications.*') && (bool) $request->user()?->isAdmin();
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
            'notificationDeleteRouteName' => $layoutKey === 'admin' ? 'admin.notifications.notification.destroy' : 'department-notifications.notification.destroy',
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

        if (in_array((int) auth()->id(), array_map('intval', (array) ($data['target_user_ids'] ?? [])), true)) {
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

        if ($targetRoles->contains('*')) {
            return true;
        }

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

    private function notificationIsScheduled(array $data): bool
    {
        $scheduledAt = $data['scheduled_at'] ?? null;
        if (blank($scheduledAt)) {
            return false;
        }

        try {
            return Carbon::parse($scheduledAt)->isFuture();
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

    private function normalizeScheduledAt(?string $scheduledAt): ?string
    {
        if (blank($scheduledAt)) {
            return null;
        }

        return Carbon::parse($scheduledAt)->toDateTimeString();
    }

    private function broadcastNotificationsFor(User $sender, string $broadcastId)
    {
        $query = DatabaseNotification::query()
            ->where('type', DepartmentBroadcastNotification::class)
            ->where('data->broadcast_id', $broadcastId);

        if (!(request()->routeIs('admin.notifications.*') && $sender->isAdmin())) {
            $query->where('data->sender_id', $sender->id);
        }

        return $query;
    }

    private function buildSentBroadcasts(User $sender, bool $includeAll = false): Collection
    {
        $query = DatabaseNotification::query()
            ->where('type', DepartmentBroadcastNotification::class)
            ->latest();

        if (!$includeAll) {
            $query->where('data->sender_id', $sender->id);
        }

        $notifications = $query->get();
        $senderIds = $notifications->pluck('data.sender_id')->filter()->map(fn ($id) => (int) $id)->unique();
        $senders = User::query()->whereIn('id', $senderIds)->get(['id', 'name', 'email'])->keyBy('id');
        $recipientIds = $notifications->pluck('notifiable_id')->map(fn ($id) => (int) $id)->unique();
        $recipients = User::query()->whereIn('id', $recipientIds)->get(['id', 'name', 'email'])->keyBy('id');

        return $notifications
            ->groupBy(fn (DatabaseNotification $notification) => $notification->data['broadcast_id'] ?? $notification->id)
            ->map(function (Collection $group, string $broadcastId) use ($senders, $recipients) {
                $first = $group->sortByDesc('created_at')->first();
                $data = $first->data ?? [];
                $sender = $senders->get((int) ($data['sender_id'] ?? 0));

                return [
                    'broadcast_id' => $broadcastId,
                    'title' => $data['title'] ?? 'Thông báo',
                    'message' => $data['message'] ?? '',
                    'url' => $data['url'] ?? '',
                    'target_roles' => (array) ($data['target_roles'] ?? []),
                    'target_role_names' => (array) ($data['target_role_names'] ?? []),
                    'target_user_ids' => (array) ($data['target_user_ids'] ?? []),
                    'expires_at' => $data['expires_at'] ?? null,
                    'scheduled_at' => $data['scheduled_at'] ?? null,
                    'priority' => $data['priority'] ?? 'info',
                    'sender_id' => $data['sender_id'] ?? null,
                    'sender_name' => $sender?->name ?? 'Hệ thống',
                    'sender_email' => $sender?->email,
                    'created_at' => $first->created_at,
                    'updated_at' => $first->updated_at,
                    'recipient_count' => $group->count(),
                    'read_count' => $group->whereNotNull('read_at')->count(),
                    'is_expired' => $this->notificationIsExpired($data),
                    'is_scheduled' => $this->notificationIsScheduled($data),
                    'can_edit' => !empty($data['broadcast_id']),
                    'recipients' => $group->map(function (DatabaseNotification $notification) use ($recipients) {
                        $recipient = $recipients->get((int) $notification->notifiable_id);

                        return [
                            'id' => (int) $notification->notifiable_id,
                            'name' => $recipient?->name ?? 'Tài khoản #' . $notification->notifiable_id,
                            'email' => $recipient?->email,
                            'read_at' => $notification->read_at,
                        ];
                    })->sortBy('name')->values(),
                ];
            })
            ->sortByDesc('created_at')
            ->values();
    }

    private function validateBroadcast(Request $request): array
    {
        $isAdminCenter = $this->isAdminNotificationCenter($request);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:2000'],
            'target_roles' => ['nullable', 'array'],
            'target_roles.*' => ['required', Rule::in(array_keys(self::DEPARTMENT_ROLE_OPTIONS))],
            'target_user_ids' => [$isAdminCenter ? 'nullable' : 'prohibited', 'array'],
            'target_user_ids.*' => ['integer', 'exists:users,id'],
            'url' => ['nullable', 'string', 'max:500'],
            'priority' => ['nullable', Rule::in(array_keys(self::PRIORITY_OPTIONS))],
            'scheduled_at' => [$isAdminCenter ? 'nullable' : 'prohibited', 'date'],
            'expires_at' => ['nullable', 'date'],
        ]);

        if (empty($validated['target_roles']) && empty($validated['target_user_ids'])) {
            throw ValidationException::withMessages([
                'target_roles' => 'Hãy chọn ít nhất một phòng ban hoặc một nhân sự nhận thông báo.',
            ]);
        }

        if (!$isAdminCenter && array_intersect((array) ($validated['target_roles'] ?? []), ['all', 'admin', 'package', 'procurement_manager'])) {
            throw ValidationException::withMessages([
                'target_roles' => 'Bạn không có quyền gửi tới nhóm người nhận này.',
            ]);
        }

        if (!empty($validated['scheduled_at']) && !empty($validated['expires_at'])
            && Carbon::parse($validated['expires_at'])->lte(Carbon::parse($validated['scheduled_at']))) {
            throw ValidationException::withMessages([
                'expires_at' => 'Thời hạn phải sau thời điểm bắt đầu hiển thị.',
            ]);
        }

        return $validated;
    }

    private function broadcastRecipients(array $targetRoleNames, array $targetUserIds): Collection
    {
        $query = User::query();

        if (in_array('*', $targetRoleNames, true)) {
            return $query->get();
        }

        $query->where(function ($recipientQuery) use ($targetRoleNames, $targetUserIds) {
            if ($targetRoleNames !== []) {
                $recipientQuery->whereHas('roles', fn ($roleQuery) => $roleQuery->whereIn('name', $targetRoleNames));
            }
            if ($targetUserIds !== []) {
                $method = $targetRoleNames === [] ? 'whereIn' : 'orWhereIn';
                $recipientQuery->{$method}('id', $targetUserIds);
            }
        });

        return $query->get()->unique('id')->values();
    }

    private function broadcastMetrics(Collection $broadcasts): array
    {
        $recipientCount = (int) $broadcasts->sum('recipient_count');
        $readCount = (int) $broadcasts->sum('read_count');

        return [
            'total' => $broadcasts->count(),
            'active' => $broadcasts->where('is_expired', false)->where('is_scheduled', false)->count(),
            'scheduled' => $broadcasts->where('is_scheduled', true)->count(),
            'expired' => $broadcasts->where('is_expired', true)->count(),
            'recipient_count' => $recipientCount,
            'read_count' => $readCount,
            'read_rate' => $recipientCount > 0 ? (int) round(($readCount / $recipientCount) * 100) : 0,
        ];
    }

    private function filterBroadcasts(Collection $broadcasts, Request $request): Collection
    {
        $search = Str::lower(trim((string) $request->query('broadcast_search', '')));
        $status = (string) $request->query('broadcast_status', '');
        $priority = (string) $request->query('broadcast_priority', '');
        $role = (string) $request->query('broadcast_role', '');

        return $broadcasts
            ->when($search !== '', fn (Collection $items) => $items->filter(function (array $item) use ($search) {
                return Str::contains(Str::lower(implode(' ', [
                    $item['title'], $item['message'], $item['sender_name'], $item['sender_email'] ?? '',
                ])), $search);
            }))
            ->when($status !== '', fn (Collection $items) => $items->filter(fn (array $item) => match ($status) {
                'active' => !$item['is_expired'] && !$item['is_scheduled'],
                'scheduled' => $item['is_scheduled'],
                'expired' => $item['is_expired'],
                default => true,
            }))
            ->when($priority !== '', fn (Collection $items) => $items->where('priority', $priority))
            ->when($role !== '', fn (Collection $items) => $items->filter(fn (array $item) => in_array($role, $item['target_roles'], true)))
            ->values();
    }
}
