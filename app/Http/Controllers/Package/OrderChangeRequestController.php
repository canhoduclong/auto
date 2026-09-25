<?php

namespace App\Http\Controllers\Package;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class OrderChangeRequestController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'response_status' => ['nullable', 'in:sale_confirmed,sale_rejected'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $user = $request->user();
        $warehouseId = $user?->warehouse_id ? (int) $user->warehouse_id : null;
        $responseStatus = (string) $request->input('response_status', '');
        $search = trim((string) $request->input('search', ''));

        $ordersQuery = Order::query()
            ->with([
                'customer',
                'user',
                'warehouse',
                'warehouseAdjustmentRequester',
                'warehouseAdjustmentConfirmer',
                'warehouseAdjustmentRejecter',
                'histories.user.warehouse',
                'items.product.avatar.media',
                'items.variant.product',
                'items.variant.avatar.media',
            ])
            ->where(function (Builder $query) {
                $query->whereNull('is_return_order')->orWhere('is_return_order', false);
            })
            ->whereIn('warehouse_adjustment_status', [
                Order::WAREHOUSE_ADJUSTMENT_STATUS_SALE_CONFIRMED,
                Order::WAREHOUSE_ADJUSTMENT_STATUS_SALE_REJECTED,
            ]);

        if (!$user?->hasRole('admin')) {
            $warehouseId
                ? $ordersQuery->where('warehouse_id', $warehouseId)
                : $ordersQuery->whereRaw('1 = 0');
        }

        if (in_array($responseStatus, [
            Order::WAREHOUSE_ADJUSTMENT_STATUS_SALE_CONFIRMED,
            Order::WAREHOUSE_ADJUSTMENT_STATUS_SALE_REJECTED,
        ], true)) {
            $ordersQuery->where('warehouse_adjustment_status', $responseStatus);
        }

        if ($request->filled('from_date')) {
            $ordersQuery->where(function (Builder $query) use ($request) {
                $date = $request->date('from_date')->startOfDay();
                $query->where('warehouse_adjustment_confirmed_at', '>=', $date)
                    ->orWhere('warehouse_adjustment_rejected_at', '>=', $date);
            });
        }

        if ($request->filled('to_date')) {
            $ordersQuery->where(function (Builder $query) use ($request) {
                $date = $request->date('to_date')->endOfDay();
                $query->where('warehouse_adjustment_confirmed_at', '<=', $date)
                    ->orWhere('warehouse_adjustment_rejected_at', '<=', $date);
            });
        }

        if ($search !== '') {
            $ordersQuery->where(function (Builder $query) use ($search) {
                $query->where('code', 'like', '%' . $search . '%')
                    ->orWhereHas('customer', fn (Builder $customerQuery) => $customerQuery->where('name', 'like', '%' . $search . '%'));
            });
        }

        $orders = $ordersQuery
            ->orderByRaw('COALESCE(warehouse_adjustment_confirmed_at, warehouse_adjustment_rejected_at) DESC')
            ->get();

        return view('warehouse.orders.index', [
            'orders' => $orders,
            'responseStatus' => $responseStatus,
            'search' => $search,
            'fromDate' => (string) $request->input('from_date', ''),
            'toDate' => (string) $request->input('to_date', ''),
            'activeTransfersByOrder' => [],
            'warehouses' => collect(),
            'shippers' => collect(),
            'orderRoutePrefix' => 'package',
            'packingInventoryRoute' => 'package.inventory',
            'ordersLayout' => 'layouts.package',
            'ordersPageTitle' => 'Phản hồi thay đổi đơn hàng',
            'ordersPageSubtitle' => 'Theo dõi yêu cầu thay đổi đã được Sale phản hồi',
            'orderCardReadonly' => true,
            'orderChangesMode' => true,
        ]);
    }
}
