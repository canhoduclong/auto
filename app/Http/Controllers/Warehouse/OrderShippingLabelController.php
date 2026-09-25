<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderShippingLabelController extends Controller
{
    public function show(Request $request, Order $order): View
    {
        $this->authorizeOrder($request, $order);

        return view('warehouse.orders.shipping-label', [
            'order' => $order,
            'label' => $order->shippingLabelData(),
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrder($request, $order);
        $data = $request->validate(['printed' => ['required', 'boolean']]);
        $order->shipping_label_printed_at = $data['printed'] ? now() : null;
        $order->saveQuietly();

        return redirect()->route('warehouse.orders.shipping-label', $order)
            ->with('shipping_label_updated', true);
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        $user = $request->user();
        abort_unless($user && ($user->hasRole('admin') || (
            $user->hasRole('warehouse') && $user->warehouse_id
            && (int) $user->warehouse_id === (int) $order->warehouse_id
        )), 403, 'Bạn không có quyền in đơn hàng của kho này.');

        $order->loadMissing(['customer.truckStation', 'truckStation']);
        abort_unless($order->shippingLabelData()['enabled'], 404);
    }
}
