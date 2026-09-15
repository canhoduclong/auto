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
                'count' => $dispatch ? $this->warehouseOrders($this->dispatchOrderIds($dispatch))->count() : 0,
            ];
        });

        $dispatch = $this->latestDispatch($selectedDate);
        $routeMeta = $dispatch ? $this->dispatchOrderMeta($dispatch) : [];
        $orders = $dispatch
            ? $this->warehouseOrders($this->dispatchOrderIds($dispatch))
            : collect();
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
        abort_unless($dispatch, 404, 'Ngày này chưa có lộ trình đã phát hành.');

        $allowedOrders = $this->warehouseOrders($this->dispatchOrderIds($dispatch));
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

        return view('warehouse.assignment-review.print', compact('orders', 'selectedDate'));
    }

    private function latestDispatch(string $date): ?ShipperDispatchHistory
    {
        return ShipperDispatchHistory::query()
            ->whereDate('schedule_date', $date)
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

    private function warehouseOrders(array $orderIds): Collection
    {
        $warehouseId = (int) (Auth::user()?->warehouse_id ?? 0);

        return Order::query()
            ->with(['customer', 'shipper:id,name,phone', 'user:id,name', 'warehouse:id,name', 'items.product', 'items.variant.product'])
            ->whereIn('id', $orderIds)
            ->when($warehouseId > 0, fn ($query) => $query->where('warehouse_id', $warehouseId))
            ->get()
            ->sortBy(fn (Order $order) => array_search((int) $order->id, $orderIds, true))
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
