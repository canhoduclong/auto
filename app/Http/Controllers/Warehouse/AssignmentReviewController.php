<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\ShipperDispatchHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AssignmentReviewController extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : Carbon::today()->toDateString();

        $quickDates = collect(range(0, 6))->map(function (int $offset): array {
            $date = Carbon::today()->subDays($offset)->toDateString();
            $dispatch = $this->latestDispatch($date);

            return [
                'date' => $date,
                'label' => Carbon::parse($date)->format('d/m'),
                'count' => $this->warehouseOrders(
                    $dispatch ? $this->dispatchOrderIds($dispatch) : [],
                    $date
                )->count(),
            ];
        });

        $dispatch = $this->latestDispatch($selectedDate);
        $routeMeta = $dispatch ? $this->dispatchOrderMeta($dispatch) : [];
        $orders = $this->warehouseOrders(
            $dispatch ? $this->dispatchOrderIds($dispatch) : [],
            $selectedDate
        );
        $printHistories = OrderHistory::query()
            ->with('user:id,name')
            ->whereIn('order_id', $orders->pluck('id'))
            ->where('action', 'warehouse_delivery_note_printed')
            ->latest('id')
            ->get()
            ->unique('order_id')
            ->keyBy('order_id');

        return view('warehouse.assignment-review.index', compact(
            'selectedDate', 'quickDates', 'dispatch', 'orders', 'routeMeta', 'printHistories'
        ));
    }

    public function print(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['integer', 'distinct', 'exists:orders,id'],
        ], [
            'order_ids.required' => 'Vui lòng chọn ít nhất một phiếu để in.',
            'order_ids.min' => 'Vui lòng chọn ít nhất một phiếu để in.',
        ]);

        $selectedDate = Carbon::parse($validated['date'])->toDateString();
        $dispatch = $this->latestDispatch($selectedDate);
        $allowedOrders = $this->warehouseOrders(
            $dispatch ? $this->dispatchOrderIds($dispatch) : [],
            $selectedDate
        );
        $requestedIds = collect($validated['order_ids'])->map(fn ($id) => (int) $id)->values();
        abort_if(
            $requestedIds->diff($allowedOrders->pluck('id')->map(fn ($id) => (int) $id))->isNotEmpty(),
            403,
            'Có phiếu không thuộc kho của bạn hoặc không thuộc lộ trình ngày đã chọn.'
        );
        $ordersById = $allowedOrders->keyBy('id');
        $orders = $requestedIds->map(fn (int $id) => $ordersById->get($id))->filter()->values();

        DB::transaction(function () use ($orders): void {
            foreach ($orders as $order) {
                OrderHistory::create([
                    'order_id' => $order->id,
                    'action' => 'warehouse_delivery_note_printed',
                    'user_id' => Auth::id(),
                    'role' => 'warehouse',
                    'status_before' => $order->status,
                    'status_after' => $order->status,
                    'note' => 'Kho đã in phiếu giao hàng từ trang Review & In ấn.',
                ]);
            }
        });

        return view('shipper.assignment-documents-print', compact('orders', 'selectedDate'));
    }

    private function latestDispatch(string $date): ?ShipperDispatchHistory
    {
        return ShipperDispatchHistory::query()
            ->whereDate('schedule_date', $date)
            ->whereNull('revoked_at')
            ->latest('version')
            ->latest('id')
            ->first();
    }

    private function dispatchOrderIds(ShipperDispatchHistory $dispatch): array
    {
        return collect($dispatch->route_plan ?? [])
            ->flatMap(fn ($plan) => collect($plan['routes'] ?? [])->flatMap(fn ($route) => $route['orders'] ?? []))
            ->pluck('order_id')
            ->filter(fn ($id) => (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function warehouseOrders(array $orderIds, string $date): Collection
    {
        $user = Auth::user();
        $warehouseId = (int) (Auth::user()?->warehouse_id ?? 0);

        return Order::query()
            ->with(['customer', 'shipper:id,name,phone', 'user:id,name', 'warehouse:id,name', 'items.product', 'items.variant.product'])
            ->where(function ($query) use ($orderIds, $date) {
                $query->whereDate('delivery_date', $date);
                if ($orderIds !== []) {
                    $query->orWhereIn('id', $orderIds);
                }
            })
            ->whereNull('trash_at')
            ->whereNotIn('status', [Order::STATUS_CANCELLED, 'canceled'])
            ->where(function ($query): void {
                // Include legacy orders already beyond packing even when an
                // old record is missing its packing-completion history.
                $query->whereIn('status', [
                    Order::STATUS_PACKED,
                    Order::STATUS_READY_TO_SHIP,
                    Order::STATUS_DELIVERING,
                    Order::STATUS_IN_DELIVERY,
                    Order::STATUS_SHIPPING,
                    'picked_up',
                    Order::STATUS_DELIVERED,
                    Order::STATUS_COMPLETED,
                    Order::STATUS_RETURNING,
                    Order::STATUS_RETURNED_COMPLETED,
                    Order::STATUS_RETURNED,
                ])->orWhereHas('histories', fn ($history) => $history->whereIn('action', [
                    'complete_packing',
                    'warehouse_complete_packing',
                ]));
            })
            ->when(
                ! $user?->hasRole('admin'),
                fn ($query) => $warehouseId > 0
                    ? $query->where('warehouse_id', $warehouseId)
                    : $query->whereRaw('1 = 0')
            )
            ->get()
            ->sortBy(function (Order $order) use ($orderIds) {
                $routePosition = array_search((int) $order->id, $orderIds, true);

                return $routePosition === false
                    ? PHP_INT_MAX - (int) $order->id
                    : $routePosition;
            })
            ->values();
    }

    private function dispatchOrderMeta(ShipperDispatchHistory $dispatch): array
    {
        $meta = [];
        foreach ($dispatch->route_plan ?? [] as $plan) {
            foreach ($plan['routes'] ?? [] as $routeIndex => $route) {
                foreach ($route['orders'] ?? [] as $orderIndex => $entry) {
                    $orderId = (int) ($entry['order_id'] ?? 0);
                    if ($orderId > 0 && ! isset($meta[$orderId])) {
                        $meta[$orderId] = [
                            'shipper_name' => $plan['shipper_name'] ?? null,
                            'route_name' => $route['name'] ?? 'Chuyến '.($routeIndex + 1),
                            'sequence' => $orderIndex + 1,
                        ];
                    }
                }
            }
        }

        return $meta;
    }
}
