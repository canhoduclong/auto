<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderHistory;
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
            'data' => [
                'id' => (int) $order->id,
                'code' => (string) ($order->code ?: ('#' . $order->id)),
                'status' => (string) $order->status,
                'customer' => (string) ($order->customer?->name ?? '—'),
                'phone' => (string) ($order->customer?->phone ?? '—'),
                'address' => (string) ($order->customer?->address ?? '—'),
                'items' => $order->items->map(fn ($item) => [
                    'name' => (string) ($item->variant?->name ?? $item->product?->name ?? 'Sản phẩm'),
                    'sku' => (string) ($item->variant?->sku ?? '—'),
                    'quantity' => (int) ($item->quantity ?? 0),
                ])->values(),
            ],
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

        return response()->json(['ok' => true, 'message' => 'Đã xác nhận giao hàng thành công.']);
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

    private function authorizeOrder(Request $request, Order $order): void
    {
        $user = $request->user();

        if ((int) $order->shipper_id !== (int) $user->id && !$user->hasRole('admin')) {
            abort(403, 'Bạn không có quyền thao tác đơn này.');
        }
    }
}
