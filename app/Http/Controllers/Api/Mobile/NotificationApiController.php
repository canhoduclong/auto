<?php

namespace App\Http\Controllers\Api\Mobile;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return $this->fail('Unauthorized', 401);
        }

        $limit = min(50, max(1, (int) $request->query('limit', 20)));
        $items = $user->notifications()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($n) => [
                'id' => (string) $n->id,
                'title' => (string) ($n->data['title'] ?? 'Thong bao'),
                'message' => (string) ($n->data['message'] ?? ''),
                'priority' => (string) ($n->data['priority'] ?? 'info'),
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
            'unread_count' => (int) $user->unreadNotifications()->count(),
            'items' => $items,
        ]);
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
        $request->user()?->unreadNotifications->markAsRead();

        return $this->ok(null, 'Da danh dau tat ca da doc');
    }
}
