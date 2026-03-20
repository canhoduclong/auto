<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ShipperDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:shipper,admin']);
    }

    public function index()
    {
        $userId = Auth::id();
        $today  = Carbon::today();

        $stats = [
            'today_total'    => Order::where('shipper_id', $userId)->whereDate('created_at', $today)->count(),
            'delivering'     => Order::where('shipper_id', $userId)->where('status', Order::STATUS_DELIVERING)->count(),
            'delivered_today'=> Order::where('shipper_id', $userId)
                                    ->where('status', 'delivered')
                                    ->whereDate('delivered_at', $today)->count(),
            'returning'      => Order::where('shipper_id', $userId)->where('status', Order::STATUS_RETURNING)->count(),
            'cod_today'      => Order::where('shipper_id', $userId)
                                    ->where('status', 'delivered')
                                    ->whereDate('delivered_at', $today)
                                    ->sum('collected_amount'),
            'available'      => Order::where('status', Order::STATUS_READY_TO_SHIP)->whereNull('shipper_id')->count(),
        ];

        return view('shipper.dashboard', compact('stats'));
    }

    /**
     * Orders ready to be picked up by a shipper.
     */
    public function available()
    {
        $orders = Order::with(['customer.addresses', 'items'])
            ->where('status', Order::STATUS_READY_TO_SHIP)
            ->whereNull('shipper_id')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('shipper.available', compact('orders'));
    }

    /**
     * Accept an order: packed_waiting_pickup → delivering (with concurrency lock)
     */
    public function accept(Order $order)
    {
        $accepted = DB::transaction(function () use ($order) {
            $fresh = Order::where('id', $order->id)
                ->where('status', Order::STATUS_READY_TO_SHIP)
                ->whereNull('shipper_id')
                ->lockForUpdate()
                ->first();

            if (!$fresh) {
                return false;
            }

            $fresh->update([
                'shipper_id' => Auth::id(),
                'status'     => Order::STATUS_DELIVERING,
            ]);

            OrderHistory::create([
                'order_id'      => $fresh->id,
                'action'        => 'shipper_accepted',
                'user_id'       => Auth::id(),
                'role'          => 'shipper',
                'status_before' => Order::STATUS_READY_TO_SHIP,
                'status_after'  => Order::STATUS_DELIVERING,
                'note'          => 'Shipper nhận đơn để giao',
            ]);

            return true;
        });

        if (!$accepted) {
            return back()->with('error', 'Đơn hàng này đã được shipper khác nhận hoặc không còn khả dụng.');
        }

        return redirect()->route('shipper.my-orders')
            ->with('success', 'Đã nhận đơn #' . $order->code . ' thành công!');
    }

    /**
     * My delivering orders.
     */
    public function myOrders()
    {
        $orders = Order::with(['customer', 'items'])
            ->where('shipper_id', Auth::id())
            ->whereIn('status', [Order::STATUS_DELIVERING, Order::STATUS_COMPLETED])
            ->orderBy('created_at', 'asc')
            ->get();

        return view('shipper.delivering', compact('orders'));
    }

    /**
     * Show delivery confirmation form.
     */
    public function deliveredForm(Order $order)
    {
        $this->authorizeShipper($order);
        abort_if($order->status !== Order::STATUS_DELIVERING, 403, 'Đơn không đang giao.');
        return view('shipper.deliver-form', compact('order'));
    }

    /**
     * Confirm delivery: delivering → delivered
     */
    public function markDelivered(Request $request, Order $order)
    {
        $this->authorizeShipper($order);
        abort_if($order->status !== Order::STATUS_DELIVERING, 422, 'Đơn không đang giao.');

        $request->validate([
            'collected_amount' => 'required|numeric|min:0',
            'payment_method'   => 'required|in:cash,transfer',
            'proof_image'      => 'required|image|max:5120',
        ]);

        $imagePath = $request->file('proof_image')->store('order-proofs', 'public');

        $order->update([
            'status'           => 'delivered',
            'collected_amount' => $request->collected_amount,
            'delivered_at'     => now(),
            'proof_images'     => [$imagePath],
        ]);

        OrderHistory::create([
            'order_id'      => $order->id,
            'action'        => 'delivered',
            'user_id'       => Auth::id(),
            'role'          => 'shipper',
            'status_before' => Order::STATUS_DELIVERING,
            'status_after'  => 'delivered',
            'note'          => 'Giao hàng thành công. Đã thu: ' . number_format($request->collected_amount) . 'đ – ' . ($request->payment_method === 'cash' ? 'Tiền mặt' : 'Chuyển khoản'),
        ]);

        return redirect()->route('shipper.my-orders')->with('success', 'Xác nhận giao hàng thành công!');
    }

    /**
     * Show return form.
     */
    public function returnForm(Order $order)
    {
        $this->authorizeShipper($order);
        abort_if($order->status !== Order::STATUS_DELIVERING, 403, 'Đơn không đang giao.');
        return view('shipper.return-form', compact('order'));
    }

    /**
     * Submit return: delivering → returning
     */
    public function storeReturn(Request $request, Order $order)
    {
        $this->authorizeShipper($order);
        abort_if($order->status !== Order::STATUS_DELIVERING, 422, 'Đơn không đang giao.');

        $request->validate([
            'return_reason' => 'required|string',
            'return_note'   => 'nullable|string|max:500',
            'return_image'  => 'required|image|max:5120',
        ]);

        $imagePath = $request->file('return_image')->store('order-returns-proof', 'public');

        $order->update([
            'status'        => Order::STATUS_RETURNING,
            'return_reason' => $request->return_reason,
            'shipper_note'  => $request->return_note,
            'proof_images'  => [$imagePath],
        ]);

        OrderHistory::create([
            'order_id'      => $order->id,
            'action'        => 'return_request',
            'user_id'       => Auth::id(),
            'role'          => 'shipper',
            'status_before' => Order::STATUS_DELIVERING,
            'status_after'  => Order::STATUS_RETURNING,
            'note'          => 'Shipper gửi trả hàng: ' . $request->return_reason,
        ]);

        return redirect()->route('shipper.my-orders')->with('success', 'Đã gửi yêu cầu trả hàng về kho.');
    }

    /**
     * Delivery history.
     */
    public function history()
    {
        $orders = Order::with('customer')
            ->where('shipper_id', Auth::id())
            ->whereIn('status', ['delivered', Order::STATUS_RETURNING, Order::STATUS_RETURNED_COMPLETED, 'completed'])
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('shipper.history', compact('orders'));
    }

    protected function authorizeShipper(Order $order): void
    {
        if ($order->shipper_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(403, 'Bạn không có quyền thực hiện hành động này.');
        }
    }
}
