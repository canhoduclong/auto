<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\User;
use App\Services\ShipperAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShipperMobileController extends Controller
{
    public function index()
    {
        return view('mobile.shipper.index');
    }

    public function todayOrders(Request $request): JsonResponse
    {
        $user = $request->user();

        $orders = Order::query()
            ->with(['customer:id,name,phone,address'])
            ->where('shipper_id', $user->id)
            ->whereIn('status', [Order::STATUS_DELIVERING, 'delivered', Order::STATUS_RETURNING, 'completed'])
            ->whereDate('updated_at', now()->toDateString())
            ->orderByDesc('updated_at')
            ->limit(80)
            ->get();

        $data = $orders->map(function (Order $order) {
            return [
                'id' => (int) $order->id,
                'code' => (string) ($order->code ?: ('#' . $order->id)),
                'status' => (string) $order->status,
                'customer' => (string) ($order->customer?->name ?? '—'),
                'phone' => (string) ($order->customer?->phone ?? '—'),
                'address' => (string) ($order->customer?->address ?? '—'),
                'total' => (float) ($order->total ?? 0),
                'amount_due' => (float) ($order->amount_due ?? 0),
                'updated_at' => optional($order->updated_at)->format('d/m H:i'),
            ];
        });

        return response()->json(['data' => $data]);
    }

    public function orderDetail(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($request, $order);

        $order->load(['customer:id,name,phone,address', 'items.variant.product']);

        return response()->json([
            'data' => $this->mobileOrderDetailPayload($order),
        ]);
    }

    public function completeOrder(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($request, $order);

        if ($order->status !== Order::STATUS_DELIVERING) {
            return response()->json(['ok' => false, 'message' => 'Đơn không ở trạng thái đang giao.'], 422);
        }

        $validated = $request->validate([
            'collected_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'in:cash,transfer'],
        ]);

        $collected = (float) ($validated['collected_amount'] ?? 0);
        $paymentMethod = (string) ($validated['payment_method'] ?? 'cash');

        $order->update([
            'status' => 'delivered',
            'collected_amount' => $collected,
            'delivered_at' => now(),
        ]);

        OrderHistory::create([
            'order_id' => $order->id,
            'action' => 'mobile_delivered',
            'user_id' => $request->user()->id,
            'role' => 'shipper',
            'status_before' => Order::STATUS_DELIVERING,
            'status_after' => 'delivered',
            'note' => 'Mobile xác nhận giao thành công. Đã thu: ' . number_format($collected) . 'đ - ' . ($paymentMethod === 'cash' ? 'Tiền mặt' : 'Chuyển khoản'),
        ]);

        $order->refresh()->load(['customer:id,name,phone,address', 'items.variant.product']);

        return response()->json([
            'ok' => true,
            'message' => 'Đã xác nhận giao hàng thành công.',
            'data' => $this->mobileOrderDetailPayload($order),
        ]);
    }

    public function failOrder(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($request, $order);

        if ($order->status !== Order::STATUS_DELIVERING) {
            return response()->json(['ok' => false, 'message' => 'Đơn không ở trạng thái đang giao.'], 422);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $order->update([
            'status' => Order::STATUS_RETURNING,
            'return_reason' => $validated['reason'],
        ]);

        OrderHistory::create([
            'order_id' => $order->id,
            'action' => 'mobile_delivery_failed',
            'user_id' => $request->user()->id,
            'role' => 'shipper',
            'status_before' => Order::STATUS_DELIVERING,
            'status_after' => Order::STATUS_RETURNING,
            'note' => 'Mobile báo giao thất bại: ' . $validated['reason'],
        ]);

        return response()->json(['ok' => true, 'message' => 'Đã cập nhật giao thất bại.']);
    }

    public function assignments(Request $request): JsonResponse
    {
        $this->authorizeManager($request);

        $date = \Carbon\Carbon::parse($request->query('date', now()->toDateString()))->toDateString();
        $statuses = $this->assignmentStatuses();
        $shippers = $this->availableShippers();
        $orders = Order::query()
            ->with([
                'customer.defaultShipper:id,name',
                'customer.truckStation',
                'customer.truckRoute.stops.station',
                'shipper:id,name',
                'user:id,name',
                'warehouse:id,name',
                'items.product',
                'items.variant.product',
            ])
            ->whereIn('status', $statuses)
            ->whereDate('created_at', $date)
            ->orderByRaw('CASE WHEN shipper_id IS NULL THEN 0 ELSE 1 END')
            ->orderByRaw("CASE WHEN delivery_time IS NULL OR delivery_time = '' THEN 1 ELSE 0 END")
            ->orderBy('delivery_time')
            ->orderByRaw('CASE WHEN daily_sequence IS NULL THEN 1 ELSE 0 END')
            ->orderBy('daily_sequence')
            ->orderBy('created_at')
            ->get();

        $canPublishSchedule = $orders
            ->whereNotNull('shipper_id')
            ->pluck('shipper_id')
            ->unique()
            ->contains(fn ($shipperId) => app(ShipperAssignmentService::class)->hasUnpublishedDailySchedule((int) $shipperId, $date));

        return response()->json([
            'data' => [
                'selected_date' => $date,
                'can_publish_schedule' => $canPublishSchedule,
                'cards' => [
                    ['label' => 'Tổng đơn', 'value' => $orders->count()],
                    ['label' => 'Chưa gán', 'value' => $orders->whereNull('shipper_id')->count()],
                    ['label' => 'Đã gán', 'value' => $orders->whereNotNull('shipper_id')->count()],
                    ['label' => 'Shipper', 'value' => $shippers->count()],
                ],
                'shippers' => $shippers,
                'items' => $orders->map(fn (Order $order) => $this->assignmentOrderPayload($order, $shippers))->values(),
            ],
        ]);
    }

    public function assignOrder(Request $request, Order $order): JsonResponse
    {
        $this->authorizeManager($request);

        $validated = $request->validate([
            'shipper_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        if (!in_array((string) $order->status, $this->assignmentStatuses(), true)) {
            return response()->json(['ok' => false, 'message' => 'Đơn chưa ở trạng thái có thể điều phối.'], 422);
        }

        $shipper = User::query()->findOrFail((int) $validated['shipper_id']);
        if (!($shipper->hasRole('shipper') || $shipper->hasRole('ship') || $shipper->hasRole('manager_shipper'))) {
            return response()->json(['ok' => false, 'message' => 'Người được chọn không phải shipper.'], 422);
        }

        $previous = $order->shipper;
        $order->update(['shipper_id' => $shipper->id]);

        if ($order->customer && !$order->customer->default_shipper_id) {
            $order->customer->update(['default_shipper_id' => $shipper->id]);
        }

        OrderHistory::create([
            'order_id' => $order->id,
            'action' => $previous ? 'shipper_reassigned' : 'shipper_assigned',
            'user_id' => $request->user()->id,
            'role' => 'manager_shipper',
            'status_before' => $order->status,
            'status_after' => $order->status,
            'note' => 'Điều phối mobile: ' . ($previous?->name ? $previous->name . ' -> ' : '') . $shipper->name,
        ]);

        return response()->json(['ok' => true, 'message' => 'Đã gán đơn cho ' . $shipper->name]);
    }

    public function unassignOrder(Request $request, Order $order): JsonResponse
    {
        $this->authorizeManager($request);

        if (!in_array((string) $order->status, $this->assignmentStatuses(), true) || !$order->shipper_id) {
            return response()->json(['ok' => false, 'message' => 'Đơn không thể gỡ điều phối.'], 422);
        }

        $previous = $order->shipper;
        $order->update(['shipper_id' => null]);

        OrderHistory::create([
            'order_id' => $order->id,
            'action' => 'shipper_unassigned',
            'user_id' => $request->user()->id,
            'role' => 'manager_shipper',
            'status_before' => $order->status,
            'status_after' => $order->status,
            'note' => 'Gỡ điều phối mobile khỏi ' . ($previous?->name ?? 'shipper'),
        ]);

        return response()->json(['ok' => true, 'message' => 'Đã gỡ điều phối đơn.']);
    }

    public function createSchedules(Request $request): JsonResponse
    {
        $this->authorizeManager($request);

        $validated = $request->validate([
            'date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $date = \Carbon\Carbon::parse($validated['date'] ?? now()->toDateString())->toDateString();
        $groups = Order::query()
            ->whereNotNull('shipper_id')
            ->whereIn('status', $this->assignmentStatuses())
            ->whereDate('created_at', $date)
            ->get()
            ->groupBy('shipper_id');

        if ($groups->isEmpty()) {
            return response()->json(['ok' => false, 'message' => 'Không có đơn đã gán shipper để tạo lịch trình.'], 422);
        }

        $count = 0;
        foreach ($groups as $shipperId => $orders) {
            if (app(ShipperAssignmentService::class)->publishDailySchedule(
                (int) $shipperId,
                $date,
                (int) $request->user()->id,
                'manager_shipper',
                $validated['notes'] ?? null
            )) {
                $count += $orders->count();
            }
        }

        return response()->json(['ok' => true, 'message' => 'Đã gửi lịch trình giao hàng.', 'orders_count' => $count]);
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        $user = $request->user();

        if ((int) $order->shipper_id !== (int) $user->id && !$user->hasRole('admin')) {
            abort(403, 'Bạn không có quyền thao tác đơn này.');
        }
    }

    private function authorizeManager(Request $request): void
    {
        $user = $request->user();

        if (!$user || !($user->hasRole('manager_shipper') || $user->hasRole('admin'))) {
            abort(403, 'Bạn không có quyền điều phối shipper.');
        }
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

    private function availableShippers()
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['shipper', 'ship', 'manager_shipper']))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $shipper) => [
                'id' => (int) $shipper->id,
                'name' => (string) $shipper->name,
            ])
            ->values();
    }

    private function assignmentOrderPayload(Order $order, $shippers): array
    {
        $customer = $order->customer;
        $selectedRoute = $customer?->truckRoute;
        if (!$selectedRoute && $customer?->truck_station_id) {
            $selectedRoute = $customer?->truckRouteByStation;
        }
        $truckStation = $customer?->truckStation ?: ($selectedRoute?->stops?->last()?->station);
        $truckStationText = trim(collect([$truckStation?->name, $truckStation?->address])->filter()->join(' - '));
        $defaultShippingFee = (bool) ($order->charge_shipping_fee ?? true)
            ? (float) ($order->shipping_fee ?? $customer?->shipping_fee ?? 0)
            : 0;

        return [
            'id' => (int) $order->id,
            'code' => (string) ($order->code ?: ('#' . $order->id)),
            'title' => 'Đơn #' . ($order->daily_sequence ?: $order->code ?: $order->id),
            'status' => (string) (\App\Models\Order::statusOptions()[$order->status] ?? $order->status),
            'status_code' => (string) $order->status,
            'customer_id' => $order->customer_id ? (int) $order->customer_id : null,
            'customer' => (string) ($order->customer?->name ?? 'Khách hàng'),
            'phone' => (string) ($order->customer?->phone ?? ''),
            'address' => (string) ($order->customer?->address ?? ''),
            'origin' => (string) ($order->warehouse?->name ?? 'Chưa chọn kho'),
            'destination' => (string) ($truckStationText ?: $order->recipient_address ?: $customer?->truck_station_address ?: $customer?->address ?: ''),
            'sale_name' => (string) ($order->user?->name ?? 'Chưa có sale'),
            'total' => (float) ($order->total ?? 0),
            'default_shipping_fee' => $defaultShippingFee,
            'shipper_id' => $order->shipper_id ? (int) $order->shipper_id : null,
            'shipper_name' => (string) ($order->shipper?->name ?? ''),
            'default_shipper_id' => $order->customer?->default_shipper_id ? (int) $order->customer->default_shipper_id : null,
            'default_shipper_name' => (string) ($order->customer?->defaultShipper?->name ?? ''),
            'daily_sequence' => $order->daily_sequence ? (int) $order->daily_sequence : null,
            'delivery_time' => (string) ($order->delivery_time ?: $order->customer?->delivery_time ?: ''),
            'delivery_date' => optional($order->delivery_date)->toDateString(),
            'created_date' => optional($order->created_at)->toDateString(),
            'available_shippers' => $shippers,
            'updated_at' => optional($order->updated_at)->format('d/m H:i'),
            'items' => $order->items->map(function ($item) {
                $variant = $item->variant;
                $productName = $item->product?->name ?: $variant?->product?->name ?: 'Sản phẩm';
                $variantName = $variant?->name ?: $variant?->variant_name ?: $variant?->sku;
                $quantity = (float) ($item->quantity ?? 0);
                $price = (float) ($item->price ?? 0);

                return [
                    'name' => trim($productName . ($variantName ? ' - ' . $variantName : '')),
                    'sku' => (string) ($variant?->sku ?? ''),
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => (float) ($item->total ?? ($quantity * $price)),
                ];
            })->values(),
        ];
    }

    private function mobileOrderDetailPayload(Order $order): array
    {
        return [
            'id' => (int) $order->id,
            'code' => (string) ($order->code ?: ('#' . $order->id)),
            'status' => (string) $order->status,
            'customer' => (string) ($order->customer?->name ?? '—'),
            'phone' => (string) ($order->customer?->phone ?? '—'),
            'address' => (string) ($order->customer?->address ?? '—'),
            'total' => (float) ($order->total ?? 0),
            'amount_due' => (float) ($order->amount_due ?? 0),
            'collected_amount' => (float) ($order->collected_amount ?? 0),
            'delivered_at' => optional($order->delivered_at)->format('d/m/Y H:i'),
            'updated_at' => optional($order->updated_at)->format('d/m/Y H:i'),
            'items' => $order->items->map(fn ($item) => [
                'name' => (string) ($item->variant?->name ?? $item->product?->name ?? 'Sản phẩm'),
                'sku' => (string) ($item->variant?->sku ?? '—'),
                'quantity' => (int) ($item->quantity ?? 0),
            ])->values(),
        ];
    }
}
