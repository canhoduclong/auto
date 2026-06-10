<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\ShipperDashboardController;
use App\Models\MobileLocationPing;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\OrderReturn;
use App\Models\ReturnItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
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
            'available' => Order::query()->where(function ($query) {
                $query->where('status', Order::STATUS_READY_TO_SHIP)
                    ->orWhere(function ($returnQuery) {
                        $returnQuery->where('status', Order::STATUS_APPROVED)->where('is_return_order', true);
                    });
            })
                ->where('shipper_id', $userId)
                ->whereNotIn('status', ['cancelled', 'canceled'])
                ->where(function ($query) {
                    $this->constrainConfirmedDeliverySchedule($query);
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
        $selectedDate = $this->scheduleDate($request);

        $orders = Order::query()
            ->with(['customer:id,name,phone,address', 'items.product:id,name,unit', 'items.variant:id,name,sku,size,product_id'])
            ->where(function ($query) {
                $query->where('status', Order::STATUS_READY_TO_SHIP)
                    ->orWhere(function ($returnQuery) {
                        $returnQuery->where('status', Order::STATUS_APPROVED)->where('is_return_order', true);
                    });
            })
            ->where('shipper_id', $userId)
            ->whereNotIn('status', ['cancelled', 'canceled'])
            ->where(function ($query) {
                $this->constrainConfirmedDeliverySchedule($query);
            })
            ->where(function ($query) use ($selectedDate) {
                $query->whereDate('updated_at', $selectedDate)
                    ->orWhereDate('created_at', $selectedDate);
            })
            ->tap(function ($query) {
                $this->constrainNoActiveWarehouseTransfer($query);
            })
            ->latest('updated_at')
            ->paginate(20);

        $this->attachDeliveryScheduleMetadata($orders->getCollection());

        return $this->paginated($orders);
    }

    public function acceptedOrders(Request $request): JsonResponse
    {
        $this->ensureShipperRole($request);
        $userId = (int) $request->user()->id;
        $selectedDate = $this->scheduleDate($request);

        $orders = Order::query()
            ->with(['customer:id,name,phone,address', 'items.product:id,name,unit', 'items.variant:id,name,sku,size,product_id'])
            ->where('shipper_id', $userId)
            ->where('status', Order::STATUS_DELIVERING)
            ->where(function ($query) use ($selectedDate) {
                $query->whereDate('updated_at', $selectedDate)
                    ->orWhereDate('created_at', $selectedDate);
            })
            ->tap(function ($query) {
                $this->constrainNoActiveWarehouseTransfer($query);
            })
            ->orderByRaw('CASE WHEN daily_sequence IS NULL THEN 1 ELSE 0 END')
            ->orderBy('daily_sequence', 'asc')
            ->orderBy('delivery_time', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->paginate(20);

        $this->attachDeliveryScheduleMetadata($orders->getCollection());

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
        $status = $this->deliveryScheduleStatus($latestHistory, $snapshotHash);
        $pendingOrders = in_array($status, ['waiting', 'changed'], true) ? $orders : collect();

        return $this->ok([
            'date' => $selectedDate,
            'id' => $latestHistory?->id,
            'code' => $this->deliveryScheduleCode($userId, $selectedDate, $latestHistory),
            'status' => $status,
            'notes' => $latestHistory?->note,
            'confirmed_at' => $status === 'confirmed' ? optional($latestHistory?->created_at)->toIso8601String() : null,
            'orders_count' => $pendingOrders->count(),
            'total_cod' => (float) $pendingOrders->sum('total'),
            'orders' => $pendingOrders->values(),
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
        Auth::setUser($request->user());
        $request->headers->set('Accept', 'application/json');
        $response = app(ShipperDashboardController::class)->accept($order);
        $payload = $response instanceof JsonResponse ? $response->getData(true) : [];

        if ($response instanceof JsonResponse && $response->getStatusCode() >= 400) {
            return $this->fail((string) ($payload['message'] ?? 'Khong the nhan don.'), $response->getStatusCode());
        }

        return $this->ok($payload['order'] ?? null, (string) ($payload['message'] ?? 'Nhan don thanh cong'));
    }

    public function warehouses(Request $request): JsonResponse
    {
        $this->ensureShipperRole($request);

        return $this->ok(Warehouse::query()->orderBy('name')->get(['id', 'name']));
    }

    public function returnOrder(Request $request, Order $order): JsonResponse
    {
        $this->ensureShipperRole($request);
        $user = $request->user();
        if ((int) $order->shipper_id !== (int) $user->id && !$user->hasRole('admin')) {
            return $this->fail('Khong co quyen thao tac don nay.', 403);
        }
        if ($order->status !== Order::STATUS_DELIVERING) {
            return $this->fail('Don khong dang giao.', 422);
        }

        $validated = $request->validate([
            'return_reason' => ['required', 'string', 'max:500'],
            'return_note' => ['nullable', 'string', 'max:500'],
            'return_warehouse_id' => ['required', 'exists:warehouses,id'],
        ]);
        $warehouse = Warehouse::query()->findOrFail((int) $validated['return_warehouse_id']);
        $note = trim((string) ($validated['return_note'] ?? ''));
        $note = trim($note . ' | Kho trả về: ' . $warehouse->name, ' |');

        $orderReturn = DB::transaction(function () use ($order, $user, $validated, $warehouse, $note) {
            $updates = [
                'status' => Order::STATUS_RETURNING,
                'return_reason' => $validated['return_reason'],
                'shipper_note' => $note,
            ];
            if (Schema::hasColumn('orders', 'return_warehouse_id')) {
                $updates['return_warehouse_id'] = $warehouse->id;
            }
            if (Schema::hasColumn('orders', 'warehouse_id')) {
                $updates['warehouse_id'] = $warehouse->id;
            }
            $order->update($updates);
            $order->loadMissing('items');

            $orderReturn = OrderReturn::query()->firstOrCreate(
                ['order_id' => $order->id, 'status' => 'pending_warehouse'],
                [
                    'customer_id' => $order->customer_id,
                    'warehouse_id' => $warehouse->id,
                    'created_by' => $user->id,
                    'reason' => $validated['return_reason'],
                    'return_scope' => 'full',
                    'refund_amount' => (float) ($order->total ?? 0),
                    'note' => $note,
                ]
            );
            $orderReturn->update(['warehouse_id' => $warehouse->id, 'reason' => $validated['return_reason'], 'note' => $note]);

            if ($orderReturn->returnItems()->count() === 0) {
                foreach ($order->items as $item) {
                    ReturnItem::query()->create([
                        'order_return_id' => $orderReturn->id,
                        'product_variant_id' => $item->product_variant_id,
                        'quantity' => (int) $item->quantity,
                        'condition' => 'good',
                    ]);
                }
            }

            OrderHistory::query()->create([
                'order_id' => $order->id,
                'action' => 'return_request',
                'user_id' => $user->id,
                'role' => 'shipper',
                'status_before' => Order::STATUS_DELIVERING,
                'status_after' => Order::STATUS_RETURNING,
                'note' => 'Shipper gửi trả hàng qua mobile: ' . $validated['return_reason'] . ' | Kho trả về: ' . $warehouse->name,
            ]);

            return $orderReturn;
        });

        return $this->ok(['return_id' => (int) $orderReturn->id], 'Da tao phieu tra hang cho kho tiep nhan');
    }

    public function assignOrder(Request $request, Order $order, User $shipper): JsonResponse
    {
        $this->ensureManagerShipperRole($request);
        if (!in_array($order->status, $this->assignmentStatuses(), true)) {
            return $this->fail('Don chua o trang thai co the dieu phoi.', 422);
        }
        if (!($shipper->hasRole('shipper') || $shipper->hasRole('manager_shipper'))) {
            return $this->fail('Nguoi dung khong phai shipper.', 422);
        }
        $previous = $order->shipper;
        $order->update(['shipper_id' => $shipper->id]);
        OrderHistory::query()->create([
            'order_id' => $order->id,
            'action' => $previous ? 'shipper_reassigned' : 'shipper_assigned',
            'user_id' => $request->user()->id,
            'role' => 'manager_shipper',
            'status_before' => $order->status,
            'status_after' => $order->status,
            'note' => 'Điều phối mobile: ' . ($previous?->name ? $previous->name . ' -> ' : '') . $shipper->name,
        ]);

        return $this->ok(null, 'Da dieu phoi don cho ' . $shipper->name);
    }

    public function unassignOrder(Request $request, Order $order): JsonResponse
    {
        $this->ensureManagerShipperRole($request);
        if (!in_array($order->status, $this->assignmentStatuses(), true) || !$order->shipper_id) {
            return $this->fail('Don khong the go dieu phoi.', 422);
        }
        $previous = $order->shipper;
        $order->update(['shipper_id' => null]);
        OrderHistory::query()->create([
            'order_id' => $order->id,
            'action' => 'shipper_unassigned',
            'user_id' => $request->user()->id,
            'role' => 'manager_shipper',
            'status_before' => $order->status,
            'status_after' => $order->status,
            'note' => 'Gỡ điều phối mobile khỏi ' . ($previous?->name ?? 'shipper'),
        ]);

        return $this->ok(null, 'Da go dieu phoi don');
    }

    public function createDeliverySchedules(Request $request): JsonResponse
    {
        $this->ensureManagerShipperRole($request);
        $date = now()->toDateString();
        $groups = Order::query()
            ->whereNotNull('shipper_id')
            ->whereIn('status', $this->assignmentStatuses())
            ->where(function ($query) use ($date) {
                $query->whereDate('created_at', $date)->orWhereDate('updated_at', $date);
            })
            ->orderByRaw('CASE WHEN daily_sequence IS NULL THEN 1 ELSE 0 END')
            ->orderBy('daily_sequence')
            ->orderBy('delivery_time')
            ->orderBy('created_at')
            ->get()
            ->groupBy('shipper_id');

        if ($groups->isEmpty()) {
            return $this->fail('Khong co don da gan shipper de tao lich trinh.', 422);
        }

        $count = 0;
        DB::transaction(function () use ($groups, $request, &$count) {
            foreach ($groups as $orders) {
                $snapshot = $this->buildDeliveryScheduleSnapshot($orders);
                $hash = $this->hashDeliveryScheduleSnapshot($snapshot);
                foreach ($orders as $order) {
                    OrderHistory::query()->create([
                        'order_id' => $order->id,
                        'action' => 'schedule_created',
                        'user_id' => $request->user()->id,
                        'role' => 'manager_shipper',
                        'status_before' => $order->status,
                        'status_after' => $order->status,
                        'note' => 'Lịch trình giao hàng được gửi từ mobile.',
                        'schedule_snapshot_hash' => $hash,
                        'schedule_snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                    $count++;
                }
            }
        });

        return $this->ok(['orders_count' => $count], 'Da gui lich trinh giao hang');
    }

    public function myOrders(Request $request): JsonResponse
    {
        $this->ensureShipperRole($request);
        $userId = (int) $request->user()->id;

        $orders = Order::query()
            ->with(['customer:id,name,phone,address', 'items.product:id,name,unit', 'items.variant:id,name,sku,size,product_id'])
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
            ->with(['customer:id,name,phone,address', 'items.product:id,name,unit', 'items.variant:id,name,sku,size,product_id'])
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

    private function ensureManagerShipperRole(Request $request): void
    {
        $user = $request->user();
        if (!$user || !($user->hasRole('manager_shipper') || $user->hasRole('admin'))) {
            abort(403, 'Role khong duoc phep dieu phoi shipper');
        }
    }

    private function recordDeliveryScheduleDecision(Request $request, string $historyAction, string $successMessage): JsonResponse
    {
        $this->ensureShipperRole($request);
        $user = $request->user();
        $userId = (int) $user->id;
        $selectedDate = $this->scheduleDate($request);

        $rules = [
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'exists:orders,id'],
        ];

        if ($historyAction === 'schedule_rejected') {
            $rules['reason'] = ['required', 'string', 'max:500'];
        }

        $validated = $request->validate($rules);

        $requestedOrderIds = array_map('intval', $validated['order_ids']);
        $orders = $this->deliveryScheduleOrdersForShipper($userId, $selectedDate)
            ->whereIn('id', $requestedOrderIds)
            ->get();

        if ($orders->count() !== count(array_unique($requestedOrderIds))) {
            return $this->fail('Co don khong thuoc lo trinh cua ban.', 403);
        }

        $snapshot = $this->buildDeliveryScheduleSnapshot($orders);
        $snapshotHash = $this->hashDeliveryScheduleSnapshot($snapshot);

        $decisionNote = $historyAction === 'schedule_rejected'
            ? 'Shipper ' . $user->name . ' tu choi lo trinh giao hang qua mobile app. Ly do: ' . trim((string) $validated['reason'])
            : 'Shipper ' . $user->name . ' xac nhan lo trinh giao hang qua mobile app.';

        DB::transaction(function () use ($orders, $userId, $historyAction, $snapshotHash, $snapshot, $decisionNote): void {
            foreach ($orders as $order) {
                OrderHistory::query()->create([
                    'order_id' => $order->id,
                    'action' => $historyAction,
                    'user_id' => $userId,
                    'role' => 'shipper',
                    'status_before' => $order->status,
                    'status_after' => $order->status,
                    'note' => $decisionNote,
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
            ->with(['customer:id,name,phone,address', 'items.product:id,name,unit', 'items.variant:id,name,sku,size,product_id'])
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

    private function constrainNoActiveWarehouseTransfer($query): void
    {
        $query->whereDoesntHave('warehouseTransfers', function ($transferQuery) {
            $transferQuery->whereIn('status', [
                WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP,
                WarehouseTransfer::STATUS_IN_TRANSIT,
                WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE,
            ]);
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

    private function deliveryScheduleCode(int $shipperId, string $selectedDate, ?OrderHistory $history): string
    {
        $suffix = $history?->schedule_snapshot_hash
            ? strtoupper(substr((string) $history->schedule_snapshot_hash, 0, 6))
            : str_pad((string) $shipperId, 3, '0', STR_PAD_LEFT);

        return 'LT-' . Carbon::parse($selectedDate)->format('Ymd') . '-' . $suffix;
    }

    private function attachDeliveryScheduleMetadata($orders): void
    {
        $orderIds = $orders->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (empty($orderIds)) {
            return;
        }

        $histories = OrderHistory::query()
            ->whereIn('order_id', $orderIds)
            ->whereIn('action', ['schedule_created', 'schedule_confirmed', 'schedule_rejected'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('order_id')
            ->map(fn ($items) => $items->first());

        foreach ($orders as $order) {
            $history = $histories->get($order->id);
            if (!$history) {
                continue;
            }

            $scheduleDate = optional($history->created_at)->toDateString() ?: Carbon::today()->toDateString();
            $order->setAttribute('delivery_schedule', [
                'id' => (int) $history->id,
                'code' => $this->deliveryScheduleCode((int) $order->shipper_id, $scheduleDate, $history),
                'status' => match ($history->action) {
                    'schedule_confirmed' => 'confirmed',
                    'schedule_rejected' => 'rejected',
                    default => 'waiting',
                },
                'confirmed_at' => $history->action === 'schedule_confirmed'
                    ? optional($history->created_at)->toIso8601String()
                    : null,
            ]);
        }
    }
}
