<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Models\MobileLocationPing;
use App\Models\Order;
use App\Models\OrderHistory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ShipperApiController extends BaseApiController
{
    public function dashboard(Request $request): JsonResponse
    {
        $this->ensureShipperRole($request);
        $userId = (int) $request->user()->id;
        $today = now()->toDateString();

        $stats = [
            'today_total' => Order::query()->where('shipper_id', $userId)->whereDate('updated_at', $today)->count(),
            'available' => Order::query()->where('status', Order::STATUS_READY_TO_SHIP)
                ->where(function ($query) use ($userId) {
                    $query->whereNull('shipper_id')
                        ->orWhere(function ($assignedQuery) use ($userId) {
                            $assignedQuery->where('shipper_id', $userId);
                            $this->constrainConfirmedDeliverySchedule($assignedQuery);
                        });
                })
                ->count(),
            'delivering' => Order::query()->where('shipper_id', $userId)->where('status', Order::STATUS_DELIVERING)->count(),
            'delivered_today' => Order::query()->where('shipper_id', $userId)->where('status', 'delivered')->whereDate('delivered_at', $today)->count(),
            'returning' => Order::query()->where('shipper_id', $userId)->where('status', Order::STATUS_RETURNING)->count(),
        ];

        return $this->ok($stats);
    }

    public function availableOrders(Request $request): JsonResponse
    {
        $this->ensureShipperRole($request);
        $userId = (int) $request->user()->id;

        $orders = Order::query()
            ->with(['customer:id,name,phone,address'])
            ->where('status', Order::STATUS_READY_TO_SHIP)
            ->where(function ($query) use ($userId) {
                $query->whereNull('shipper_id')
                    ->orWhere(function ($assignedQuery) use ($userId) {
                        $assignedQuery->where('shipper_id', $userId);
                        $this->constrainConfirmedDeliverySchedule($assignedQuery);
                    });
            })
            ->latest('updated_at')
            ->paginate(20);

        return $this->paginated($orders);
    }

    public function deliverySchedules(Request $request): JsonResponse
    {
        $this->ensureShipperRole($request);
        $userId = (int) $request->user()->id;
        $selectedDate = $this->scheduleDate($request);

        $orders = $this->deliveryScheduleOrdersForShipper($userId, $selectedDate)->get();
        $snapshot = $this->buildDeliveryScheduleSnapshot($orders);
        $snapshotHash = $this->hashDeliveryScheduleSnapshot($snapshot);
        $latestHistory = $this->latestDeliveryScheduleHistoryForShipperOnDate($userId, $selectedDate);

        return $this->ok([
            'date' => $selectedDate,
            'status' => $this->deliveryScheduleStatus($latestHistory, $snapshotHash),
            'orders_count' => $orders->count(),
            'orders' => $orders,
        ]);
    }

    public function confirmDeliverySchedule(Request $request): JsonResponse
    {
        return $this->recordDeliveryScheduleDecision($request, 'schedule_confirmed', 'Da xac nhan lo trinh giao hang');
    }

    public function rejectDeliverySchedule(Request $request): JsonResponse
    {
        return $this->recordDeliveryScheduleDecision($request, 'schedule_rejected', 'Da tu choi lo trinh giao hang');
    }

    public function acceptOrder(Request $request, Order $order): JsonResponse
    {
        $this->ensureShipperRole($request);
        $user = $request->user();

        if ($order->status !== Order::STATUS_READY_TO_SHIP) {
            return $this->fail('Don khong o trang thai co the nhan.', 422);
        }

        if (!is_null($order->shipper_id) && (int) $order->shipper_id !== (int) $user->id && !$user->hasRole('admin')) {
            return $this->fail('Don da duoc shipper khac nhan.', 422);
        }

        if ((int) ($order->shipper_id ?? 0) === (int) $user->id && !$this->orderHasConfirmedDeliverySchedule($order)) {
            return $this->fail('Vui long xac nhan lo trinh giao hang truoc khi nhan don.', 422);
        }

        $order->update([
            'shipper_id' => $user->id,
            'status' => Order::STATUS_DELIVERING,
        ]);

        OrderHistory::query()->create([
            'order_id' => $order->id,
            'action' => 'mobile_accept_order',
            'user_id' => $user->id,
            'role' => 'shipper',
            'status_before' => Order::STATUS_READY_TO_SHIP,
            'status_after' => Order::STATUS_DELIVERING,
            'note' => 'Shipper nhan don qua mobile app',
        ]);

        return $this->ok(null, 'Nhan don thanh cong');
    }

    public function myOrders(Request $request): JsonResponse
    {
        $this->ensureShipperRole($request);
        $userId = (int) $request->user()->id;

        $orders = Order::query()
            ->with(['customer:id,name,phone,address'])
            ->where('shipper_id', $userId)
            ->whereIn('status', [Order::STATUS_DELIVERING, 'delivered', Order::STATUS_RETURNING, 'completed'])
            ->latest('updated_at')
            ->paginate(20);

        return $this->paginated($orders);
    }

    public function history(Request $request): JsonResponse
    {
        $this->ensureShipperRole($request);
        $userId = (int) $request->user()->id;

        $orders = Order::query()
            ->with(['customer:id,name,phone,address'])
            ->where('shipper_id', $userId)
            ->whereIn('status', ['delivered', 'completed', Order::STATUS_RETURNING, Order::STATUS_RETURNED_COMPLETED])
            ->latest('updated_at')
            ->paginate(20);

        return $this->paginated($orders);
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $this->ensureShipperRole($request);
        $user = $request->user();
        if ((int) $order->shipper_id !== (int) $user->id && !$user->hasRole('admin')) {
            return $this->fail('Khong co quyen thao tac don nay', 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:delivered,returning'],
            'collected_amount' => ['nullable', 'numeric', 'min:0'],
            'return_reason' => ['nullable', 'string', 'max:500'],
            'shipper_note' => ['nullable', 'string', 'max:1000'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
        ]);

        $before = (string) $order->status;
        $nextStatus = $validated['status'] === 'returning' ? Order::STATUS_RETURNING : 'delivered';

        $order->update([
            'status' => $nextStatus,
            'collected_amount' => $validated['collected_amount'] ?? $order->collected_amount,
            'return_reason' => $validated['return_reason'] ?? $order->return_reason,
            'shipper_note' => $validated['shipper_note'] ?? $order->shipper_note,
            'delivered_at' => $nextStatus === 'delivered' ? now() : $order->delivered_at,
        ]);

        OrderHistory::query()->create([
            'order_id' => $order->id,
            'action' => 'mobile_update_delivery_status',
            'user_id' => $user->id,
            'role' => 'shipper',
            'status_before' => $before,
            'status_after' => $nextStatus,
            'note' => 'Mobile update status. GPS: ' . ($validated['lat'] ?? '-') . ',' . ($validated['lng'] ?? '-'),
        ]);

        return $this->ok([
                'order_id' => (int) $order->id,
                'status' => $nextStatus,
            ], 'Cap nhat trang thai thanh cong');
    }

    public function uploadProof(Request $request, Order $order): JsonResponse
    {
        $this->ensureShipperRole($request);
        $user = $request->user();
        if ((int) $order->shipper_id !== (int) $user->id && !$user->hasRole('admin')) {
            return $this->fail('Khong co quyen upload anh cho don nay', 403);
        }

        $validated = $request->validate([
            'proof_image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $path = $validated['proof_image']->store('mobile/proof-delivery', 'public');

        $proofImages = $order->proof_images ?: [];
        $proofImages[] = $path;

        $order->update([
            'delivered_image_path' => $path,
            'proof_images' => $proofImages,
        ]);

        return $this->ok([
                'path' => $path,
                'url' => Storage::disk('public')->url($path),
            ], 'Upload anh thanh cong');
    }

    public function updateLocation(Request $request): JsonResponse
    {
        $this->ensureShipperRole($request);

        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        MobileLocationPing::query()->create([
            'user_id' => (int) $request->user()->id,
            'lat' => (float) $validated['lat'],
            'lng' => (float) $validated['lng'],
            'accuracy' => isset($validated['accuracy']) ? (float) $validated['accuracy'] : null,
            'recorded_at' => $validated['recorded_at'] ?? now(),
        ]);

        return $this->ok(null, 'GPS updated');
    }

    public function notifications(Request $request): JsonResponse
    {
        $this->ensureShipperRole($request);
        $limit = min(50, max(1, (int) $request->query('limit', 20)));

        $items = $request->user()
            ->notifications()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($n) => [
                'id' => (string) $n->id,
                'title' => (string) ($n->data['title'] ?? 'Thong bao'),
                'message' => (string) ($n->data['message'] ?? ''),
                'read_at' => optional($n->read_at)->toIso8601String(),
                'created_at' => optional($n->created_at)->toIso8601String(),
            ])
            ->values();

        return $this->ok($items);
    }

    private function ensureShipperRole(Request $request): void
    {
        $user = $request->user();
        if (!$user || !($user->hasRole('shipper') || $user->hasRole('ship') || $user->hasRole('manager_shipper') || $user->hasRole('admin'))) {
            abort(403, 'Role khong duoc phep truy cap API shipper');
        }
    }

    private function recordDeliveryScheduleDecision(Request $request, string $historyAction, string $successMessage): JsonResponse
    {
        $this->ensureShipperRole($request);
        $user = $request->user();
        $userId = (int) $user->id;
        $selectedDate = $this->scheduleDate($request);

        $validated = $request->validate([
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'exists:orders,id'],
        ]);

        $requestedOrderIds = array_map('intval', $validated['order_ids']);
        $orders = $this->deliveryScheduleOrdersForShipper($userId, $selectedDate)
            ->whereIn('id', $requestedOrderIds)
            ->get();

        if ($orders->count() !== count(array_unique($requestedOrderIds))) {
            return $this->fail('Co don khong thuoc lo trinh cua ban.', 403);
        }

        $snapshot = $this->buildDeliveryScheduleSnapshot($orders);
        $snapshotHash = $this->hashDeliveryScheduleSnapshot($snapshot);

        DB::transaction(function () use ($orders, $user, $userId, $historyAction, $snapshotHash, $snapshot): void {
            foreach ($orders as $order) {
                OrderHistory::query()->create([
                    'order_id' => $order->id,
                    'action' => $historyAction,
                    'user_id' => $userId,
                    'role' => 'shipper',
                    'status_before' => $order->status,
                    'status_after' => $order->status,
                    'note' => 'Shipper ' . $user->name . ' cap nhat trang thai lo trinh giao hang qua mobile app.',
                    'schedule_snapshot_hash' => $snapshotHash,
                    'schedule_snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
            }
        });

        return $this->ok(null, $successMessage);
    }

    private function scheduleDate(Request $request): string
    {
        return $request->filled('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : Carbon::today()->toDateString();
    }

    private function deliveryScheduleOrdersForShipper(int $shipperId, string $selectedDate)
    {
        return Order::query()
            ->with(['customer:id,name,phone,address', 'items.variant'])
            ->where('shipper_id', $shipperId)
            ->whereIn('status', $this->assignmentStatuses())
            ->where(function ($query) use ($selectedDate) {
                $query->whereDate('created_at', $selectedDate)
                    ->orWhereDate('updated_at', $selectedDate);
            })
            ->orderByRaw('CASE WHEN daily_sequence IS NULL THEN 1 ELSE 0 END')
            ->orderBy('daily_sequence', 'asc')
            ->orderBy('delivery_time', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc');
    }

    private function assignmentStatuses(): array
    {
        return [
            Order::STATUS_APPROVED,
            Order::STATUS_READY_TO_PACK,
            Order::STATUS_PACKING,
            Order::STATUS_READY_TO_SHIP,
        ];
    }

    private function buildDeliveryScheduleSnapshot($orders): array
    {
        return $orders->map(function ($order) {
            return [
                'order_id' => (int) $order->id,
                'daily_sequence' => $order->daily_sequence !== null ? (int) $order->daily_sequence : null,
                'delivery_time' => $order->delivery_time,
                'updated_at' => optional($order->updated_at)->toDateTimeString(),
            ];
        })->values()->all();
    }

    private function hashDeliveryScheduleSnapshot(array $snapshot): string
    {
        return hash('sha256', json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function latestDeliveryScheduleHistoryForShipperOnDate(int $shipperId, string $selectedDate): ?OrderHistory
    {
        return OrderHistory::query()
            ->join('orders', 'orders.id', '=', 'order_histories.order_id')
            ->where('orders.shipper_id', $shipperId)
            ->whereDate('order_histories.created_at', $selectedDate)
            ->whereIn('order_histories.action', ['schedule_created', 'schedule_confirmed', 'schedule_rejected'])
            ->orderByDesc('order_histories.created_at')
            ->orderByDesc('order_histories.id')
            ->select('order_histories.*')
            ->first();
    }

    private function deliveryScheduleStatus(?OrderHistory $latestHistory, string $currentSnapshotHash): string
    {
        if (!$latestHistory) {
            return 'none';
        }

        if ($latestHistory->action === 'schedule_confirmed' && $latestHistory->schedule_snapshot_hash === $currentSnapshotHash) {
            return 'confirmed';
        }

        if ($latestHistory->action === 'schedule_rejected' && $latestHistory->schedule_snapshot_hash === $currentSnapshotHash) {
            return 'rejected';
        }

        if ($latestHistory->action === 'schedule_created') {
            return 'waiting';
        }

        return 'changed';
    }

    private function constrainConfirmedDeliverySchedule($query): void
    {
        $query->whereExists(function ($historyQuery) {
            $historyQuery->selectRaw('1')
                ->from('order_histories as latest_schedule_history')
                ->whereColumn('latest_schedule_history.order_id', 'orders.id')
                ->where('latest_schedule_history.action', 'schedule_confirmed')
                ->whereRaw(
                    'latest_schedule_history.id = (
                        select oh2.id
                        from order_histories as oh2
                        where oh2.order_id = orders.id
                          and oh2.action in ("schedule_created", "schedule_confirmed", "schedule_rejected")
                        order by oh2.created_at desc, oh2.id desc
                        limit 1
                    )'
                );
        });
    }

    private function orderHasConfirmedDeliverySchedule(Order $order): bool
    {
        return OrderHistory::query()
            ->where('order_id', $order->id)
            ->whereIn('action', ['schedule_created', 'schedule_confirmed', 'schedule_rejected'])
            ->latest('created_at')
            ->latest('id')
            ->value('action') === 'schedule_confirmed';
    }
}
