<?php

namespace App\Http\Controllers\Package;

use App\Http\Controllers\Controller;
use App\Http\Controllers\WarehouseDashboardController;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderPackingController extends Controller
{
    public function __construct(private WarehouseDashboardController $warehouseOrders)
    {
    }

    public function index(Request $request)
    {
        return $this->warehouseOrders->orders($request);
    }

    public function startPacking(Request $request, Order $order)
    {
        return $this->warehouseOrders->startPacking($request, $order);
    }

    public function updateLogistics(Request $request, Order $order)
    {
        return $this->warehouseOrders->updateLogistics($request, $order);
    }

    public function completePacking(Request $request, Order $order)
    {
        return $this->warehouseOrders->completePacking($request, $order);
    }

    public function requestAdjustment(Request $request, Order $order)
    {
        return $this->warehouseOrders->requestAdjustment($request, $order);
    }

    public function createTransferRequest(Request $request, Order $order)
    {
        return $this->warehouseOrders->createTransferRequest($request, $order);
    }

    public function returnToReadyToPack(Request $request, Order $order)
    {
        return $this->warehouseOrders->returnToReadyToPack($request, $order);
    }

    public function reopenPacking(Request $request, Order $order)
    {
        return $this->warehouseOrders->reopenPacking($request, $order);
    }

    public function show(Order $order)
    {
        $this->authorizePackageOrder($order);

        return redirect()->route('package.orders', ['date' => $order->created_at?->toDateString()]);
    }

    private function authorizePackageOrder(Order $order): void
    {
        $user = request()->user();
        $managedWarehouseId = $user?->warehouse_id ? (int) $user->warehouse_id : null;
        $orderWarehouseId = $order->warehouse_id ? (int) $order->warehouse_id : null;

        if (!$user?->hasRole('admin') && $managedWarehouseId && $orderWarehouseId && $managedWarehouseId !== $orderWarehouseId) {
            abort(403, 'Đơn hàng thuộc kho khác.');
        }
    }

}
