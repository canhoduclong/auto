<?php
namespace App\Http\Controllers;
use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\CustomerShippingFeeHistory;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\OrderReturn;
use App\Models\ProductVariant;
use App\Models\ReturnItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Services\ShipperAssignmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ShipperDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:shipper,manager_shipper,admin']);
    }
    /**
     * Chuyển đơn sang kho khác (cập nhật warehouse_id)
     */
    public function transferToWarehouse(Request $request, Order $order)
    {
        $this->authorizeManagerShipper();

        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        $warehouseId = (int) $request->input('warehouse_id');
        $warehouse = Warehouse::findOrFail($warehouseId);

        // Cập nhật kho đích cho đơn
        $order->update([
            'warehouse_id' => $warehouse->id,
        ]);

        // Ghi lịch sử
        OrderHistory::create([
            'order_id'      => $order->id,
            'action'        => 'transfer_to_warehouse',
            'user_id'       => Auth::id(),
            'role'          => 'manager_shipper',
            'status_before' => $order->status,
            'status_after'  => $order->status,
            'note'          => 'Chuyển tới kho: ' . $warehouse->name,
        ]);

        return back()->with('success', 'Đã chuyển đơn #' . $order->code . ' tới kho ' . $warehouse->name . ' thành công!');
    }
    private function assignmentOrderingDate(Request $request): string
    {
        return $request->filled('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : Carbon::today()->toDateString();
    }

    private function assignmentMutationResponse(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('success', $message);
    }

    private function constrainAvailableReadyOrder($query): void
    {
        $query->where('shipper_id', Auth::id())
            ->where(function ($statusQuery) {
                $statusQuery->where('status', Order::STATUS_READY_TO_SHIP)
                    ->orWhere(function ($returnQuery) {
                        $returnQuery->where('status', Order::STATUS_APPROVED)
                            ->where('is_return_order', true);
                    });
            });

        $this->constrainConfirmedDeliverySchedule($query);

        $this->constrainNoActiveWarehouseTransfer($query);
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

    private function reorderShipperDailySequences(int $shipperId, string $dateString): void
    {
        $orders = Order::query()
            ->where('shipper_id', $shipperId)
            ->whereIn('status', $this->assignmentStatuses())
            ->forDeliveryDate($dateString)
            ->orderByRaw('CASE WHEN daily_sequence IS NULL THEN 1 ELSE 0 END')
            ->orderBy('daily_sequence', 'asc')
            ->orderByRaw("CASE WHEN delivery_time IS NULL OR delivery_time = '' THEN 1 ELSE 0 END")
            ->orderBy('delivery_time', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->lockForUpdate()
            ->get();

        $sequence = 0;
        foreach ($orders as $order) {
            $sequence++;
            $order->update(['daily_sequence' => $sequence]);
        }
    }

    private function moveOrderWithinShipper(Order $order, int $direction, string $dateString): void
    {
        abort_if(!$order->shipper_id, 422, 'Đơn chưa được gán cho shipper nào.');
        abort_if(!in_array($order->status, $this->assignmentStatuses(), true), 422, 'Đơn chưa ở trạng thái có thể sắp xếp.');

        DB::transaction(function () use ($order, $direction, $dateString): void {
            $this->reorderShipperDailySequences((int) $order->shipper_id, $dateString);

            $orders = Order::query()
                ->where('shipper_id', $order->shipper_id)
                ->whereIn('status', $this->assignmentStatuses())
                ->forDeliveryDate($dateString)
                ->orderBy('daily_sequence', 'asc')
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get()
                ->values();

            $currentIndex = $orders->search(fn (Order $item) => (int) $item->id === (int) $order->id);
            if ($currentIndex === false) {
                return;
            }

            $targetIndex = $direction < 0 ? $currentIndex - 1 : $currentIndex + 1;
            if (!isset($orders[$targetIndex])) {
                return;
            }

            $movedOrder = $orders[$currentIndex];
            $neighborOrder = $orders[$targetIndex];
            [$orders[$currentIndex], $orders[$targetIndex]] = [$neighborOrder, $movedOrder];

            foreach ($orders as $index => $item) {
                $item->update(['daily_sequence' => $index + 1]);
            }
        });
    }

    // Di chuyển đơn lên trên trong danh sách shipper
    public function moveOrderUp(Request $request, Order $order)
    {
        $this->authorizeManagerShipper();

        $dateString = $this->assignmentOrderingDate($request);
        $this->moveOrderWithinShipper($order, -1, $dateString);
        return $this->assignmentMutationResponse($request, 'Đã đưa đơn #' . ($order->code ?: $order->id) . ' lên trên.');
    }

    // Di chuyển đơn xuống dưới trong danh sách shipper
    public function moveOrderDown(Request $request, Order $order)
    {
        $this->authorizeManagerShipper();

        $dateString = $this->assignmentOrderingDate($request);
        $this->moveOrderWithinShipper($order, 1, $dateString);
        return $this->assignmentMutationResponse($request, 'Đã đưa đơn #' . ($order->code ?: $order->id) . ' xuống dưới.');
    }

    public function moveOwnScheduleUp(Request $request, Order $order)
    {
        $currentUser = Auth::user();
        $canManageAny = $currentUser && ($currentUser->hasRole('manager_shipper') || $currentUser->hasRole('admin'));

        if (!$canManageAny && (int) ($order->shipper_id ?? 0) !== (int) Auth::id()) {
            abort(403, 'Đơn này không thuộc lịch trình của bạn.');
        }

        $dateString = $this->assignmentOrderingDate($request);
        $this->moveOrderWithinShipper($order, -1, $dateString);

        return back()->with('success', 'Đã đưa đơn #' . ($order->code ?: $order->id) . ' lên trên lịch trình.');
    }

    public function moveOwnScheduleDown(Request $request, Order $order)
    {
        $currentUser = Auth::user();
        $canManageAny = $currentUser && ($currentUser->hasRole('manager_shipper') || $currentUser->hasRole('admin'));

        if (!$canManageAny && (int) ($order->shipper_id ?? 0) !== (int) Auth::id()) {
            abort(403, 'Đơn này không thuộc lịch trình của bạn.');
        }

        $dateString = $this->assignmentOrderingDate($request);
        $this->moveOrderWithinShipper($order, 1, $dateString);

        return back()->with('success', 'Đã đưa đơn #' . ($order->code ?: $order->id) . ' xuống dưới lịch trình.');
    }

    public function index()
    {
        $userId = Auth::id();
        $today  = Carbon::today();
        $selectedDate = $today->toDateString();
        $deliveryScheduleOrders = $this->deliveryScheduleOrdersForShipper($userId, $selectedDate)->get();
        $deliveryScheduleSnapshot = $this->buildDeliveryScheduleSnapshot($deliveryScheduleOrders);
        $deliveryScheduleHash = $this->hashDeliveryScheduleSnapshot($deliveryScheduleSnapshot);
        $latestScheduleHistory = $this->latestDeliveryScheduleHistoryForShipperOnDate($userId, $selectedDate);
        $deliveryScheduleStatus = $this->deliveryScheduleStatus($latestScheduleHistory, $deliveryScheduleHash);

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
            'available'      => Order::where(function ($query) {
                                        $this->constrainAvailableReadyOrder($query);
                                    })
                                    ->count(),
        ];

        return view('shipper.dashboard', compact(
            'stats',
            'selectedDate',
            'deliveryScheduleOrders',
            'deliveryScheduleStatus'
        ));
    }

    /**
     * Orders ready to be picked up by a shipper.
     */
    public function available(Request $request)
    {
        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : Carbon::today()->toDateString();

        $today = Carbon::today();
        $startDate = $today->copy()->subDays(6)->toDateString();

        $dailyCounts = Order::query()
            ->selectRaw('delivery_date as day_key, COUNT(*) as total')
            ->where(function ($query) {
                $query->where(function ($readyQuery) {
                    $this->constrainAvailableReadyOrder($readyQuery);
                })->orWhere(function ($acceptedQuery) {
                    $acceptedQuery->where('status', Order::STATUS_DELIVERING)
                        ->where('shipper_id', Auth::id());
                    $this->constrainNoActiveWarehouseTransfer($acceptedQuery);
                });
            })
            ->whereDate('delivery_date', '>=', $startDate)
            ->whereDate('delivery_date', '<=', $today->toDateString())
            ->groupBy('day_key')
            ->pluck('total', 'day_key');

        $quickDates = collect(range(0, 6))->map(function ($offset) use ($today, $dailyCounts, $selectedDate) {
            $date = $today->copy()->subDays($offset);
            $dateKey = $date->toDateString();
            $count = (int) ($dailyCounts[$dateKey] ?? 0);

            return [
                'date' => $dateKey,
                'label' => $offset === 0 ? 'Hôm nay' : $date->format('d/m'),
                'count' => $count,
                'available' => $count > 0,
                'active' => $dateKey === $selectedDate,
            ];
        });

        $orders = Order::with(['customer.addresses', 'items.variant.product', 'warehouse', 'histories.user.warehouse'])
            ->where(function ($query) {
                $query->where(function ($readyQuery) {
                    $this->constrainAvailableReadyOrder($readyQuery);
                })->orWhere(function ($acceptedQuery) {
                    $acceptedQuery->where('status', Order::STATUS_DELIVERING)
                        ->where('shipper_id', Auth::id());
                    $this->constrainNoActiveWarehouseTransfer($acceptedQuery);
                });
            })
            ->forDeliveryDate($selectedDate)
            ->orderBy('created_at', 'asc')
            ->get();

        $receivedTransfersByOrder = WarehouseTransfer::query()
            ->with('targetWarehouse:id,name')
            ->whereIn('order_id', $orders->pluck('id')->all())
            ->where('status', WarehouseTransfer::STATUS_RECEIVED_COMPLETED)
            ->orderByDesc('id')
            ->get()
            ->unique('order_id')
            ->keyBy('order_id');

        $orders->each(function (Order $order) use ($receivedTransfersByOrder): void {
            $receivedTransfer = $receivedTransfersByOrder->get($order->id);
            if (!$receivedTransfer) {
                return;
            }

            $targetWarehouseName = $receivedTransfer->targetWarehouse?->name;
            if ($targetWarehouseName) {
                $order->setAttribute('resolved_pickup_warehouse_name', $targetWarehouseName);
            }
        });

        return view('shipper.available', compact('orders', 'selectedDate', 'quickDates'));
    }

    /**
     * Accept an order: packed_waiting_pickup → delivering (with concurrency lock)
     */
    public function accept(Order $order)
    {
        $accepted = DB::transaction(function () use ($order) {
            $fresh = Order::with('items')
                ->where('id', $order->id)
                ->where(function ($query) {
                    $this->constrainAvailableReadyOrder($query);
                })
                ->where(function ($query) {
                    $today = Carbon::today()->toDateString();

                    $query->whereDate('updated_at', $today)
                        ->orWhereDate('created_at', $today);
                })
                ->lockForUpdate()
                ->first();

            if (!$fresh) {
                return false;
            }

            $packingHistory = $fresh->histories()
                                    ->with('user')
                                    ->whereIn('action', ['complete_packing', 'warehouse_complete_packing'])
                                    ->latest('id')
                                    ->first();

            $latestReceivedTransfer = WarehouseTransfer::query()
                ->where('order_id', $fresh->id)
                ->where('status', WarehouseTransfer::STATUS_RECEIVED_COMPLETED)
                ->latest('id')
                ->first();

            $warehouseId = (int) ($latestReceivedTransfer?->target_warehouse_id
                ?: $fresh->warehouse_id
                ?: $packingHistory?->user?->warehouse_id
                ?: 0);

            $isReturnOrder = (bool) ($fresh->is_return_order ?? false)
                || (string) ($fresh->order_type ?? '') === 'order_return'
                || (string) ($fresh->workflow_code ?? '') === 'order_return';

            if (!$isReturnOrder && $warehouseId > 0 && (int) ($fresh->warehouse_id ?? 0) !== $warehouseId) {
                $fresh->update(['warehouse_id' => $warehouseId]);
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
                'status_before' => $isReturnOrder ? Order::STATUS_APPROVED : Order::STATUS_READY_TO_SHIP,
                'status_after'  => Order::STATUS_DELIVERING,
                'note'          => $isReturnOrder ? 'Shipper nhận đơn hoàn trả' : 'Shipper nhận đơn để giao',
            ]);

            if ($isReturnOrder) {
                return true;
            }

            if ($warehouseId <= 0) {
                throw new \RuntimeException('Không xác định được kho xuất cho đơn hàng này.');
            }

            $document = InventoryDocument::create([
                'type'          => 'export',
                'document_date' => now()->toDateString(),
                'warehouse_id'  => $warehouseId,
                'notes'         => 'Xuất kho cho đơn #' . $fresh->code,
                'shipping_fee'  => (float) ($fresh->shipping_fee ?? 0),
                'user_id'       => Auth::id(),
            ]);

            $fresh->loadMissing('items');

            foreach ($fresh->items as $item) {
                $document->items()->create([
                    'product_variant_id' => $item->product_variant_id,
                    'quantity'           => $item->quantity,
                    'unit_cost'          => $item->price ?? 0,
                ]);

                $this->deductStockForAcceptedOrderItem($fresh, $document, $item, $warehouseId);
            }

            return true;

        });
        
        if (!$accepted) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Đơn hàng này không còn khả dụng hoặc không thuộc ngày lên đón hôm nay.',
                ], 409);
            }

            return back()->with('error', 'Đơn hàng này không còn khả dụng hoặc không thuộc ngày lên đón hôm nay.');
        }

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Đã nhận đơn #' . $order->code . ' thành công!',
                'order' => [
                    'id' => $order->id,
                    'code' => $order->code,
                    'status' => Order::STATUS_DELIVERING,
                    'shipper_id' => Auth::id(),
                ],
            ]);
        }

        return redirect()->route('shipper.my-orders')
            ->with('success', 'Đã nhận đơn #' . $order->code . ' thành công!');
    }

    /**
     * My delivering orders.
     */
    public function myOrders()
    {
        $orders = Order::with([
                'customer.addresses',
                'items.variant.product',
                'warehouse',
                'histories.user.warehouse',
                'returnRecords' => fn ($query) => $query->where('return_scope', 'partial'),
            ])
            ->where('shipper_id', Auth::id())
            ->whereIn('status', [Order::STATUS_DELIVERING, Order::STATUS_COMPLETED])
            ->orderBy('created_at', 'asc')
            ->get();

        return view('shipper.delivering', compact('orders'));
    }

    public function warehouseTransfers(Request $request)
    {
        $user = Auth::user();
        $today = $request->filled('date') ? Carbon::parse($request->input('date'))->toDateString() : Carbon::today()->toDateString();

        $transfers = WarehouseTransfer::query()
            ->with([
                'order.customer.currentOwner',
                'order.user',
                'order.items.variant.product',
                'sourceWarehouse',
                'targetWarehouse',
                'shipper',
            ])
            ->when(!$user->hasRole('admin') && !$user->hasRole('manager_shipper'), function ($query) {
                $query->where('shipper_id', Auth::id());
            })
            ->whereIn('status', [
                WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP,
                WarehouseTransfer::STATUS_IN_TRANSIT,
                WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE,
                WarehouseTransfer::STATUS_RECEIVED_COMPLETED,
            ])
            ->whereHas('order', fn ($query) => $query->forDeliveryDate($today))
            ->orderByRaw("CASE WHEN status = 'pending_shipper_pickup' THEN 0 WHEN status = 'in_transit' THEN 1 WHEN status = 'delivered_waiting_receive' THEN 2 ELSE 3 END")
            ->orderByDesc('id')
            ->get()
            ->unique('order_id')
            ->sortBy(function (WarehouseTransfer $transfer): array {
                $order = $transfer->order;

                return [
                    $order?->delivery_time ?: $order?->customer?->delivery_time ?: '23:59:59',
                    (int) ($order?->daily_sequence ?? PHP_INT_MAX),
                ];
            })
            ->values();

        $transfers->each(function (WarehouseTransfer $transfer, int $index): void {
            $transfer->sequence_number = $index + 1;
        });

        return view('shipper.warehouse-transfers', compact('transfers', 'today'));
    }

    public function pickupWarehouseTransfer(Request $request, WarehouseTransfer $transfer)
    {
        $this->authorizeWarehouseTransferShipper($transfer);

        if ($transfer->status !== WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP) {
            return back()->with('error', 'Phiếu điều chuyển không ở trạng thái chờ shipper nhận hàng.');
        }

        $validated = $request->validate([
            'pickup_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $transfer->loadMissing(['order.items']);
        $order = $transfer->order;
        if (!$order) {
            return back()->with('error', 'Không tìm thấy đơn hàng của phiếu điều chuyển.');
        }

        try {
            DB::transaction(function () use ($transfer, $order, $validated): void {
                $document = InventoryDocument::create([
                    'type' => 'export',
                    'document_date' => now()->toDateString(),
                    'warehouse_id' => $transfer->source_warehouse_id,
                    'notes' => 'Xuat kho dieu chuyen don #' . $order->code . ' [WHT#' . $transfer->id . ']',
                    'shipping_fee' => 0,
                    'user_id' => Auth::id(),
                ]);

                foreach ($order->items as $item) {
                    $document->items()->create([
                        'product_variant_id' => $item->product_variant_id,
                        'quantity' => (int) ($item->quantity ?? 0),
                        'unit_cost' => (float) ($item->price ?? 0),
                    ]);

                    $this->deductStockForWarehouseTransferItem($order, $document, $item, (int) $transfer->source_warehouse_id);
                }

                $transfer->update([
                    'status' => WarehouseTransfer::STATUS_IN_TRANSIT,
                    'export_document_id' => $document->id,
                    'picked_up_by' => Auth::id(),
                    'picked_up_at' => now(),
                    'shipper_pickup_note' => trim((string) ($validated['pickup_note'] ?? '')) ?: null,
                ]);

                OrderHistory::create([
                    'order_id' => $order->id,
                    'action' => 'shipper_pickup_warehouse_transfer',
                    'user_id' => Auth::id(),
                    'role' => 'shipper',
                    'status_before' => $order->status,
                    'status_after' => $order->status,
                    'note' => 'Shipper da nhan hang dieu chuyen #' . $transfer->id,
                ]);
            });
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Đã nhận hàng điều chuyển và xuất kho nguồn thành công.');
    }

    /**
     * Shipper rollback warehouse transfer (delivered_waiting_receive)
     */
    public function rollbackWarehouseTransfer(Request $request, \App\Models\WarehouseTransfer $transfer)
    {
        if ($transfer->status !== \App\Models\WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE) {
            return back()->with('error', 'Phiếu điều chuyển không còn ở trạng thái chờ tiếp nhận.');
        }
        // Chỉ cho phép shipper phụ trách phiếu này rollback
        if ((int) $transfer->shipper_id !== (int) auth()->id()) {
            return back()->with('error', 'Bạn không có quyền hoàn lại phiếu điều chuyển này.');
        }
        $validated = $request->validate([
            'rollback_note' => ['nullable', 'string', 'max:1000'],
        ]);
        $transfer->loadMissing(['order', 'sourceWarehouse', 'targetWarehouse']);
        $order = $transfer->order;
        $reason = trim((string) ($validated['rollback_note'] ?? ''));
        $noteParts = [
            'Shipper hoàn lại trước khi kho nhận xác nhận.',
        ];
        if ($reason !== '') {
            $noteParts[] = 'Lý do: ' . $reason;
        }
        $transfer->update([
            'status' => \App\Models\WarehouseTransfer::STATUS_CANCELLED,
            'note' => implode(' | ', $noteParts),
        ]);
        if ($order) {
            \App\Models\OrderHistory::create([
                'order_id' => $order->id,
                'action' => 'warehouse_transfer_rolled_back_by_shipper',
                'user_id' => auth()->id(),
                'role' => 'shipper',
                'status_before' => $order->status,
                'status_after' => $order->status,
                'note' => 'Shipper hoàn lại phiếu điều chuyển #' . $transfer->id
                    . ' trước khi kho nhận xác nhận. Kho gửi: ' . ($transfer->sourceWarehouse?->name ?? 'N/A')
                    . '; Kho nhận: ' . ($transfer->targetWarehouse?->name ?? 'N/A')
                    . ($reason !== '' ? '; Lý do: ' . $reason : ''),
            ]);
        }
        return back()->with('success', 'Đã hoàn lại phiếu điều chuyển trước khi kho nhận xác nhận.');
    }

    public function deliverWarehouseTransfer(Request $request, WarehouseTransfer $transfer)
    {
        $this->authorizeWarehouseTransferShipper($transfer);

        if ($transfer->status !== WarehouseTransfer::STATUS_IN_TRANSIT) {
            return back()->with('error', 'Phiếu điều chuyển không ở trạng thái đang vận chuyển.');
        }

        $validated = $request->validate([
            'delivery_note' => ['nullable', 'string', 'max:1000'],
            'delivery_proof_image' => ['nullable', 'image', 'max:5120'],
        ]);

        $proofImagePath = $transfer->delivery_proof_image;
        if ($request->hasFile('delivery_proof_image')) {
            $proofImagePath = $request->file('delivery_proof_image')->store('shipper/warehouse-transfers', 'public');
        }

        $transfer->loadMissing('order');

        $transfer->update([
            'status' => WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE,
            'delivered_by' => Auth::id(),
            'delivered_at' => now(),
            'shipper_delivery_note' => trim((string) ($validated['delivery_note'] ?? '')) ?: null,
            'delivery_proof_image' => $proofImagePath,
        ]);

        if ($transfer->order) {
            OrderHistory::create([
                'order_id' => $transfer->order->id,
                'action' => 'shipper_deliver_warehouse_transfer',
                'user_id' => Auth::id(),
                'role' => 'shipper',
                'status_before' => $transfer->order->status,
                'status_after' => $transfer->order->status,
                'note' => 'Shipper da giao hang dieu chuyen #' . $transfer->id . ' cho kho nhan.',
            ]);
        }

        return back()->with('success', 'Đã cập nhật giao hàng thành công. Kho nhận có thể tiếp nhận hàng.');
    }

    public function bulkPickupWarehouseTransfers(Request $request)
    {
        $this->authorizeManagerShipper();

        $validated = $request->validate([
            'transfer_ids' => ['required', 'array', 'min:1'],
            'transfer_ids.*' => ['required', 'integer', 'exists:warehouse_transfers,id'],
            'pickup_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $transfers = WarehouseTransfer::query()
            ->with(['order.items'])
            ->whereIn('id', $validated['transfer_ids'])
            ->where('status', WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP)
            ->get();

        if ($transfers->isEmpty()) {
            return back()->with('error', 'Không có phiếu điều chuyển nào ở trạng thái chờ nhận hàng.');
        }

        $processed = 0;
        $errors = [];

        foreach ($transfers as $transfer) {
            try {
                DB::transaction(function () use ($transfer, $validated): void {
                    $order = $transfer->order;
                    if (!$order) {
                        throw new \RuntimeException('Không tìm thấy đơn hàng của phiếu #' . $transfer->id);
                    }

                    $document = InventoryDocument::create([
                        'type' => 'export',
                        'document_date' => now()->toDateString(),
                        'warehouse_id' => $transfer->source_warehouse_id,
                        'notes' => 'Xuat kho dieu chuyen don #' . $order->code . ' [WHT#' . $transfer->id . ']',
                        'shipping_fee' => 0,
                        'user_id' => Auth::id(),
                    ]);

                    foreach ($order->items as $item) {
                        $document->items()->create([
                            'product_variant_id' => $item->product_variant_id,
                            'quantity' => (int) ($item->quantity ?? 0),
                            'unit_cost' => (float) ($item->price ?? 0),
                        ]);
                        $this->deductStockForWarehouseTransferItem($order, $document, $item, (int) $transfer->source_warehouse_id);
                    }

                    $transfer->update([
                        'status' => WarehouseTransfer::STATUS_IN_TRANSIT,
                        'export_document_id' => $document->id,
                        'picked_up_by' => Auth::id(),
                        'picked_up_at' => now(),
                        'shipper_pickup_note' => trim((string) ($validated['pickup_note'] ?? '')) ?: null,
                    ]);

                    OrderHistory::create([
                        'order_id' => $order->id,
                        'action' => 'shipper_pickup_warehouse_transfer',
                        'user_id' => Auth::id(),
                        'role' => 'manager_shipper',
                        'status_before' => $order->status,
                        'status_after' => $order->status,
                        'note' => 'Shipper nhận hàng điều chuyển (bulk) #' . $transfer->id,
                    ]);
                });
                $processed++;
            } catch (\Throwable $e) {
                $errors[] = 'Phiếu #' . $transfer->id . ': ' . $e->getMessage();
            }
        }

        $message = "Đã nhận hàng {$processed}/{$transfers->count()} phiếu điều chuyển.";
        if (!empty($errors)) {
            return back()->with('warning', $message . ' Lỗi: ' . implode('; ', $errors));
        }
        return back()->with('success', $message);
    }

    public function bulkDeliverWarehouseTransfers(Request $request)
    {
        $this->authorizeManagerShipper();

        $validated = $request->validate([
            'transfer_ids' => ['required', 'array', 'min:1'],
            'transfer_ids.*' => ['required', 'integer', 'exists:warehouse_transfers,id'],
            'delivery_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $transfers = WarehouseTransfer::query()
            ->with(['order'])
            ->whereIn('id', $validated['transfer_ids'])
            ->where('status', WarehouseTransfer::STATUS_IN_TRANSIT)
            ->get();

        if ($transfers->isEmpty()) {
            return back()->with('error', 'Không có phiếu điều chuyển nào ở trạng thái đang vận chuyển.');
        }

        $processed = 0;
        $errors = [];

        foreach ($transfers as $transfer) {
            try {
                $transfer->update([
                    'status' => WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE,
                    'delivered_by' => Auth::id(),
                    'delivered_at' => now(),
                    'shipper_delivery_note' => trim((string) ($validated['delivery_note'] ?? '')) ?: null,
                ]);

                if ($transfer->order) {
                    OrderHistory::create([
                        'order_id' => $transfer->order->id,
                        'action' => 'shipper_deliver_warehouse_transfer',
                        'user_id' => Auth::id(),
                        'role' => 'manager_shipper',
                        'status_before' => $transfer->order->status,
                        'status_after' => $transfer->order->status,
                        'note' => 'Shipper giao hàng điều chuyển (bulk) #' . $transfer->id . ' cho kho nhận.',
                    ]);
                }
                $processed++;
            } catch (\Throwable $e) {
                $errors[] = 'Phiếu #' . $transfer->id . ': ' . $e->getMessage();
            }
        }

        $message = "Đã giao hàng {$processed}/{$transfers->count()} phiếu điều chuyển cho kho nhận.";
        if (!empty($errors)) {
            return back()->with('warning', $message . ' Lỗi: ' . implode('; ', $errors));
        }
        return back()->with('success', $message);
    }

    /**
     * Show delivery confirmation form.
     */
    public function deliveredForm(Order $order)
    {
        $this->authorizeShipper($order);
        abort_if($order->status !== Order::STATUS_DELIVERING, 403, 'Đơn không đang giao.');

        $order->load([
            'customer.addresses',
            'customer.truckStation',
            'customer.truckRoute',
            'items.variant.product',
            'returnRecords' => fn ($query) => $query->where('return_scope', 'partial'),
        ]);
        $warehouses = Warehouse::query()->orderBy('name')->get();
        $resumePaymentOnly = $order->returnRecords->isNotEmpty();

        return view('shipper.deliver-form', compact('order', 'warehouses', 'resumePaymentOnly'));
    }

    /**
     * Confirm delivery: delivering → delivered
     */
    public function markDelivered(Request $request, Order $order)
    {
        $this->authorizeShipper($order);
        abort_if($order->status !== Order::STATUS_DELIVERING, 422, 'Đơn không đang giao.');

        $resumePaymentOnly = $order->returnRecords()
            ->where('return_scope', 'partial')
            ->exists();

        if ($resumePaymentOnly) {
            $request->merge([
                'has_partial_return' => '0',
                'delivered_qty' => [],
                'partial_weight' => [],
            ]);
        }

        $order->loadMissing('customer.truckStation');
        $isTruckStationDelivery = (bool) ($order->customer?->use_truck_station ?? false)
            && !empty($order->customer?->truck_station_id);

        $validationRules = [
            'collected_amount'      => 'nullable|numeric|min:0',
            'proof_image'           => 'nullable|image|max:5120',
            'truck_station_receipt_image' => 'nullable|image|max:5120',
            'weight_image'          => 'nullable|image|max:5120',
            'actual_weight'         => 'nullable|array',
            'actual_weight.*'       => 'nullable|numeric|min:0',
            'delivered_qty'         => 'nullable|array',
            'delivered_qty.*'       => 'nullable|integer|min:0',
            'partial_weight'        => 'nullable|array',
            'partial_weight.*'      => 'nullable|numeric|min:0',
            'return_warehouse_id'   => 'required_if:has_partial_return,1|nullable|exists:warehouses,id',
            'partial_return_reason' => 'required_if:has_partial_return,1|nullable|string',
        ];

        $request->validate($validationRules);

        $collectedAmount = $request->filled('collected_amount')
            ? (float) $request->input('collected_amount', 0)
            : null;

        // Validate partial_weight against each item's max weight (qty × unit_weight)
        if ($request->filled('has_partial_return') && $request->input('has_partial_return') == '1') {
            $order->loadMissing('items');
            $partialWeightInput = $request->input('partial_weight', []);
            $actualWeightInput = $request->input('actual_weight', []);
            $weightErrors = [];
            foreach ($order->items as $item) {
                if (!array_key_exists($item->id, $partialWeightInput)) continue;
                $entered  = $partialWeightInput[$item->id];
                if ($entered === null || $entered === '') continue;

                $baseWeight = $item->packed_weight !== null
                    ? (float) $item->packed_weight
                    : (($item->actual_weight !== null && (float) $item->actual_weight > 0)
                        ? (float) $item->actual_weight
                        : (float) $item->effective_unit_weight * (int) $item->quantity);

                if (array_key_exists($item->id, $actualWeightInput)
                    && $actualWeightInput[$item->id] !== null
                    && $actualWeightInput[$item->id] !== ''
                ) {
                    $baseWeight = max(0, (float) $actualWeightInput[$item->id]);
                }

                $maxWeight = max(0, $baseWeight);
                if ((float) $entered > $maxWeight) {
                    $weightErrors["partial_weight.{$item->id}"] = "Khối lượng giao của '{$item->variant?->name}' tối đa {$maxWeight} kg (đã nhập " . (float)$entered . ' kg)';
                }
            }
            if (!empty($weightErrors)) {
                throw \Illuminate\Validation\ValidationException::withMessages($weightErrors);
            }
        }

        $proofImages = []; if ($request->hasFile('proof_image')) { $proofImages[] = $request->file('proof_image')->store('order-proofs', 'public'); }
        

        if ($isTruckStationDelivery && $request->hasFile('truck_station_receipt_image')) {
            $proofImages[] = $request->file('truck_station_receipt_image')->store('order-proofs/truck-station', 'public');
        }

        if ($request->hasFile('weight_image')) {
            $proofImages[] = $request->file('weight_image')->store('order-proofs', 'public');
        }

        $order->loadMissing('items');

        $actualWeights = $request->input('actual_weight', []);
        $deliveredQtys = $request->input('delivered_qty', []);
        $partialWeights = $request->input('partial_weight', []);
        $hasPartialReturn = (bool) $request->input('has_partial_return', false);
        $returnWarehouseId = $hasPartialReturn ? (int) $request->input('return_warehouse_id') : null;
        $partialReturnReason = $hasPartialReturn ? (string) $request->input('partial_return_reason', 'other') : null;

        $partialReturnNotes = [];
        $returnedItems = []; // [order_item_id => ['variant_id' => int, 'returned_qty' => int]]

        DB::transaction(function () use ($order, $actualWeights, $deliveredQtys, $partialWeights, $hasPartialReturn, $returnWarehouseId, $partialReturnReason, &$partialReturnNotes, &$returnedItems) {
            foreach ($order->items as $item) {
                $updates = [];

                // Cập nhật cân thực tế nếu shipper nhập
                if (array_key_exists($item->id, $actualWeights) && $actualWeights[$item->id] !== null && $actualWeights[$item->id] !== '') {
                    $updates['actual_weight'] = max(0, (float) $actualWeights[$item->id]);
                }

                // Xử lý giao 1 phần
                if ($hasPartialReturn && array_key_exists($item->id, $deliveredQtys)) {
                    $originalQty  = (int) $item->quantity;
                    $deliveredQty = max(0, min((int) $deliveredQtys[$item->id], $originalQty));
                    $returnedQty  = $originalQty - $deliveredQty;

                    if ($returnedQty > 0) {
                        $updates['quantity'] = $deliveredQty;

                        // Lưu cân thực tế phần thực giao (ghi đè nếu shipper nhập partial_weight)
                        if (array_key_exists($item->id, $partialWeights) && $partialWeights[$item->id] !== null && $partialWeights[$item->id] !== '') {
                            $updates['actual_weight'] = max(0, (float) $partialWeights[$item->id]);
                        }

                        $noteSegment = ($item->variant?->name ?? 'SP') . ': giao ' . $deliveredQty . '/' . $originalQty . ' (trả ' . $returnedQty . ')';
                        if ((bool) $item->effective_priced_by_kg) {
                            $baseWeight = $item->packed_weight !== null
                                ? (float) $item->packed_weight
                                : (($item->actual_weight !== null && (float) $item->actual_weight > 0)
                                    ? (float) $item->actual_weight
                                    : (float) $item->effective_unit_weight * $originalQty);
                            $deliveredWeight = array_key_exists($item->id, $partialWeights)
                                ? max(0, (float) ($partialWeights[$item->id] ?? 0))
                                : 0;
                            $returnedWeight = max(0, round($baseWeight - $deliveredWeight, 3));
                            $noteSegment .= ' ~ ' . $returnedWeight . ' kg';
                        }

                        $partialReturnNotes[] = $noteSegment;
                        $returnedItems[$item->id] = [
                            'variant_id'   => (int) $item->product_variant_id,
                            'returned_qty' => $returnedQty,
                        ];
                    }
                }

                if (!empty($updates)) {
                    $item->update($updates);
                }
            }

            // Tạo phiếu OrderReturn cho hàng chưa giao – kho sẽ xác nhận nhập sau
            if (!empty($returnedItems) && $returnWarehouseId) {
                $orderReturn = OrderReturn::create([
                    'order_id'     => $order->id,
                    'customer_id'  => $order->customer_id,
                    'warehouse_id' => $returnWarehouseId,
                    'created_by'   => Auth::id(),
                    'status'       => 'pending_warehouse',
                    'reason'       => $partialReturnReason,
                    'return_scope' => 'partial',
                    'note'         => 'Giao 1 phần: ' . implode('; ', $partialReturnNotes),
                ]);

                foreach ($returnedItems as $info) {
                    ReturnItem::create([
                        'order_return_id'    => $orderReturn->id,
                        'product_variant_id' => $info['variant_id'],
                        'quantity'           => $info['returned_qty'],
                        'condition'          => 'good',
                    ]);
                }
            }
        });

        $noteText = 'Giao hàng thành công.';
        if ($collectedAmount !== null && $collectedAmount > 0) {
            $noteText .= ' Đã thu: ' . number_format($collectedAmount) . 'đ.';
        } else {
            $noteText .= ' Chưa thu tiền / thanh toán sau.';
        }

        if ($isTruckStationDelivery) {
            $stationName = $order->customer?->truckStation?->name ?: 'trạm xe';
            $noteText .= ' | Đã bàn giao hàng tại nhà xe: ' . $stationName;
        }

        if (!empty($partialReturnNotes)) {
            $noteText .= ' | Giao 1 phần: ' . implode('; ', $partialReturnNotes) . ' – Phiếu hoàn trả đã tạo';
        }

        // Tính lại bill từ items sau khi đã cập nhật cân/SL
        $order->loadMissing('items');
        $newSubtotal = (float) $order->items->sum(function ($item) {
            if ((bool) $item->effective_priced_by_kg) {
                $weight = (float) ($item->actual_weight ?? ($item->effective_unit_weight * (int) $item->quantity));
                return $weight * (float) ($item->price ?? 0);
            }
            return (int) $item->quantity * (float) ($item->price ?? 0);
        });
        $shippingFee = (float) ($order->shipping_fee ?? 0);
        $foamBoxFee  = (float) (($order->charge_foam_box_fee ?? false) ? ($order->foam_box_price ?? 0) : 0);
        $newTotal    = $newSubtotal + $shippingFee + $foamBoxFee;

        $order->update([
            'status'           => 'delivered',
            'collected_amount' => $collectedAmount,
            'delivered_at'     => now(),
            'proof_images'     => $proofImages,
            'subtotal_amount'  => $newSubtotal,
            'total'            => $newTotal,
        ]);

        OrderHistory::create([
            'order_id'      => $order->id,
            'action'        => 'delivered',
            'user_id'       => Auth::id(),
            'role'          => 'shipper',
            'status_before' => Order::STATUS_DELIVERING,
            'status_after'  => 'delivered',
            'note'          => $noteText,
        ]);

        $successMsg = 'Xác nhận giao hàng thành công!';
        if (!empty($partialReturnNotes)) {
            $successMsg .= ' Đã tạo phiếu hoàn trả ' . count($returnedItems) . ' sản phẩm – chờ kho xác nhận nhập.';
        }

        return redirect()->route('shipper.my-orders')->with('success', $successMsg);
    }

    /**
     * Show return form.
     */
    public function returnForm(Order $order)
    {
        $this->authorizeShipper($order);
        abort_if($order->status !== Order::STATUS_DELIVERING, 403, 'Đơn không đang giao.');

        $order->load(['customer.addresses', 'items.variant.product', 'warehouse', 'histories.user.warehouse']);
        $warehouses = Warehouse::query()->orderBy('name')->get();
        $sourceWarehouse = $this->resolveOrderWarehouse($order);

        return view('shipper.return-form', compact('order', 'warehouses', 'sourceWarehouse'));
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
            'return_warehouse_id' => 'required|exists:warehouses,id',
        ]);

        $imagePath = $request->file('return_image')->store('order-returns-proof', 'public');
        $returnWarehouse = Warehouse::query()->findOrFail((int) $request->input('return_warehouse_id'));

        $shipperNote = trim((string) $request->input('return_note', ''));
        $warehouseNote = 'Kho trả về: ' . $returnWarehouse->name;
        $shipperNote = $shipperNote !== '' ? $shipperNote . ' | ' . $warehouseNote : $warehouseNote;

        $updateData = [
            'status'        => Order::STATUS_RETURNING,
            'return_reason' => $request->return_reason,
            'shipper_note'  => $shipperNote,
            'proof_images'  => [$imagePath],
        ];

        if (Schema::hasColumn('orders', 'return_warehouse_id')) {
            $updateData['return_warehouse_id'] = $returnWarehouse->id;
        }

        if (Schema::hasColumn('orders', 'warehouse_id')) {
            $updateData['warehouse_id'] = $returnWarehouse->id;
        }

        $order->update($updateData);

        $isReturnOrder = (bool) ($order->is_return_order ?? false)
            || (string) ($order->order_type ?? '') === 'order_return'
            || (string) ($order->workflow_code ?? '') === 'order_return';

        if ($isReturnOrder) {
            $order->loadMissing('items');

            $orderReturn = OrderReturn::firstOrCreate(
                [
                    'order_id' => $order->id,
                    'status' => 'pending_warehouse',
                ],
                [
                    'customer_id' => $order->customer_id,
                    'warehouse_id' => $returnWarehouse->id,
                    'created_by' => Auth::id(),
                    'reason' => $request->return_reason,
                    'return_scope' => 'full',
                    'refund_amount' => (float) ($order->total ?? 0),
                    'note' => trim($shipperNote . ' | Đơn hoàn trả từ sale'),
                ]
            );

            $orderReturn->update([
                'warehouse_id' => $returnWarehouse->id,
                'reason' => $request->return_reason,
                'refund_amount' => (float) ($order->total ?? 0),
            ]);

            if ($orderReturn->returnItems()->count() === 0) {
                foreach ($order->items as $item) {
                    ReturnItem::create([
                        'order_return_id' => $orderReturn->id,
                        'product_variant_id' => $item->product_variant_id,
                        'quantity' => (int) $item->quantity,
                        'condition' => 'good',
                    ]);
                }
            }
        }

        OrderHistory::create([
            'order_id'      => $order->id,
            'action'        => 'return_request',
            'user_id'       => Auth::id(),
            'role'          => 'shipper',
            'status_before' => Order::STATUS_DELIVERING,
            'status_after'  => Order::STATUS_RETURNING,
            'note'          => 'Shipper gửi trả hàng: ' . $request->return_reason . ' | ' . $warehouseNote,
        ]);

        return redirect()->route('shipper.my-orders')->with('success', 'Đã gửi yêu cầu trả hàng về kho.');
    }

    /**
     * Delivery history.
     */
    public function history(Request $request)
    {
        $date = $request->input('date');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $period = $request->input('period');

        // Default landing state: show today's deliveries when no filters are provided.
        if (empty($date) && empty($fromDate) && empty($toDate) && empty($period)) {
            $period = 'today';
        }

        if (empty($date) && !empty($period)) {
            $today = Carbon::today();

            if ($period === 'today') {
                $date = $today->toDateString();
            } elseif (in_array($period, ['7', '15', '30'], true)) {
                $fromDate = $today->copy()->subDays(((int) $period) - 1)->toDateString();
                $toDate = $today->toDateString();
            }
        }

        $query = Order::with('customer')
            ->where('shipper_id', Auth::id())
            ->whereIn('status', ['delivered', Order::STATUS_RETURNING, Order::STATUS_RETURNED_COMPLETED, 'completed'])
            ->when(!empty($date), function ($q) use ($date) {
                $q->whereDate('updated_at', $date);
            }, function ($q) use ($fromDate, $toDate) {
                if (!empty($fromDate)) {
                    $q->whereDate('updated_at', '>=', $fromDate);
                }

                if (!empty($toDate)) {
                    $q->whereDate('updated_at', '<=', $toDate);
                }
            });

        $summaryOrders = (clone $query)->get([
            'status',
            'shipping_fee',
            'charge_shipping_fee',
            'collected_amount',
        ]);

        $stats = [
            'total' => $summaryOrders->count(),
            'delivered' => $summaryOrders->where('status', 'delivered')->count(),
            'returning' => $summaryOrders->where('status', Order::STATUS_RETURNING)->count(),
            'completed' => $summaryOrders->where('status', 'completed')->count(),
            'total_ship_fee' => $summaryOrders->sum(function ($order) {
                return ($order->charge_shipping_fee ?? true) ? (float) ($order->shipping_fee ?? 0) : 0;
            }),
            'total_collected' => (float) $summaryOrders->sum(function ($order) {
                return (float) ($order->collected_amount ?? 0);
            }),
        ];

        $orders = $query
            ->orderByDesc('updated_at')
            ->paginate(20);

        $filters = [
            'date' => $date,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'period' => $period,
        ];

        return view('shipper.history', compact('orders', 'stats', 'filters'));
    }

    public function historyDetail(Order $order)
    {
        $this->authorizeShipper($order);

        abort_unless(
            in_array($order->status, ['delivered', Order::STATUS_RETURNING, Order::STATUS_RETURNED_COMPLETED, 'completed'], true),
            404,
            'Đơn hàng này chưa có trong lịch sử giao hàng.'
        );

        $order->load([
            'customer.addresses',
            'customer.truckStation',
            'items.variant.product',
            'warehouse',
            'returnWarehouse',
            'histories.user',
            'shipper',
        ]);

        $deliveryHistory = $order->histories
            ->where('action', 'delivered')
            ->sortByDesc('created_at')
            ->first();

        $latestReturn = OrderReturn::query()
            ->with(['warehouse', 'creator', 'returnItems.productVariant'])
            ->where('order_id', $order->id)
            ->latest('id')
            ->first();

        return view('shipper.history-detail', compact('order', 'deliveryHistory', 'latestReturn'));
    }

    protected function authorizeShipper(Order $order): void
    {
        if ($order->shipper_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(403, 'Bạn không có quyền thực hiện hành động này.');
        }
    }

    protected function resolveOrderWarehouse(Order $order): ?Warehouse
    {
        if ($order->warehouse) {
            return $order->warehouse;
        }

        $packingHistory = $order->histories
            ->whereIn('action', ['complete_packing', 'warehouse_complete_packing'])
            ->sortByDesc('id')
            ->first();

        return $packingHistory?->user?->warehouse;
    }

    private function deductStockForAcceptedOrderItem(Order $order, InventoryDocument $document, $item, int $warehouseId): void
    {
        $remaining = (int) $item->quantity;

        $reservations = InventoryReservation::query()
            ->where('order_item_id', $item->id)
            ->lockForUpdate()
            ->get();

        foreach ($reservations as $reservation) {
            if ($remaining <= 0) {
                break;
            }

            $inventory = Inventory::query()->lockForUpdate()->find($reservation->inventory_id);
            if (!$inventory) {
                continue;
            }

            $deductQty = min($remaining, (int) $reservation->quantity);
            if ($deductQty <= 0) {
                continue;
            }

            if ((int) $inventory->quantity < $deductQty || (int) $inventory->reserved_quantity < $deductQty) {
                throw new \RuntimeException('Dữ liệu tồn kho đặt trước không hợp lệ khi xuất kho.');
            }

            $inventory->quantity -= $deductQty;
            $inventory->reserved_quantity -= $deductQty;
            $inventory->save();

            InventoryMovement::create([
                'inventory_id'   => $inventory->id,
                'quantity'       => -$deductQty,
                'type'           => 'export',
                'reference_id'   => $document->id,
                'reference_type' => InventoryDocument::class,
                'user_id'        => Auth::id(),
            ]);

            $remaining -= $deductQty;
            $reservation->quantity -= $deductQty;

            if ((int) $reservation->quantity <= 0) {
                $reservation->delete();
            } else {
                $reservation->save();
            }
        }

        if ($remaining > 0) {
            $inventories = Inventory::query()
                ->where('product_variant_id', $item->product_variant_id)
                ->where('warehouse_id', $warehouseId)
                ->orderByDesc('quantity')
                ->lockForUpdate()
                ->get();

            foreach ($inventories as $inventory) {
                if ($remaining <= 0) {
                    break;
                }

                $available = (int) $inventory->quantity - (int) ($inventory->reserved_quantity ?? 0);
                if ($available <= 0) {
                    continue;
                }

                $deductQty = min($remaining, $available);

                $inventory->quantity -= $deductQty;
                $inventory->save();

                InventoryMovement::create([
                    'inventory_id'   => $inventory->id,
                    'quantity'       => -$deductQty,
                    'type'           => 'export',
                    'reference_id'   => $document->id,
                    'reference_type' => InventoryDocument::class,
                    'user_id'        => Auth::id(),
                ]);

                $remaining -= $deductQty;
            }
        }

        if ($remaining > 0) {
            throw new \RuntimeException('Không đủ tồn kho khả dụng để xuất cho đơn #' . ($order->code ?: $order->id));
        }

        $this->syncVariantStockFromInventories((int) $item->product_variant_id);
    }

    private function deductStockForWarehouseTransferItem(Order $order, InventoryDocument $document, $item, int $warehouseId): void
    {
        $remaining = (int) ($item->quantity ?? 0);

        $inventories = Inventory::query()
            ->where('product_variant_id', $item->product_variant_id)
            ->where('warehouse_id', $warehouseId)
            ->orderByDesc('quantity')
            ->lockForUpdate()
            ->get();

        foreach ($inventories as $inventory) {
            if ($remaining <= 0) {
                break;
            }

            $available = (int) $inventory->quantity - (int) ($inventory->reserved_quantity ?? 0);
            if ($available <= 0) {
                continue;
            }

            $deductQty = min($remaining, $available);

            $inventory->quantity -= $deductQty;
            $inventory->save();

            InventoryMovement::create([
                'inventory_id' => $inventory->id,
                'quantity' => -$deductQty,
                'type' => 'export',
                'reference_id' => $document->id,
                'reference_type' => InventoryDocument::class,
                'user_id' => Auth::id(),
            ]);

            $remaining -= $deductQty;
        }

        // Không kiểm tra tồn kho khả dụng ở flow shipper, giả định đã hợp lệ từ kho xuất.
        // if ($remaining > 0) {
        //     throw new \RuntimeException('Không đủ tồn kho khả dụng để điều chuyển cho đơn #' . ($order->code ?: $order->id));
        // }

        $this->syncVariantStockFromInventories((int) $item->product_variant_id);
    }

    private function authorizeWarehouseTransferShipper(WarehouseTransfer $transfer): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        if ($user->hasRole('admin') || $user->hasRole('manager_shipper')) {
            return;
        }

        if ((int) $transfer->shipper_id !== (int) $user->id) {
            abort(403, 'Bạn không có quyền thao tác phiếu điều chuyển này.');
        }
    }

    private function syncVariantStockFromInventories(int $variantId): void
    {
        $totalStock = (int) Inventory::query()
            ->where('product_variant_id', $variantId)
            ->sum('quantity');

        ProductVariant::query()
            ->where('id', $variantId)
            ->update(['stock' => $totalStock]);
    }

    /**
     * Manager Shipper: Manage order assignments to shippers
     */
    public function manageAssignments(Request $request)
    {
        $this->authorizeManagerShipper();

        // Get orders that can be planned for a shipper before packing finishes
        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : Carbon::tomorrow()->toDateString();

        $this->applyDefaultShipperAssignmentsForDate($selectedDate);

        $ordersQuery = Order::with(['customer.defaultShipper', 'customer.truckStation', 'customer.truckRoute.stops.station', 'items.variant', 'shipper'])
            ->whereIn('status', $this->assignmentStatuses())
            ->forDeliveryDate($selectedDate)
            ->orderByRaw("CASE WHEN delivery_time IS NULL OR delivery_time = '' THEN 1 ELSE 0 END")
            ->orderBy('delivery_time', 'asc')
            ->orderBy('created_at', 'asc');

        $assignedOrdersCount = (clone $ordersQuery)->whereNotNull('shipper_id')->count();
        $unassignedOrdersCount = (clone $ordersQuery)->whereNull('shipper_id')->count();
        $totalOrdersCount = $assignedOrdersCount + $unassignedOrdersCount;

        $unassignedOrders = (clone $ordersQuery)
            ->whereNull('shipper_id')
            ->paginate(15)
            ->withQueryString();

        $assignedOrders = (clone $ordersQuery)
            ->whereNotNull('shipper_id')
            ->get()
            ->groupBy('shipper_id')
            ->map(function ($orders) {
                return $orders
                    ->sortBy(function ($order) {
                        return [
                            $order->daily_sequence ?? PHP_INT_MAX,
                            $order->delivery_time ?: '23:59:59',
                            $order->created_at?->timestamp ?? 0,
                            $order->id,
                        ];
                    })
                    ->values();
            });

        // Get available shippers from team (users with shipper role)
        $shippers = \App\Models\User::query()
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['shipper', 'manager_shipper']);
            })
            ->where('id', '!=', Auth::id())
            ->orderBy('name')
            ->get();

        $shipperScheduleStatuses = $this->resolveShipperScheduleStatuses($selectedDate, $assignedOrders);
        $hasUnpublishedSchedules = collect($shipperScheduleStatuses)->contains('draft');

        $warehouses = Warehouse::query()->orderBy('name')->get();
        return view('shipper.manage-assignments', compact(
            'unassignedOrders',
            'assignedOrders',
            'shippers',
            'selectedDate',
            'assignedOrdersCount',
            'unassignedOrdersCount',
            'totalOrdersCount',
            'shipperScheduleStatuses',
            'hasUnpublishedSchedules',
            'warehouses'
        ));
    }

    private function applyDefaultShipperAssignmentsForDate(string $selectedDate): void
    {
        $orders = Order::with(['customer.defaultShipper'])
            ->whereNull('shipper_id')
            ->whereIn('status', $this->assignmentStatuses())
            ->forDeliveryDate($selectedDate)
            ->whereHas('customer', fn ($query) => $query->whereNotNull('default_shipper_id'))
            ->whereDoesntHave('histories', fn ($query) => $query->where('action', 'shipper_unassigned'))
            ->get();

        if ($orders->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($orders): void {
            foreach ($orders as $order) {
                $defaultShipperId = $order->customer?->default_shipper_id;
                if (!$defaultShipperId) {
                    continue;
                }

                $order->update(['shipper_id' => $defaultShipperId]);

                OrderHistory::create([
                    'order_id'      => $order->id,
                    'action'        => 'shipper_auto_assigned',
                    'user_id'       => Auth::id(),
                    'role'          => 'manager_shipper',
                    'status_before' => $order->status,
                    'status_after'  => $order->status,
                    'note'          => 'Tự động gán shipper cố định của khách hàng'
                        . ($order->customer?->defaultShipper?->name ? ': ' . $order->customer->defaultShipper->name : ''),
                ]);
            }
        });
    }

    private function resolveShipperScheduleStatuses(string $selectedDate, $assignedOrders): array
    {
        $statusByShipperId = [];

        foreach ($assignedOrders as $shipperId => $orders) {
            $snapshotHash = $this->hashDeliveryScheduleSnapshot($this->buildDeliveryScheduleSnapshot($orders));
            $latestHistory = $this->latestDeliveryScheduleHistoryForShipperOnDate((int) $shipperId, $selectedDate);
            $statusByShipperId[(int) $shipperId] = $this->deliveryScheduleStatus($latestHistory, $snapshotHash);
            if (in_array($statusByShipperId[(int) $shipperId], ['none', 'changed'], true)) {
                $statusByShipperId[(int) $shipperId] = 'draft';
            }
        }

        return $statusByShipperId;
    }

    private function buildDeliveryScheduleSnapshot($orders): array
    {
        return $orders->map(function ($order) {
            return [
                'order_id' => (int) $order->id,
                'daily_sequence' => $order->daily_sequence !== null ? (int) $order->daily_sequence : null,
                'delivery_date' => optional($order->delivery_date)->toDateString(),
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
            ->whereDate('orders.delivery_date', $selectedDate)
            ->whereIn('order_histories.action', ['schedule_created', 'schedule_confirmed', 'schedule_rejected'])
            ->orderByDesc('order_histories.created_at')
            ->orderByDesc('order_histories.id')
            ->select('order_histories.*')
            ->first();
    }

    private function deliveryScheduleOrdersForShipper(int $shipperId, string $selectedDate)
    {
        return Order::with(['customer', 'items.variant'])
            ->where('shipper_id', $shipperId)
            ->whereIn('status', $this->assignmentStatuses())
            ->forDeliveryDate($selectedDate)
            ->orderByRaw('CASE WHEN daily_sequence IS NULL THEN 1 ELSE 0 END')
            ->orderBy('daily_sequence', 'asc')
            ->orderBy('delivery_time', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc');
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

    /**
     * Assign order to specific shipper
     */
    public function assignSelectedOrder(Request $request, Order $order)
    {
        $validated = $request->validate([
            'shipper_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        return $this->assignOrder($request, $order, User::query()->findOrFail((int) $validated['shipper_id']));
    }

    public function assignOrder(Request $request, Order $order, User $shipper)
    {
        $this->authorizeManagerShipper();

        $request->validate([
            'notes' => 'nullable|string|max:500',
            'set_default_shipper' => 'nullable|boolean',
            'date' => 'nullable|date',
        ]);

        abort_if(!in_array($order->status, $this->assignmentStatuses(), true), 422, 'Đơn chưa ở trạng thái có thể gán shipper.');
        abort_if(!($shipper->hasRole('shipper') || $shipper->hasRole('manager_shipper')), 422, 'Người dùng không phải shipper.');

        $previousShipper = $order->shipper;

        DB::transaction(function () use ($request, $order, $shipper, $previousShipper): void {
            $order->update([
                'shipper_id' => $shipper->id,
            ]);

            $customer = $order->customer;
            if ($customer && (!$customer->default_shipper_id || $request->boolean('set_default_shipper'))) {
                $customer->update(['default_shipper_id' => $shipper->id]);
            }

            OrderHistory::create([
                'order_id'      => $order->id,
                'action'        => $previousShipper ? 'shipper_reassigned' : 'shipper_assigned',
                'user_id'       => Auth::id(),
                'role'          => 'manager_shipper',
                'status_before' => $order->status,
                'status_after'  => $order->status,
                'note'          => 'Quản lý ' . ($previousShipper ? 'chuyển' : 'gán trước') . ' đơn cho ' . $shipper->name
                    . ($customer && (int) $customer->default_shipper_id === (int) $shipper->id ? ' và đặt làm shipper cố định của khách' : '')
                    . ($request->filled('notes') ? ' - ' . $request->notes : ''),
            ]);
        });

        return $this->assignmentMutationResponse($request, 'Đã gán đơn #' . $order->code . ' cho ' . $shipper->name . ' thành công!');
    }

    /**
     * Change the customer's fixed shipper and optionally move pending orders.
     */
    public function updateCustomerDefaultShipper(Request $request, Customer $customer)
    {
        $this->authorizeManagerShipper();

        $validated = $request->validate([
            'shipper_id' => ['required', 'integer', 'exists:users,id'],
            'transfer_pending_orders' => ['nullable', 'boolean'],
            'date' => ['nullable', 'date'],
        ]);

        $shipper = User::query()->findOrFail((int) $validated['shipper_id']);
        abort_if(!($shipper->hasRole('shipper') || $shipper->hasRole('manager_shipper')), 422, 'Người dùng không phải shipper.');

        $previousShipperId = $customer->default_shipper_id ? (int) $customer->default_shipper_id : null;
        $transferredOrders = collect();

        DB::transaction(function () use ($request, $customer, $shipper, $previousShipperId, &$transferredOrders): void {
            $customer->update(['default_shipper_id' => $shipper->id]);

            if (!$request->boolean('transfer_pending_orders') || !$previousShipperId || $previousShipperId === (int) $shipper->id) {
                return;
            }

            $transferredOrders = Order::query()
                ->where('customer_id', $customer->id)
                ->where('shipper_id', $previousShipperId)
                ->whereIn('status', $this->assignmentStatuses())
                ->lockForUpdate()
                ->get();

            foreach ($transferredOrders as $order) {
                $order->update(['shipper_id' => $shipper->id]);

                OrderHistory::create([
                    'order_id' => $order->id,
                    'action' => 'shipper_reassigned',
                    'user_id' => Auth::id(),
                    'role' => 'manager_shipper',
                    'status_before' => $order->status,
                    'status_after' => $order->status,
                    'note' => 'Chuyển đơn theo thay đổi shipper cố định của khách sang ' . $shipper->name,
                ]);
            }
        });

        $message = 'Đã đổi shipper cố định của khách ' . $customer->name . ' sang ' . $shipper->name . '.';
        if ($transferredOrders->isNotEmpty()) {
            $message .= ' Đã chuyển ' . $transferredOrders->count() . ' đơn đang chờ sang shipper mới.';
        }

        return $this->assignmentMutationResponse($request, $message);
    }

    /**
     * Move all pre-assigned orders from one shipper to another.
     */
    public function bulkTransferAssignments(Request $request)
    {
        $this->authorizeManagerShipper();

        $validated = $request->validate([
            'from_shipper_id' => ['required', 'exists:users,id'],
            'to_shipper_id' => ['required', 'different:from_shipper_id', 'exists:users,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'date' => ['nullable', 'date'],
        ]);

        $fromShipper = User::query()->findOrFail((int) $validated['from_shipper_id']);
        $toShipper = User::query()->findOrFail((int) $validated['to_shipper_id']);

        abort_if(!($fromShipper->hasRole('shipper') || $fromShipper->hasRole('manager_shipper')), 422, 'Người chuyển không phải shipper.');
        abort_if(!($toShipper->hasRole('shipper') || $toShipper->hasRole('manager_shipper')), 422, 'Người nhận không phải shipper.');

        $date = $this->assignmentOrderingDate($request);

        $ordersQuery = Order::query()
            ->where('shipper_id', $fromShipper->id)
            ->whereIn('status', $this->assignmentStatuses())
            ->forDeliveryDate($date);

        $orders = DB::transaction(function () use ($ordersQuery, $fromShipper, $toShipper, $validated) {
            $orders = $ordersQuery->lockForUpdate()->get();

            foreach ($orders as $order) {
                $order->update([
                    'shipper_id' => $toShipper->id,
                ]);

                OrderHistory::create([
                    'order_id'      => $order->id,
                    'action'        => 'shipper_reassigned',
                    'user_id'       => Auth::id(),
                    'role'          => 'manager_shipper',
                    'status_before' => $order->status,
                    'status_after'  => $order->status,
                    'note'          => 'Chuyển đơn từ ' . $fromShipper->name . ' sang ' . $toShipper->name . (!empty($validated['notes']) ? ' - ' . $validated['notes'] : ''),
                ]);
            }

            return $orders;
        });

        if ($orders->isEmpty()) {
            return $this->assignmentMutationResponse($request, 'Không có đơn nào phù hợp để chuyển.');
        }

        return $this->assignmentMutationResponse($request, 'Đã chuyển ' . $orders->count() . ' đơn từ ' . $fromShipper->name . ' sang ' . $toShipper->name . '.');
    }

    /**
     * Gỡ ra: Loại đơn hàng khỏi danh sách gán shipper
     */
    public function unassignOrder(Request $request, Order $order)
    {
        $this->authorizeManagerShipper();

        abort_if(!in_array($order->status, $this->assignmentStatuses(), true), 422, 'Đơn chưa ở trạng thái có thể gỡ ra.');
        abort_if(!$order->shipper_id, 422, 'Đơn chưa được gán cho shipper nào.');

        $previousShipper = $order->shipper;
        $order->update([
            'shipper_id' => null,
        ]);

        OrderHistory::create([
            'order_id'      => $order->id,
            'action'        => 'shipper_unassigned',
            'user_id'       => Auth::id(),
            'role'          => 'manager_shipper',
            'status_before' => $order->status,
            'status_after'  => $order->status,
            'note'          => 'Quản lý gỡ ra đơn từ shipper ' . ($previousShipper?->name ?? 'N/A'),
        ]);

        return $this->assignmentMutationResponse($request, 'Đã gỡ ra đơn #' . $order->code . ' khỏi danh sách ' . ($previousShipper?->name ?? '') . ' thành công!');
    }

    /**
     * Tạo lịch trình giao hàng - Hoàn thành & Gửi xác nhận
     */
    public function createDeliverySchedule(Request $request)
    {
        $this->authorizeManagerShipper();

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
            'date' => ['nullable', 'date'],
        ]);

        $date = $this->assignmentOrderingDate($request);

        // Lấy danh sách tất cả shippers có đơn đã gán trong ngày
        $shipperIds = Order::query()
            ->whereNotNull('shipper_id')
            ->whereIn('status', $this->assignmentStatuses())
            ->forDeliveryDate($date)
            ->distinct('shipper_id')
            ->pluck('shipper_id')
            ->toArray();

        if (empty($shipperIds)) {
            return $this->assignmentMutationResponse($request, 'Không có shipper nào được gán đơn trong ngày để tạo lịch trình giao hàng.');
        }

        $totalOrdersCount = 0;
        $processedShippers = [];
        foreach ($shipperIds as $shipperId) {
            $shipper = User::query()->findOrFail($shipperId);
            $ordersCount = $this->deliveryScheduleOrdersForShipper((int) $shipperId, $date)->count();
            if (app(ShipperAssignmentService::class)->publishDailySchedule(
                (int) $shipperId,
                $date,
                (int) Auth::id(),
                'manager_shipper',
                $validated['notes'] ?? null,
            )) {
                $totalOrdersCount += $ordersCount;
                $processedShippers[] = $shipper->name . ' (' . $ordersCount . ' đơn)';
            }
        }

        if (empty($processedShippers)) {
            $message = 'Lộ trình hiện tại đã được gửi, không có thay đổi mới.';
            return $request->expectsJson() ? response()->json(['message' => $message]) : back()->with('info', $message);
        }

        $shipperList = implode(', ', $processedShippers);
        $message = 'Đã gửi lịch trình giao hàng cho ' . count($processedShippers) . ' shipper (' . $totalOrdersCount . ' đơn): ' . $shipperList . '. Các shipper sẽ nhận được thông báo xác nhận.';

        return $this->assignmentMutationResponse($request, $message);
    }

    /**
     * Danh sách lịch trình giao hàng cho shipper
     */
    public function deliverySchedules(Request $request)
    {
        $userId = Auth::id();
        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : Carbon::today()->toDateString();

        $orders = $this->deliveryScheduleOrdersForShipper($userId, $selectedDate)->get();

        $currentSnapshot = $this->buildDeliveryScheduleSnapshot($orders);
        $currentSnapshotHash = $this->hashDeliveryScheduleSnapshot($currentSnapshot);
        $latestHistory = $this->latestDeliveryScheduleHistoryForShipperOnDate($userId, $selectedDate);

        $scheduleAlreadyConfirmed = $this->deliveryScheduleStatus($latestHistory, $currentSnapshotHash) === 'confirmed';

        return view('shipper.delivery-schedules', compact(
            'orders',
            'selectedDate',
            'scheduleAlreadyConfirmed'
        ));
    }

    public function customers(Request $request)
    {
        $user = Auth::user();
        $isManagerShipper = $user && ($user->hasRole('manager_shipper') || $user->hasRole('admin'));
        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : Carbon::today()->toDateString();
        $sort = in_array($request->input('sort'), ['name', 'delivery_time', 'orders_count', 'total'], true)
            ? $request->input('sort')
            : 'delivery_time';
        $direction = $request->input('direction') === 'desc' ? 'desc' : 'asc';

        if ($isManagerShipper) {
            $assignmentStatus = in_array($request->input('assignment_status'), ['all', 'fixed', 'unassigned'], true)
                ? $request->input('assignment_status')
                : 'all';
            $keyword = trim((string) $request->input('q', ''));

            $customers = Customer::query()
                ->with('defaultShipper:id,name')
                ->when($keyword !== '', function ($query) use ($keyword) {
                    $query->where(function ($subQuery) use ($keyword) {
                        $subQuery->where('name', 'like', '%' . $keyword . '%')
                            ->orWhere('phone', 'like', '%' . $keyword . '%')
                            ->orWhere('address', 'like', '%' . $keyword . '%');
                    });
                })
                ->when($assignmentStatus === 'fixed', fn ($query) => $query->whereNotNull('default_shipper_id'))
                ->when($assignmentStatus === 'unassigned', fn ($query) => $query->whereNull('default_shipper_id'))
                ->withCount(['orders as orders_count' => fn ($query) => $query->forDeliveryDate($selectedDate)])
                ->withSum(['orders as orders_total' => fn ($query) => $query->forDeliveryDate($selectedDate)], 'total')
                ->when($sort === 'name', fn ($query) => $query->orderBy('name', $direction))
                ->when($sort === 'delivery_time', fn ($query) => $query->orderByRaw("CASE WHEN delivery_time IS NULL OR delivery_time = '' THEN 1 ELSE 0 END")->orderBy('delivery_time', $direction))
                ->when($sort === 'orders_count', fn ($query) => $query->orderBy('orders_count', $direction))
                ->when($sort === 'total', fn ($query) => $query->orderBy('orders_total', $direction))
                ->orderBy('name')
                ->get();

            $shippers = User::query()
                ->whereHas('roles', function ($query) {
                    $query->whereIn('name', ['shipper', 'manager_shipper']);
                })
                ->orderBy('name')
                ->get(['id', 'name']);

            return view('shipper.customers', compact(
                'selectedDate',
                'sort',
                'direction',
                'customers',
                'shippers',
                'isManagerShipper',
                'assignmentStatus',
                'keyword',
            ));
        }

        $customers = Customer::query()
            ->where(function ($query) {
                $query->where('default_shipper_id', Auth::id())
                    ->orWhereNull('default_shipper_id');
            })
            ->whereHas('orders', fn ($query) => $query
                ->where('shipper_id', Auth::id())
                ->forDeliveryDate($selectedDate))
            ->withCount(['orders as orders_count' => fn ($query) => $query
                ->where('shipper_id', Auth::id())
                ->forDeliveryDate($selectedDate)])
            ->withSum(['orders as orders_total' => fn ($query) => $query
                ->where('shipper_id', Auth::id())
                ->forDeliveryDate($selectedDate)], 'total')
            ->when($sort === 'name', fn ($query) => $query->orderBy('name', $direction))
            ->when($sort === 'delivery_time', fn ($query) => $query->orderByRaw("CASE WHEN delivery_time IS NULL OR delivery_time = '' THEN 1 ELSE 0 END")->orderBy('delivery_time', $direction))
            ->when($sort === 'orders_count', fn ($query) => $query->orderBy('orders_count', $direction))
            ->when($sort === 'total', fn ($query) => $query->orderBy('orders_total', $direction))
            ->orderBy('name')
            ->get();

        $fixedCustomers = $customers->where('default_shipper_id', Auth::id())->values();
        $unassignedCustomers = $customers->whereNull('default_shipper_id')->values();

        return view('shipper.customers', compact(
            'selectedDate',
            'sort',
            'direction',
            'fixedCustomers',
            'unassignedCustomers',
            'isManagerShipper',
        ));
    }

    public function deliveryStatistics(Request $request)
    {
        $fromDate = $request->filled('from_date')
            ? Carbon::parse($request->input('from_date'))->startOfDay()
            : Carbon::today()->startOfWeek();
        $toDate = $request->filled('to_date')
            ? Carbon::parse($request->input('to_date'))->startOfDay()
            : Carbon::today()->endOfWeek();

        if ($fromDate->gt($toDate)) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }
        if ($fromDate->diffInDays($toDate) > 31) {
            $toDate = $fromDate->copy()->addDays(31);
        }

        $dates = collect();
        for ($date = $fromDate->copy(); $date->lte($toDate); $date->addDay()) {
            $dates->push($date->toDateString());
        }

        $orders = Order::query()
            ->with('customer:id,name')
            ->where('shipper_id', Auth::id())
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
            ->whereDate('delivery_date', '>=', $fromDate->toDateString())
            ->whereDate('delivery_date', '<=', $toDate->toDateString())
            ->get();

        $rows = $orders->groupBy('customer_id')->map(function ($customerOrders) use ($dates) {
            return [
                'customer' => $customerOrders->first()->customer?->name ?? 'Khách hàng',
                'days' => $dates->mapWithKeys(fn ($date) => [
                    $date => $customerOrders->where(fn ($order) => optional($order->delivery_date)->toDateString() === $date)->count(),
                ]),
                'total' => $customerOrders->count(),
            ];
        })->sortBy('customer')->values();

        return view('shipper.delivery-statistics', [
            'fromDate' => $fromDate->toDateString(),
            'toDate' => $toDate->toDateString(),
            'dates' => $dates,
            'rows' => $rows,
        ]);
    }

    /**
     * Xác nhận lịch trình giao hàng dựa trên snapshot OrderHistory hiện tại.
     */
    public function confirmDeliverySchedule(Request $request, $schedule)
    {
        return $this->recordDeliveryScheduleDecision($request, $schedule, 'schedule_confirmed', 'Bạn đã xác nhận nhận lịch trình giao hàng. Sẵn sàng giao hàng!');
    }

    public function rejectDeliverySchedule(Request $request, $schedule)
    {
        return $this->recordDeliveryScheduleDecision($request, $schedule, 'schedule_rejected', 'Bạn đã từ chối nhận lịch trình giao hàng.');
    }

    private function recordDeliveryScheduleDecision(Request $request, $schedule, string $historyAction, string $successMessage)
    {
        $userId = Auth::id();
        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : Carbon::today()->toDateString();

        if ($schedule === 'bulk') {
            $validated = $request->validate([
                'order_ids' => ['required', 'array', 'min:1'],
                'order_ids.*' => ['integer', 'exists:orders,id'],
            ]);

            $orders = Order::query()
                ->whereIn('id', $validated['order_ids'])
                ->where('shipper_id', $userId)
                ->forDeliveryDate($selectedDate)
                ->orderByRaw('CASE WHEN daily_sequence IS NULL THEN 1 ELSE 0 END')
                ->orderBy('daily_sequence', 'asc')
                ->orderBy('delivery_time', 'asc')
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            abort_if($orders->count() !== count($validated['order_ids']), 403, 'Có đơn không thuộc về bạn.');

            $snapshot = $this->buildDeliveryScheduleSnapshot($orders);
            $snapshotHash = $this->hashDeliveryScheduleSnapshot($snapshot);

            DB::transaction(function () use ($orders, $userId, $historyAction, $snapshotHash, $snapshot): void {
                foreach ($orders as $order) {
                    OrderHistory::create([
                        'order_id'      => $order->id,
                        'action'        => $historyAction,
                        'user_id'       => $userId,
                        'role'          => 'shipper',
                        'status_before' => $order->status,
                        'status_after'  => $order->status,
                        'note'          => 'Shipper ' . Auth::user()->name . ' đã cập nhật trạng thái lịch trình giao hàng.',
                        'schedule_snapshot_hash' => $snapshotHash,
                        'schedule_snapshot'      => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }
            });

            return back()->with('success', $successMessage);
        }

        $order = Order::findOrFail($schedule);
        abort_if($order->shipper_id !== $userId, 403, 'Đơn này không thuộc về bạn.');

        $orders = Order::query()
            ->where('shipper_id', $userId)
            ->whereIn('status', $this->assignmentStatuses())
            ->forDeliveryDate($selectedDate)
            ->orderByRaw('CASE WHEN daily_sequence IS NULL THEN 1 ELSE 0 END')
            ->orderBy('daily_sequence', 'asc')
            ->orderBy('delivery_time', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $snapshot = $this->buildDeliveryScheduleSnapshot($orders);
        $snapshotHash = $this->hashDeliveryScheduleSnapshot($snapshot);

        OrderHistory::create([
            'order_id'      => $order->id,
            'action'        => $historyAction,
            'user_id'       => $userId,
            'role'          => 'shipper',
            'status_before' => $order->status,
            'status_after'  => $order->status,
            'note'          => 'Shipper ' . Auth::user()->name . ' đã cập nhật trạng thái lịch trình giao hàng.',
            'schedule_snapshot_hash' => $snapshotHash,
            'schedule_snapshot'      => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        return back()->with('success', $successMessage);
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

    /**
     * Manager Shipper: Manage shipping fees
     */
    public function manageFees(Request $request)
    {
        $this->authorizeManagerShipper();

        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : Carbon::today()->toDateString();

        // Get orders with delivery status
        $orders = Order::with(['customer', 'shipper', 'items', 'accountingReconciliation'])
            ->whereIn('status', [Order::STATUS_READY_TO_SHIP, Order::STATUS_DELIVERING, 'delivered', 'completed'])
            ->forDeliveryDate($selectedDate)
            ->orderBy('created_at', 'asc')
            ->paginate(15);

        $orderReturns = OrderReturn::query()
            ->with(['order.customer', 'warehouse'])
            ->where(function ($query) use ($selectedDate) {
                $query->whereDate('created_at', $selectedDate)
                    ->orWhereDate('updated_at', $selectedDate);
            })
            ->orderByDesc('id')
            ->get();

        return view('shipper.manage-fees', compact('orders', 'selectedDate', 'orderReturns'));
    }

    /**
     * Update shipping fee for single order
     */
    public function updateFee(Request $request, Order $order)
    {
        $this->authorizeManagerShipper();

        abort_if($order->accountingReconciliation?->status === \App\Models\AccountingReconciliation::STATUS_CONFIRMED, 422, 'Kế toán đã xác nhận đơn, không thể điều chỉnh phí ship.');

        $request->validate([
            'shipping_fee' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $oldFee = (float) ($order->shipping_fee ?? 0);
        $newFee = (float) $request->input('shipping_fee');

        $order->update([
            'shipping_fee' => $newFee,
        ]);

        // Recalculate order total
        $itemsSubtotal = (float) $order->items->sum(function ($item) {
            return (float) ($item->total ?? (($item->price ?? 0) * ($item->display_total_value ?? 0)));
        });
        $foamBoxFee = (float) (($order->charge_foam_box_fee ?? false) ? ($order->foam_box_price ?? 0) : 0);
        $newTotal = $itemsSubtotal + $newFee + $foamBoxFee;

        $order->update([
            'total' => $newTotal,
        ]);

        OrderHistory::create([
            'order_id'      => $order->id,
            'action'        => 'shipping_fee_updated',
            'user_id'       => Auth::id(),
            'role'          => 'manager_shipper',
            'note'          => 'Cập nhật phí ship từ ' . number_format($oldFee, 0, ',', '.') . ' đ thành ' . 
                              number_format($newFee, 0, ',', '.') . ' đ' . 
                              ($request->filled('notes') ? ' - ' . $request->notes : ''),
        ]);

        $this->syncCustomerShippingFeeHistory($order, $oldFee, $newFee, $request->input('notes'));

        return back()->with('success', 'Cập nhật phí ship cho đơn #' . $order->code . ' thành công!');
    }

    /**
     * Bulk update shipping fees
     */
    public function bulkUpdateFees(Request $request)
    {
        $this->authorizeManagerShipper();

        $request->validate([
            'fee_adjustment' => 'required|numeric',
            'adjustment_type' => 'required|in:fixed,percent',
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'exists:orders,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $orders = Order::with('accountingReconciliation')->whereIn('id', $request->input('order_ids'))->get();
        abort_if($orders->contains(fn (Order $order) => $order->accountingReconciliation?->status === \App\Models\AccountingReconciliation::STATUS_CONFIRMED), 422, 'Danh sách có đơn đã được kế toán xác nhận. Vui lòng bỏ các đơn đã chốt.');
        $adjustmentType = $request->input('adjustment_type');
        $adjustmentValue = (float) $request->input('fee_adjustment');
        $notes = $request->input('notes', '');

        DB::transaction(function () use ($orders, $adjustmentType, $adjustmentValue, $notes) {
            foreach ($orders as $order) {
                $oldFee = (float) ($order->shipping_fee ?? 0);
                $newFee = $adjustmentType === 'fixed' 
                    ? max(0, $oldFee + $adjustmentValue)
                    : max(0, $oldFee * (1 + $adjustmentValue / 100));

                $order->update(['shipping_fee' => $newFee]);

                // Recalculate order total
                $itemsSubtotal = (float) $order->items->sum(function ($item) {
                    return (float) ($item->total ?? (($item->price ?? 0) * ($item->display_total_value ?? 0)));
                });
                $foamBoxFee = (float) (($order->charge_foam_box_fee ?? false) ? ($order->foam_box_price ?? 0) : 0);
                $newTotal = $itemsSubtotal + $newFee + $foamBoxFee;

                $order->update(['total' => $newTotal]);

                OrderHistory::create([
                    'order_id'      => $order->id,
                    'action'        => 'shipping_fee_updated',
                    'user_id'       => Auth::id(),
                    'role'          => 'manager_shipper',
                    'note'          => 'Cập nhật hàng loạt phí ship từ ' . number_format($oldFee, 0, ',', '.') . ' đ thành ' . 
                                      number_format($newFee, 0, ',', '.') . ' đ' . 
                                      (!empty($notes) ? ' - ' . $notes : ''),
                ]);

                $this->syncCustomerShippingFeeHistory($order, $oldFee, $newFee, $notes);
            }
        });

        return back()->with('success', 'Cập nhật phí ship cho ' . count($orders) . ' đơn hàng thành công!');
    }

    /**
     * Manager Shipper: Route planning view
     */
    public function routePlanning(Request $request)
    {
        $this->authorizeManagerShipper();

        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : Carbon::tomorrow()->toDateString();

        // Group orders by shipper for route optimization
        $orders = Order::with(['customer', 'shipper', 'items.variant'])
            ->whereIn('status', [Order::STATUS_READY_TO_SHIP, Order::STATUS_DELIVERING])
            ->forDeliveryDate($selectedDate)
            ->orderBy('shipper_id')
            ->orderBy('created_at', 'asc')
            ->get();

        // Group by shipper
        $ordersByShipper = $orders->groupBy('shipper_id');

        $shippers = \App\Models\User::query()
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['shipper', 'manager_shipper']);
            })
            ->orderBy('name')
            ->get();

        return view('shipper.route-planning', compact('orders', 'ordersByShipper', 'shippers', 'selectedDate'));
    }

    /**
     * Manager Shipper: Team performance report
     */
    public function teamReport(Request $request)
    {
        $this->authorizeManagerShipper();

        $fromDate = $request->filled('from_date')
            ? Carbon::parse($request->input('from_date'))->toDateString()
            : Carbon::today()->toDateString();

        $toDate = $request->filled('to_date')
            ? Carbon::parse($request->input('to_date'))->toDateString()
            : Carbon::today()->toDateString();

        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $shipperIds = User::query()
            ->whereHas('roles', function ($q) {
                $q->where('name', 'shipper');
            })
            ->pluck('id');

        $orders = Order::query()
            ->whereIn('shipper_id', $shipperIds)
            ->whereDate('updated_at', '>=', $fromDate)
            ->whereDate('updated_at', '<=', $toDate)
            ->get([
                'id',
                'shipper_id',
                'status',
                'shipping_fee',
                'charge_shipping_fee',
                'collected_amount',
            ]);

        $ordersByShipper = $orders->groupBy('shipper_id');
        $shippers = User::query()
            ->whereIn('id', $shipperIds)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'email']);

        $shipperStats = $shippers->map(function (User $shipper) use ($ordersByShipper) {
            $shipperOrders = $ordersByShipper->get($shipper->id, collect());

            $totalOrders = $shipperOrders->count();
            $delivering = $shipperOrders->where('status', Order::STATUS_DELIVERING)->count();
            $delivered = $shipperOrders->where('status', 'delivered')->count();
            $completed = $shipperOrders->where('status', Order::STATUS_COMPLETED)->count();
            $returning = $shipperOrders->where('status', Order::STATUS_RETURNING)->count();
            $returnedCompleted = $shipperOrders->where('status', Order::STATUS_RETURNED_COMPLETED)->count();
            $doneOrders = $delivered + $completed;

            $totalShipFee = $shipperOrders->sum(function ($order) {
                return ($order->charge_shipping_fee ?? true) ? (float) ($order->shipping_fee ?? 0) : 0;
            });

            $totalCollected = $shipperOrders->sum(function ($order) {
                return (float) ($order->collected_amount ?? 0);
            });

            $completionRate = $totalOrders > 0
                ? round(($doneOrders / $totalOrders) * 100, 1)
                : 0;

            return [
                'shipper' => $shipper,
                'total_orders' => $totalOrders,
                'delivering' => $delivering,
                'delivered' => $delivered,
                'completed' => $completed,
                'returning' => $returning,
                'returned_completed' => $returnedCompleted,
                'done_orders' => $doneOrders,
                'completion_rate' => $completionRate,
                'total_ship_fee' => (float) $totalShipFee,
                'total_collected' => (float) $totalCollected,
            ];
        })->sortByDesc('total_orders')->values();

        $teamSummary = [
            'total_shippers' => $shippers->count(),
            'active_shippers' => $shipperStats->where('total_orders', '>', 0)->count(),
            'total_orders' => $orders->count(),
            'delivering' => $orders->where('status', Order::STATUS_DELIVERING)->count(),
            'done_orders' => $orders->whereIn('status', ['delivered', Order::STATUS_COMPLETED])->count(),
            'returning' => $orders->where('status', Order::STATUS_RETURNING)->count(),
            'total_ship_fee' => (float) $orders->sum(function ($order) {
                return ($order->charge_shipping_fee ?? true) ? (float) ($order->shipping_fee ?? 0) : 0;
            }),
            'total_collected' => (float) $orders->sum(function ($order) {
                return (float) ($order->collected_amount ?? 0);
            }),
        ];

        $unassignedReadyOrders = Order::query()
            ->whereNull('shipper_id')
            ->where('status', Order::STATUS_READY_TO_SHIP)
            ->whereDate('updated_at', '>=', $fromDate)
            ->whereDate('updated_at', '<=', $toDate)
            ->count();

        $filters = [
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];

        return view('shipper.team-report', compact(
            'filters',
            'teamSummary',
            'shipperStats',
            'unassignedReadyOrders'
        ));
    }

    public function shippingFeeReport(Request $request)
    {
        $this->authorizeManagerShipper();

        $fromDate = $request->filled('from_date')
            ? Carbon::parse($request->input('from_date'))->toDateString()
            : Carbon::today()->toDateString();

        $toDate = $request->filled('to_date')
            ? Carbon::parse($request->input('to_date'))->toDateString()
            : Carbon::today()->toDateString();

        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $dailyOrders = Order::query()
            ->with(['customer', 'shipper'])
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED, Order::STATUS_RETURNED_COMPLETED])
            ->whereDate('updated_at', '>=', $fromDate)
            ->whereDate('updated_at', '<=', $toDate)
            ->orderByDesc('updated_at')
            ->get();

        $dailySummary = [
            'total_orders' => $dailyOrders->count(),
            'total_ship_fee' => (float) $dailyOrders->sum(function (Order $order) {
                return ($order->charge_shipping_fee ?? true) ? (float) ($order->shipping_fee ?? 0) : 0;
            }),
            'customers' => $dailyOrders->pluck('customer_id')->filter()->unique()->count(),
        ];

        $feeChanges = CustomerShippingFeeHistory::query()
            ->with(['customer', 'order', 'user'])
            ->whereDate('changed_at', '>=', $fromDate)
            ->whereDate('changed_at', '<=', $toDate)
            ->whereHas('customer.orders', function ($query) {
                $query->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED, Order::STATUS_RETURNED_COMPLETED]);
            })
            ->orderByDesc('changed_at')
            ->get();

        return view('shipper.shipping-fee-report', compact(
            'fromDate',
            'toDate',
            'dailyOrders',
            'dailySummary',
            'feeChanges'
        ));
    }

    private function syncCustomerShippingFeeHistory(Order $order, float $oldFee, float $newFee, ?string $note = null): void
    {
        $customer = $order->customer;
        if (!$customer || abs($oldFee - $newFee) < 0.00001) {
            return;
        }

        $normalizedNewFee = round(max(0, $newFee), 2);

        CustomerShippingFeeHistory::create([
            'customer_id' => $customer->id,
            'order_id' => $order->id,
            'old_fee' => $oldFee,
            'new_fee' => $normalizedNewFee,
            'changed_by' => Auth::id(),
            'note' => $note ? mb_substr((string) $note, 0, 500) : 'Điều chỉnh riêng trên đơn hàng',
            'changed_at' => now(),
        ]);
    }

    public function updateCustomerShippingFee(Request $request, Customer $customer)
    {
        $this->authorizeManagerShipper();

        $validated = $request->validate([
            'shipping_fee' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $oldFee = $customer->shipping_fee === null ? null : (float) $customer->shipping_fee;
        $newFee = round((float) $validated['shipping_fee'], 2);
        $customer->update(['shipping_fee' => $newFee]);

        CustomerShippingFeeHistory::create([
            'customer_id' => $customer->id,
            'order_id' => null,
            'old_fee' => $oldFee,
            'new_fee' => $newFee,
            'changed_by' => Auth::id(),
            'note' => $validated['note'] ?? 'Cập nhật phí ship mặc định theo khách hàng',
            'changed_at' => now(),
        ]);

        return back()->with('success', 'Đã cập nhật phí ship mặc định cho khách hàng ' . $customer->name . '.');
    }

    protected function authorizeManagerShipper(): void
    {
        if (!(Auth::user()->hasRole('manager_shipper') || Auth::user()->hasRole('admin'))) {
            abort(403, 'Bạn không có quyền truy cập tính năng này.');
        }
    }
}
