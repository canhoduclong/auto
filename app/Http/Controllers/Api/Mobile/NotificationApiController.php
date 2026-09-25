<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\Concerns\ResolvesMobileRole;
use App\Models\User;
use App\Notifications\DepartmentBroadcastNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class NotificationApiController extends BaseApiController
{
    use ResolvesMobileRole;

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

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->fail('Unauthorized', 401);
        }

        $limit = min(50, max(1, (int) $request->query('limit', 20)));
        $layoutKey = $this->currentLayoutKey($user);
        $notifications = $user->notifications()
            ->latest()
            ->limit(max($limit * 6, 60))
            ->get()
            ->filter(fn ($n) => !$this->isExpired($n->data ?? []))
            ->filter(fn ($n) => !$this->isScheduled($n->data ?? []))
            ->filter(fn ($n) => $this->matchesCurrentLayout($n->data ?? [], $layoutKey))
            ->take($limit)
            ->values();

        $items = $notifications
            ->map(fn ($n) => [
                'id' => (string) $n->id,
                'title' => (string) ($n->data['title'] ?? 'Thong bao'),
                'message' => (string) ($n->data['message'] ?? ''),
                'priority' => (string) ($n->data['priority'] ?? 'info'),
                'type' => (string) ($n->data['type'] ?? ''),
                'url' => (string) ($n->data['url'] ?? ''),
                'target_roles' => (array) ($n->data['target_roles'] ?? []),
                'target_role_names' => (array) ($n->data['target_role_names'] ?? []),
                'sender_id' => isset($n->data['sender_id']) ? (int) $n->data['sender_id'] : null,
                'broadcast_id' => (string) ($n->data['broadcast_id'] ?? ''),
                'expires_at' => (string) ($n->data['expires_at'] ?? ''),
                'scheduled_at' => (string) ($n->data['scheduled_at'] ?? ''),
                'route_key' => (string) ($n->data['route_key'] ?? ''),
                'order_id' => isset($n->data['order_id']) ? (int) $n->data['order_id'] : null,
                'daily_sequence' => isset($n->data['daily_sequence']) ? (int) $n->data['daily_sequence'] : null,
                'customer_name' => (string) ($n->data['customer_name'] ?? ''),
                'sale_name' => (string) ($n->data['sale_name'] ?? ''),
                'order_created_at' => (string) ($n->data['order_created_at'] ?? ''),
                'products' => is_array($n->data['products'] ?? null) ? $n->data['products'] : [],
                'total' => (float) ($n->data['total'] ?? 0),
                'note' => (string) ($n->data['note'] ?? ''),
                'read_at' => optional($n->read_at)->toIso8601String(),
                'created_at' => optional($n->created_at)->toIso8601String(),
            ])
            ->values();

        return $this->ok([
            'layout' => $layoutKey,
            'role_options' => self::DEPARTMENT_ROLE_OPTIONS,
            'unread_count' => $notifications->whereNull('read_at')->count(),
            'items' => $items,
            'sent_broadcasts' => $this->sentBroadcasts($user),
        ]);
    }

    public function storeDepartmentBroadcast(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->fail('Unauthorized', 401);
        }

        $data = $this->validateBroadcast($request);
        $targetRoles = array_values(array_unique($data['target_roles']));
        $targetRoleNames = departmentBroadcastExpandTargetRoles($targetRoles);
        $users = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', $targetRoleNames))
            ->get();

        if ($users->isEmpty()) {
            return $this->fail('Không tìm thấy user thuộc phòng ban đã chọn.', 422);
        }

        $broadcastId = (string) Str::uuid();
        Notification::send($users, new DepartmentBroadcastNotification(
            trim($data['title']),
            trim($data['message']),
            $targetRoles,
            $targetRoleNames,
            $this->normalizeExpiresAt($data['expires_at'] ?? null),
            $broadcastId,
            !empty($data['url']) ? trim($data['url']) : null,
            $user->id,
        ));

        return $this->ok(['broadcast_id' => $broadcastId], 'Đã gửi thông báo tới ' . $users->count() . ' người nhận.');
    }

    public function updateDepartmentBroadcast(Request $request, string $broadcastId): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->fail('Unauthorized', 401);
        }

        $existing = $this->broadcastNotificationsFor($user, $broadcastId)->get();
        if ($existing->isEmpty()) {
            return $this->fail('Không tìm thấy thông báo.', 404);
        }

        $data = $this->validateBroadcast($request);
        $targetRoles = array_values(array_unique($data['target_roles']));
        $targetRoleNames = departmentBroadcastExpandTargetRoles($targetRoles);
        $users = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', $targetRoleNames))
            ->get();

        if ($users->isEmpty()) {
            return $this->fail('Không tìm thấy user thuộc phòng ban đã chọn.', 422);
        }

        $existing->each->delete();
        Notification::send($users, new DepartmentBroadcastNotification(
            trim($data['title']),
            trim($data['message']),
            $targetRoles,
            $targetRoleNames,
            $this->normalizeExpiresAt($data['expires_at'] ?? null),
            $broadcastId,
            !empty($data['url']) ? trim($data['url']) : null,
            $user->id,
        ));

        return $this->ok(null, 'Đã cập nhật thông báo.');
    }

    public function destroyDepartmentBroadcast(Request $request, string $broadcastId): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->fail('Unauthorized', 401);
        }

        $notifications = $this->broadcastNotificationsFor($user, $broadcastId)->get();
        if ($notifications->isEmpty()) {
            return $this->fail('Không tìm thấy thông báo.', 404);
        }

        $count = $notifications->count();
        $notifications->each->delete();

        return $this->ok(['deleted' => $count], 'Đã xóa thông báo.');
    }

    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        $notification = $request->user()?->notifications()->where('id', $notificationId)->first();
        if (!$notification) {
            return $this->fail('Khong tim thay thong bao.', 404);
        }

        $notification->markAsRead();

        return $this->ok(null, 'Da danh dau da doc');
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $layoutKey = $user ? $this->currentLayoutKey($user) : null;
        $user?->unreadNotifications()
            ->get()
            ->filter(fn ($n) => !$this->isExpired($n->data ?? []))
            ->filter(fn ($n) => !$this->isScheduled($n->data ?? []))
            ->filter(fn ($n) => $this->matchesCurrentLayout($n->data ?? [], $layoutKey))
            ->each->markAsRead();

        return $this->ok(null, 'Da danh dau tat ca da doc');
    }

    private function validateBroadcast(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:2000'],
            'target_roles' => ['required', 'array', 'min:1'],
            'target_roles.*' => ['required', Rule::in(array_keys(self::DEPARTMENT_ROLE_OPTIONS))],
            'url' => ['nullable', 'string', 'max:500'],
            'expires_at' => ['nullable', 'date'],
        ]);
    }

    private function currentLayoutKey(User $user): string
    {
        $user->loadMissing(['roles', 'defaultRole']);
        return $this->resolveLayout($this->resolveSelectedMobileRole($user));
    }

    private function matchesCurrentLayout(array $data, ?string $layoutKey): bool
    {
        if (($data['type'] ?? '') !== 'department_broadcast') {
            return true;
        }

        return departmentBroadcastMatchesLayout($data, $layoutKey);
    }

    private function isExpired(array $data): bool
    {
        return function_exists('departmentBroadcastIsExpired')
            ? departmentBroadcastIsExpired($data)
            : false;
    }

    private function isScheduled(array $data): bool
    {
        return function_exists('departmentBroadcastIsScheduled')
            ? departmentBroadcastIsScheduled($data)
            : false;
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

    private function sentBroadcasts(User $sender): Collection
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
                    'title' => (string) ($data['title'] ?? 'Thông báo'),
                    'message' => (string) ($data['message'] ?? ''),
                    'url' => (string) ($data['url'] ?? ''),
                    'target_roles' => (array) ($data['target_roles'] ?? []),
                    'target_role_names' => (array) ($data['target_role_names'] ?? []),
                    'expires_at' => (string) ($data['expires_at'] ?? ''),
                    'created_at' => optional($first->created_at)->toIso8601String(),
                    'recipient_count' => $group->count(),
                    'read_count' => $group->whereNotNull('read_at')->count(),
                    'is_expired' => $this->isExpired($data),
                    'can_edit' => !empty($data['broadcast_id']),
                ];
            })
            ->sortByDesc('created_at')
            ->values();
    }
}
