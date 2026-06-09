<?php

namespace App\Http\Controllers\Package;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderPackingController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $warehouseId = $user?->warehouse_id ? (int) $user->warehouse_id : null;
        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : now()->toDateString();
        $status = (string) $request->input('status', '');

        $query = Order::with([
            'customer',
            'user',
            'warehouse',
            'histories.user',
            'items.product.avatar.media',
            'items.variant.product',
            'items.variant.avatar.media',
        ])
            ->where(function ($orderQuery) {
                $orderQuery->whereNull('is_return_order')->orWhere('is_return_order', false);
            })
            ->whereDate('created_at', $selectedDate);

        if ($warehouseId) {
            $query->where(function ($warehouseQuery) use ($warehouseId) {
                $warehouseQuery->where('warehouse_id', $warehouseId)
                    ->orWhereNull('warehouse_id');
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $orders = $query
            ->orderByRaw('CASE WHEN daily_sequence IS NULL THEN 1 ELSE 0 END')
            ->orderBy('daily_sequence')
            ->orderBy('created_at')
            ->get();

        return view('package.orders', compact('orders', 'selectedDate', 'status'));
    }

    public function startPacking(Request $request, Order $order)
    {
        $this->authorizePackageOrder($order);

        if (!$order->created_at?->isToday()) {
            return back()->with('error', 'Chỉ được xử lý đơn có ngày hôm nay.');
        }
        if (!in_array($order->status, ['approved', Order::STATUS_READY_TO_PACK], true)) {
            return back()->with('error', 'Đơn không còn ở trạng thái chờ đóng gói.');
        }
        if (in_array($order->warehouse_adjustment_status, [
            Order::WAREHOUSE_ADJUSTMENT_STATUS_PENDING_SALE_CONFIRMATION,
            Order::WAREHOUSE_ADJUSTMENT_STATUS_SALE_REJECTED,
        ], true)) {
            return back()->with('error', 'Đơn đang có yêu cầu điều chỉnh chưa hoàn tất.');
        }

        $shortages = $this->stockShortages($order);
        if ($shortages->isNotEmpty()) {
            return back()->with('error', 'Không đủ tồn kho để bắt đầu đóng hàng.');
        }

        $before = $order->status;
        $payload = ['status' => Order::STATUS_PACKING];
        if (!$order->warehouse_id && Auth::user()?->warehouse_id) {
            $payload['warehouse_id'] = Auth::user()->warehouse_id;
        }
        $order->update($payload);
        $this->history($order, 'package_start_packing', $before, Order::STATUS_PACKING, 'Package bắt đầu đóng hàng');

        return back()->with('success', 'Đã bắt đầu đóng đơn #' . $order->code . '.');
    }

    public function updateLogistics(Request $request, Order $order)
    {
        $this->authorizePackageOrder($order);
        if (!$order->created_at?->isToday()) {
            return back()->with('error', 'Chỉ được xử lý đơn có ngày hôm nay.');
        }
        if ($order->status !== Order::STATUS_PACKING) {
            return back()->with('error', 'Đơn đã khóa hoặc không ở trạng thái đang đóng hàng.');
        }

        $validated = $request->validate([
            'actual_weight' => ['required', 'numeric', 'min:0'],
            'shipping_fee' => ['required', 'numeric', 'min:0'],
            'foam_box_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $order->update([
            'actual_weight' => $validated['actual_weight'],
            'shipping_fee' => $validated['shipping_fee'],
            'charge_shipping_fee' => (float) $validated['shipping_fee'] > 0,
            'foam_box_price' => $validated['foam_box_price'] ?? 0,
            'charge_foam_box_fee' => (float) ($validated['foam_box_price'] ?? 0) > 0,
        ]);

        return back()->with('success', 'Đã cập nhật thông tin đóng hàng cho đơn #' . $order->code . '.');
    }

    public function completePacking(Request $request, Order $order)
    {
        $this->authorizePackageOrder($order);
        if (!$order->created_at?->isToday()) {
            return back()->with('error', 'Chỉ được xử lý đơn có ngày hôm nay.');
        }
        if ($order->status !== Order::STATUS_PACKING) {
            return back()->with('error', 'Đơn đã khóa hoặc không ở trạng thái đang đóng hàng.');
        }
        if (in_array($order->warehouse_adjustment_status, [
            Order::WAREHOUSE_ADJUSTMENT_STATUS_PENDING_SALE_CONFIRMATION,
            Order::WAREHOUSE_ADJUSTMENT_STATUS_SALE_REJECTED,
        ], true)) {
            return back()->with('error', 'Đơn đang có yêu cầu điều chỉnh chưa hoàn tất.');
        }
        if ($order->actual_weight === null || $order->shipping_fee === null) {
            return back()->with('error', 'Vui lòng cập nhật Kg thực tế và phí ship trước khi hoàn tất.');
        }

        $order->update(['status' => Order::STATUS_READY_TO_SHIP]);
        $this->history(
            $order,
            'package_complete_packing',
            Order::STATUS_PACKING,
            Order::STATUS_READY_TO_SHIP,
            'Package hoàn tất đóng hàng; đơn được khóa không thể mở lại'
        );

        return back()->with('success', 'Đơn #' . $order->code . ' đã đóng xong và được khóa.');
    }

    public function show(Order $order)
    {
        $this->authorizePackageOrder($order);

        return redirect()->route('package.orders', ['date' => $order->created_at?->toDateString()]);
    }

    private function authorizePackageOrder(Order $order): void
    {
        $warehouseId = Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        if ($warehouseId && $order->warehouse_id && (int) $order->warehouse_id !== $warehouseId) {
            abort(403, 'Đơn hàng thuộc kho khác.');
        }
    }

    private function stockShortages(Order $order)
    {
        $warehouseId = Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        $required = $order->items
            ->groupBy('product_variant_id')
            ->map(fn ($items) => (float) $items->sum('quantity'))
            ->filter(fn ($quantity, $variantId) => (int) $variantId > 0);

        if ($required->isEmpty()) {
            return collect();
        }

        $available = Inventory::query()
            ->whereIn('product_variant_id', $required->keys())
            ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
            ->get()
            ->groupBy('product_variant_id')
            ->map(fn ($inventories) => (float) $inventories->sum('quantity'));

        return $required->filter(fn ($quantity, $variantId) => ($available[$variantId] ?? 0) < $quantity);
    }

    private function history(Order $order, string $action, string $before, string $after, string $note): void
    {
        OrderHistory::create([
            'order_id' => $order->id,
            'action' => $action,
            'user_id' => Auth::id(),
            'role' => 'package',
            'status_before' => $before,
            'status_after' => $after,
            'note' => $note,
        ]);
    }
}
