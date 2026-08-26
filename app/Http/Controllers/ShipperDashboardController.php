<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerShippingFeeHistory;
use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\OrderReturn;
use App\Models\ProductVariant;
use App\Models\ReturnItem;
use App\Models\ShipperDispatchHistory;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseTransfer;
use App\Services\ShipperAssignmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ShipperDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:shipper,manager_shipper,account,accountant,accounting,admin']);
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
            'order_id' => $order->id,
            'action' => 'transfer_to_warehouse',
            'user_id' => Auth::id(),
            'role' => 'manager_shipper',
            'status_before' => $order->status,
            'status_after' => $order->status,
            'note' => 'Chuyển tới kho: '.$warehouse->name,
        ]);

        return back()->with('success', 'Đã chuyển đơn #'.$order->code.' tới kho '.$warehouse->name.' thành công!');
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
                $statusQuery->whereIn('status', [Order::STATUS_READY_TO_SHIP, Order::STATUS_PACKED])
                    ->orWhere(function ($returnQuery) {
                        $returnQuery->where('status', Order::STATUS_APPROVED)
                            ->where('is_return_order', true);
                    });
            });

        // Restored exception orders were explicitly reopened by an admin and
        // must be able to resume from packing through delivery. Their previous
        // schedule confirmation may have been invalidated by the cancellation,
        // so the assigned shipper must not be blocked from receiving them.
        $query->where(function ($scheduleQuery): void {
            $scheduleQuery->where('skip_auto_cancel', true)
                ->orWhere(function ($confirmedQuery): void {
                    $this->constrainConfirmedDeliverySchedule($confirmedQuery);
                });
        });

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
            ->where(fn ($query) => $this->constrainAssignmentStatuses($query))
            ->forWorkflowDate($dateString)
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
        abort_if(! $order->shipper_id, 422, 'Đơn chưa được gán cho shipper nào.');
        abort_if(! $this->isAssignmentEligible($order), 422, 'Đơn chưa ở trạng thái có thể sắp xếp.');

        DB::transaction(function () use ($order, $direction, $dateString): void {
            $this->reorderShipperDailySequences((int) $order->shipper_id, $dateString);

            $orders = Order::query()
                ->where('shipper_id', $order->shipper_id)
                ->where(fn ($query) => $this->constrainAssignmentStatuses($query))
                ->forWorkflowDate($dateString)
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
            if (! isset($orders[$targetIndex])) {
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

        return $this->assignmentMutationResponse($request, 'Đã đưa đơn #'.($order->code ?: $order->id).' lên trên.');
    }

    // Di chuyển đơn xuống dưới trong danh sách shipper
    public function moveOrderDown(Request $request, Order $order)
    {
        $this->authorizeManagerShipper();

        $dateString = $this->assignmentOrderingDate($request);
        $this->moveOrderWithinShipper($order, 1, $dateString);

        return $this->assignmentMutationResponse($request, 'Đã đưa đơn #'.($order->code ?: $order->id).' xuống dưới.');
    }

    public function moveOwnScheduleUp(Request $request, Order $order)
    {
        $currentUser = Auth::user();
        $canManageAny = $currentUser && ($currentUser->hasRole('manager_shipper') || $currentUser->hasRole('admin'));

        if (! $canManageAny && (int) ($order->shipper_id ?? 0) !== (int) Auth::id()) {
            abort(403, 'Đơn này không thuộc lịch trình của bạn.');
        }

        $dateString = $this->assignmentOrderingDate($request);
        $this->moveOrderWithinShipper($order, -1, $dateString);

        return back()->with('success', 'Đã đưa đơn #'.($order->code ?: $order->id).' lên trên lịch trình.');
    }

    public function moveOwnScheduleDown(Request $request, Order $order)
    {
        $currentUser = Auth::user();
        $canManageAny = $currentUser && ($currentUser->hasRole('manager_shipper') || $currentUser->hasRole('admin'));

        if (! $canManageAny && (int) ($order->shipper_id ?? 0) !== (int) Auth::id()) {
            abort(403, 'Đơn này không thuộc lịch trình của bạn.');
        }

        $dateString = $this->assignmentOrderingDate($request);
        $this->moveOrderWithinShipper($order, 1, $dateString);

        return back()->with('success', 'Đã đưa đơn #'.($order->code ?: $order->id).' xuống dưới lịch trình.');
    }

    public function index()
    {
        $userId = Auth::id();
        $today = Carbon::today();
        $selectedDate = $today->toDateString();
        $deliveryScheduleOrders = $this->deliveryScheduleOrdersForShipper($userId, $selectedDate)->get();
        $deliveryScheduleSnapshot = $this->buildDeliveryScheduleSnapshot($deliveryScheduleOrders);
        $deliveryScheduleHash = $this->hashDeliveryScheduleSnapshot($deliveryScheduleSnapshot);
        $latestScheduleHistory = $this->latestDeliveryScheduleHistoryForShipperOnDate($userId, $selectedDate);
        $deliveryScheduleStatus = $this->deliveryScheduleStatus($latestScheduleHistory, $deliveryScheduleHash);

        $stats = [
            'today_total' => Order::where('shipper_id', $userId)->whereDate('created_at', $today)->count(),
            'delivering' => Order::where('shipper_id', $userId)->where('status', Order::STATUS_DELIVERING)->count(),
            'delivered_today' => Order::where('shipper_id', $userId)
                ->where('status', 'delivered')
                ->whereDate('delivered_at', $today)->count(),
            'returning' => Order::where('shipper_id', $userId)->where('status', Order::STATUS_RETURNING)->count(),
            'cod_today' => Order::where('shipper_id', $userId)
                ->where('status', 'delivered')
                ->whereDate('delivered_at', $today)
                ->sum('collected_amount'),
            'available' => Order::where(function ($query) {
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
        $plannedExceptionOrderIds = $this->archivedPlannedOrderIdsForShipperOnDate((int) Auth::id(), $selectedDate);

        $dailyCounts = Order::query()
            ->selectRaw('DATE(created_at) as day_key, COUNT(*) as total')
            ->where(function ($query) {
                $query->where(function ($readyQuery) {
                    $this->constrainAvailableReadyOrder($readyQuery);
                })->orWhere(function ($acceptedQuery) {
                    $acceptedQuery->where('status', Order::STATUS_DELIVERING)
                        ->where('shipper_id', Auth::id());
                    $this->constrainNoActiveWarehouseTransfer($acceptedQuery);
                });
            })
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $today->toDateString())
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
            ->where(function ($dateQuery) use ($selectedDate, $plannedExceptionOrderIds): void {
                $dateQuery->forWorkflowDate($selectedDate);
                if ($plannedExceptionOrderIds !== []) {
                    $dateQuery->orWhere(function ($exceptionQuery) use ($plannedExceptionOrderIds): void {
                        $exceptionQuery->where('skip_auto_cancel', true)
                            ->whereIn('id', $plannedExceptionOrderIds);
                    });
                }
            })
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
            if (! $receivedTransfer) {
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
        try {
            $accepted = DB::transaction(function () use ($order) {
                $fresh = Order::with('items')
                    ->where('id', $order->id)
                    ->where(function ($query) {
                        $this->constrainAvailableReadyOrder($query);
                    })
                    ->where(function ($query) {
                        $today = Carbon::today()->toDateString();

                        $query->whereDate('updated_at', $today)
                            ->orWhereDate('created_at', $today)
                            ->orWhere('skip_auto_cancel', true)
                            ->orWhereNotNull('accounting_sales_import_batch_id');
                    })
                    ->lockForUpdate()
                    ->first();

                if (! $fresh) {
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

                if (! $isReturnOrder && $warehouseId > 0 && (int) ($fresh->warehouse_id ?? 0) !== $warehouseId) {
                    $fresh->update(['warehouse_id' => $warehouseId]);
                }

                $fresh->update([
                    'shipper_id' => Auth::id(),
                    'status' => Order::STATUS_DELIVERING,
                ]);

                OrderHistory::create([
                    'order_id' => $fresh->id,
                    'action' => 'shipper_accepted',
                    'user_id' => Auth::id(),
                    'role' => 'shipper',
                    'status_before' => $isReturnOrder ? Order::STATUS_APPROVED : (string) $fresh->getOriginal('status'),
                    'status_after' => Order::STATUS_DELIVERING,
                    'note' => $isReturnOrder ? 'Shipper nhận đơn hoàn trả' : 'Shipper nhận đơn để giao',
                ]);

                if ($isReturnOrder) {
                    return true;
                }

                $inventoryBusinessDate = $this->inventoryBusinessDateForOrder($fresh);
                $isPackedHistoricalException = (bool) $fresh->skip_auto_cancel
                    && Carbon::parse($inventoryBusinessDate)->startOfDay()->lt(Carbon::today());

                // The warehouse already validated and packed an explicitly
                // restored order against its historical business-day stock.
                // Shipper acceptance only starts delivery; checking/deducting
                // today's stock again would incorrectly block the old order.
                if ($isPackedHistoricalException) {
                    $this->releaseReservationsForAcceptedHistoricalOrder($fresh);

                    return true;
                }

                if ($warehouseId <= 0) {
                    throw new \RuntimeException('Không xác định được kho xuất cho đơn hàng này.');
                }

                $document = InventoryDocument::create([
                    'type' => 'export',
                    'document_date' => now()->toDateString(),
                    'warehouse_id' => $warehouseId,
                    'notes' => 'Xuất kho cho đơn #'.$fresh->code,
                    'shipping_fee' => (float) ($fresh->shipping_fee ?? 0),
                    'user_id' => Auth::id(),
                ]);

                $fresh->loadMissing('items');

                foreach ($fresh->items as $item) {
                    // Imported sales orders can contain non-stock lines such as
                    // shipping, foam-box or other service fees. They are kept as
                    // order items for display/accounting, but must not be written
                    // to an inventory document because there is no variant to
                    // export or stock to deduct.
                    if (! $item->product_variant_id) {
                        continue;
                    }

                    $document->items()->create([
                        'product_variant_id' => $item->product_variant_id,
                        'quantity' => $item->quantity,
                        'unit_cost' => $item->price ?? 0,
                    ]);

                    $this->deductStockForAcceptedOrderItem($fresh, $document, $item, $warehouseId);
                }

                return true;

            });
        } catch (\RuntimeException $exception) {
            report($exception);

            $message = trim($exception->getMessage()) ?: 'Không thể nhận đơn do dữ liệu kho chưa hợp lệ.';

            if (request()->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        if (! $accepted) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => 'Đơn hàng này không còn khả dụng hoặc không thuộc ngày lên đón hôm nay.',
                ], 409);
            }

            return back()->with('error', 'Đơn hàng này không còn khả dụng hoặc không thuộc ngày lên đón hôm nay.');
        }

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Đã nhận đơn #'.$order->code.' thành công!',
                'order' => [
                    'id' => $order->id,
                    'code' => $order->code,
                    'status' => Order::STATUS_DELIVERING,
                    'shipper_id' => Auth::id(),
                ],
            ]);
        }

        return redirect()->route('shipper.my-orders')
            ->with('success', 'Đã nhận đơn #'.$order->code.' thành công!');
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
            ->when(! $user->hasRole('admin') && ! $user->hasRole('manager_shipper'), function ($query) {
                $query->where('shipper_id', Auth::id());
            })
            ->whereIn('status', [
                WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP,
                WarehouseTransfer::STATUS_IN_TRANSIT,
                WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE,
                WarehouseTransfer::STATUS_RECEIVED_COMPLETED,
            ])
            ->whereHas('order')
            ->where(function ($query) use ($today): void {
                // Phiếu đang hoạt động phải luôn hiện cho shipper được giao, kể cả
                // ngày giao của đơn khác ngày tạo phiếu hoặc ngày đang xem.
                $query->whereIn('status', [
                    WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP,
                    WarehouseTransfer::STATUS_IN_TRANSIT,
                    WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE,
                ])->orWhere(function ($completedQuery) use ($today): void {
                    $completedQuery
                        ->where('status', WarehouseTransfer::STATUS_RECEIVED_COMPLETED)
                        ->whereHas('order', fn ($orderQuery) => $orderQuery->forDeliveryDate($today));
                });
            })
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

        $transfer->loadMissing(['dispatchEntry.slip', 'order.items', 'order.orderTransfer.dispatchEntry.slip']);
        $order = $transfer->order;
        if (! $order) {
            return back()->with('error', 'Không tìm thấy đơn hàng của phiếu điều chuyển.');
        }
        $dispatchSlip = $transfer->dispatchEntry?->slip ?? $order->orderTransfer?->dispatchEntry?->slip;
        if ($dispatchSlip && $dispatchSlip->status === \App\Models\WarehouseDispatchSlip::STATUS_DRAFT) {
            return back()->with('error', 'Phiếu xuất kho tổng '.$dispatchSlip->code.' chưa được kho xuất chốt.');
        }

        try {
            DB::transaction(function () use ($transfer, $order, $validated): void {
                $document = InventoryDocument::create([
                    'type' => 'export',
                    'document_date' => now()->toDateString(),
                    'warehouse_id' => $transfer->source_warehouse_id,
                    'notes' => 'Xuat kho dieu chuyen don #'.$order->code.' [WHT#'.$transfer->id.']',
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
                    'note' => 'Shipper da nhan hang dieu chuyen #'.$transfer->id,
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
            $noteParts[] = 'Lý do: '.$reason;
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
                'note' => 'Shipper hoàn lại phiếu điều chuyển #'.$transfer->id
                    .' trước khi kho nhận xác nhận. Kho gửi: '.($transfer->sourceWarehouse?->name ?? 'N/A')
                    .'; Kho nhận: '.($transfer->targetWarehouse?->name ?? 'N/A')
                    .($reason !== '' ? '; Lý do: '.$reason : ''),
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
                'note' => 'Shipper da giao hang dieu chuyen #'.$transfer->id.' cho kho nhan.',
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
            ->with(['dispatchEntry.slip', 'order.items', 'order.orderTransfer.dispatchEntry.slip'])
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
                    if (! $order) {
                        throw new \RuntimeException('Không tìm thấy đơn hàng của phiếu #'.$transfer->id);
                    }
                    $dispatchSlip = $transfer->dispatchEntry?->slip ?? $order->orderTransfer?->dispatchEntry?->slip;
                    if ($dispatchSlip && $dispatchSlip->status === \App\Models\WarehouseDispatchSlip::STATUS_DRAFT) {
                        throw new \RuntimeException('Phiếu xuất kho tổng '.$dispatchSlip->code.' chưa được kho xuất chốt.');
                    }

                    $document = InventoryDocument::create([
                        'type' => 'export',
                        'document_date' => now()->toDateString(),
                        'warehouse_id' => $transfer->source_warehouse_id,
                        'notes' => 'Xuat kho dieu chuyen don #'.$order->code.' [WHT#'.$transfer->id.']',
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
                        'note' => 'Shipper nhận hàng điều chuyển (bulk) #'.$transfer->id,
                    ]);
                });
                $processed++;
            } catch (\Throwable $e) {
                $errors[] = 'Phiếu #'.$transfer->id.': '.$e->getMessage();
            }
        }

        $message = "Đã nhận hàng {$processed}/{$transfers->count()} phiếu điều chuyển.";
        if (! empty($errors)) {
            return back()->with('warning', $message.' Lỗi: '.implode('; ', $errors));
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
                        'note' => 'Shipper giao hàng điều chuyển (bulk) #'.$transfer->id.' cho kho nhận.',
                    ]);
                }
                $processed++;
            } catch (\Throwable $e) {
                $errors[] = 'Phiếu #'.$transfer->id.': '.$e->getMessage();
            }
        }

        $message = "Đã giao hàng {$processed}/{$transfers->count()} phiếu điều chuyển cho kho nhận.";
        if (! empty($errors)) {
            return back()->with('warning', $message.' Lỗi: '.implode('; ', $errors));
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
            && ! empty($order->customer?->truck_station_id);

        $validationRules = [
            'collected_amount' => 'nullable|numeric|min:0',
            'proof_image' => 'nullable|image|max:5120',
            'truck_station_receipt_image' => 'nullable|image|max:5120',
            'weight_image' => 'nullable|image|max:5120',
            'actual_weight' => 'nullable|array',
            'actual_weight.*' => 'nullable|numeric|min:0',
            'delivered_qty' => 'nullable|array',
            'delivered_qty.*' => 'nullable|integer|min:0',
            'partial_weight' => 'nullable|array',
            'partial_weight.*' => 'nullable|numeric|min:0',
            'return_warehouse_id' => 'required_if:has_partial_return,1|nullable|exists:warehouses,id',
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
                if (! array_key_exists($item->id, $partialWeightInput)) {
                    continue;
                }
                $entered = $partialWeightInput[$item->id];
                if ($entered === null || $entered === '') {
                    continue;
                }

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
                    $weightErrors["partial_weight.{$item->id}"] = "Khối lượng giao của '{$item->variant?->name}' tối đa {$maxWeight} kg (đã nhập ".(float) $entered.' kg)';
                }
            }
            if (! empty($weightErrors)) {
                throw \Illuminate\Validation\ValidationException::withMessages($weightErrors);
            }
        }

        $proofImages = [];
        if ($request->hasFile('proof_image')) {
            $proofImages[] = $request->file('proof_image')->store('order-proofs', 'public');
        }

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
        $returnedItems = []; // [order_item_id => variant, quantity and returned weight]

        DB::transaction(function () use ($order, $actualWeights, $deliveredQtys, $partialWeights, $hasPartialReturn, $returnWarehouseId, $partialReturnReason, &$partialReturnNotes, &$returnedItems) {
            foreach ($order->items as $item) {
                $updates = [];

                // Cập nhật cân thực tế nếu shipper nhập
                if (array_key_exists($item->id, $actualWeights) && $actualWeights[$item->id] !== null && $actualWeights[$item->id] !== '') {
                    $updates['actual_weight'] = max(0, (float) $actualWeights[$item->id]);
                }

                // Xử lý giao 1 phần
                if ($hasPartialReturn && array_key_exists($item->id, $deliveredQtys)) {
                    $originalQty = (int) $item->quantity;
                    $deliveredQty = max(0, min((int) $deliveredQtys[$item->id], $originalQty));
                    $returnedQty = $originalQty - $deliveredQty;

                    if ($returnedQty > 0) {
                        $updates['quantity'] = $deliveredQty;

                        $baseWeight = $item->packed_weight !== null
                            ? (float) $item->packed_weight
                            : (($item->actual_weight !== null && (float) $item->actual_weight > 0)
                                ? (float) $item->actual_weight
                                : (float) $item->effective_unit_weight * $originalQty);
                        $deliveredWeight = 0.0;
                        $returnedWeight = 0.0;

                        // Lưu cân thực tế phần thực giao (ghi đè nếu shipper nhập partial_weight)
                        if ((bool) $item->effective_priced_by_kg) {
                            $deliveredWeight = array_key_exists($item->id, $partialWeights)
                                && $partialWeights[$item->id] !== null
                                && $partialWeights[$item->id] !== ''
                                    ? max(0, (float) $partialWeights[$item->id])
                                    : ($originalQty > 0 ? round($baseWeight * $deliveredQty / $originalQty, 3) : 0.0);
                            $returnedWeight = max(0, round($baseWeight - $deliveredWeight, 3));
                            $updates['actual_weight'] = $deliveredWeight;
                        }

                        $noteSegment = ($item->variant?->name ?? 'SP').': giao '.$deliveredQty.'/'.$originalQty.' (trả '.$returnedQty.')';
                        if ((bool) $item->effective_priced_by_kg) {
                            $noteSegment .= ' ~ '.$returnedWeight.' kg';
                        }

                        $partialReturnNotes[] = $noteSegment;
                        $returnedItems[$item->id] = [
                            'variant_id' => (int) $item->product_variant_id,
                            'returned_qty' => $returnedQty,
                            'returned_weight' => $returnedWeight,
                        ];
                    }
                }

                if (! empty($updates)) {
                    $effectiveQuantity = (int) ($updates['quantity'] ?? $item->quantity);
                    $effectiveWeight = (float) ($updates['actual_weight']
                        ?? $item->actual_weight
                        ?? $item->packed_weight
                        ?? ((float) $item->effective_unit_weight * $effectiveQuantity));
                    $updates['total'] = (bool) $item->effective_priced_by_kg
                        ? round($effectiveWeight * (float) ($item->price ?? 0), 2)
                        : round($effectiveQuantity * (float) ($item->price ?? 0), 2);
                    $item->update($updates);
                }
            }

            // Tạo phiếu OrderReturn cho hàng chưa giao – kho sẽ xác nhận nhập sau
            if (! empty($returnedItems) && $returnWarehouseId) {
                $orderReturn = OrderReturn::create([
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'warehouse_id' => $returnWarehouseId,
                    'created_by' => Auth::id(),
                    'status' => 'pending_warehouse',
                    'reason' => $partialReturnReason,
                    'return_scope' => 'partial',
                    'note' => 'Giao 1 phần: '.implode('; ', $partialReturnNotes),
                ]);

                foreach ($returnedItems as $info) {
                    ReturnItem::create([
                        'order_return_id' => $orderReturn->id,
                        'product_variant_id' => $info['variant_id'],
                        'quantity' => $info['returned_qty'],
                        'original_weight' => $info['returned_weight'],
                        'condition' => 'good',
                    ]);
                }
            }
        });

        $noteText = 'Giao hàng thành công.';
        if ($collectedAmount !== null && $collectedAmount > 0) {
            $noteText .= ' Đã thu: '.number_format($collectedAmount).'đ.';
        } else {
            $noteText .= ' Chưa thu tiền / thanh toán sau.';
        }

        if ($isTruckStationDelivery) {
            $stationName = $order->customer?->truckStation?->name ?: 'trạm xe';
            $noteText .= ' | Đã bàn giao hàng tại nhà xe: '.$stationName;
        }

        if (! empty($partialReturnNotes)) {
            $noteText .= ' | Giao 1 phần: '.implode('; ', $partialReturnNotes).' – Phiếu hoàn trả đã tạo';
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
        $foamBoxFee = (float) (($order->charge_foam_box_fee ?? false) ? ($order->foam_box_price ?? 0) : 0);
        [$newTotal, $vatAmount] = $this->customerOrderTotal($order, $newSubtotal, $shippingFee, $foamBoxFee);

        $order->update([
            'status' => 'delivered',
            'collected_amount' => $collectedAmount,
            'delivered_at' => now(),
            'proof_images' => $proofImages,
            'subtotal_amount' => $newSubtotal,
            'vat_amount' => $vatAmount,
            'total' => $newTotal,
        ]);

        OrderHistory::create([
            'order_id' => $order->id,
            'action' => 'delivered',
            'user_id' => Auth::id(),
            'role' => 'shipper',
            'status_before' => Order::STATUS_DELIVERING,
            'status_after' => 'delivered',
            'note' => $noteText,
        ]);

        $successMsg = 'Xác nhận giao hàng thành công!';
        if (! empty($partialReturnNotes)) {
            $successMsg .= ' Đã tạo phiếu hoàn trả '.count($returnedItems).' sản phẩm – chờ kho xác nhận nhập.';
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
            'return_note' => 'nullable|string|max:500',
            'return_image' => 'required|image|max:5120',
            'return_warehouse_id' => 'required|exists:warehouses,id',
        ]);

        $imagePath = $request->file('return_image')->store('order-returns-proof', 'public');
        $returnWarehouse = Warehouse::query()->findOrFail((int) $request->input('return_warehouse_id'));

        $shipperNote = trim((string) $request->input('return_note', ''));
        $warehouseNote = 'Kho trả về: '.$returnWarehouse->name;
        $shipperNote = $shipperNote !== '' ? $shipperNote.' | '.$warehouseNote : $warehouseNote;

        $updateData = [
            'status' => Order::STATUS_RETURNING,
            'return_reason' => $request->return_reason,
            'shipper_note' => $shipperNote,
            'proof_images' => [$imagePath],
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
                    'note' => trim($shipperNote.' | Đơn hoàn trả từ sale'),
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
            'order_id' => $order->id,
            'action' => 'return_request',
            'user_id' => Auth::id(),
            'role' => 'shipper',
            'status_before' => Order::STATUS_DELIVERING,
            'status_after' => Order::STATUS_RETURNING,
            'note' => 'Shipper gửi trả hàng: '.$request->return_reason.' | '.$warehouseNote,
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

        if (empty($date) && ! empty($period)) {
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
            ->when(! empty($date), function ($q) use ($date) {
                $q->whereDate('updated_at', $date);
            }, function ($q) use ($fromDate, $toDate) {
                if (! empty($fromDate)) {
                    $q->whereDate('updated_at', '>=', $fromDate);
                }

                if (! empty($toDate)) {
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
        if ($order->shipper_id !== Auth::id() && ! Auth::user()->hasRole('admin')) {
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

    private function inventoryBusinessDateForOrder(Order $order): string
    {
        if ($order->accounting_sales_import_batch_id && $order->delivery_date) {
            return $order->delivery_date->toDateString();
        }

        return $order->created_at?->toDateString() ?? now()->toDateString();
    }

    private function releaseReservationsForAcceptedHistoricalOrder(Order $order): void
    {
        $orderItemIds = $order->items->pluck('id')->all();
        if ($orderItemIds === []) {
            return;
        }

        $inventoryIds = InventoryReservation::query()
            ->whereIn('order_item_id', $orderItemIds)
            ->lockForUpdate()
            ->pluck('inventory_id')
            ->unique()
            ->values();

        InventoryReservation::query()->whereIn('order_item_id', $orderItemIds)->delete();

        foreach ($inventoryIds as $inventoryId) {
            $inventory = Inventory::query()->lockForUpdate()->find($inventoryId);
            if (! $inventory) {
                continue;
            }

            $inventory->reserved_quantity = max(
                0,
                (int) InventoryReservation::query()
                    ->where('inventory_id', $inventory->id)
                    ->sum('quantity')
            );
            $inventory->save();
        }
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
            if (! $inventory) {
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
                'inventory_id' => $inventory->id,
                'quantity' => -$deductQty,
                'type' => 'export',
                'reference_id' => $document->id,
                'reference_type' => InventoryDocument::class,
                'user_id' => Auth::id(),
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
                    'inventory_id' => $inventory->id,
                    'quantity' => -$deductQty,
                    'type' => 'export',
                    'reference_id' => $document->id,
                    'reference_type' => InventoryDocument::class,
                    'user_id' => Auth::id(),
                ]);

                $remaining -= $deductQty;
            }
        }

        if ($remaining > 0) {
            throw new \RuntimeException('Không đủ tồn kho khả dụng để xuất cho đơn #'.($order->code ?: $order->id));
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
        if (! $user) {
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
            : Carbon::today()->toDateString();

        $this->applyDefaultShipperAssignmentsForDate($selectedDate);

        $ordersQuery = Order::with([
            'customer.defaultShipper',
            'customer.truckStation',
            'customer.truckRoute.stops.station',
            'items.product',
            'items.variant.product',
            'shipper',
            'user',
            'warehouse',
            'warehouseTransfers' => fn ($query) => $query
                ->where('status', WarehouseTransfer::STATUS_RECEIVED_COMPLETED)
                ->with('targetWarehouse:id,name')
                ->orderByDesc('received_at')
                ->orderByDesc('id'),
            'histories' => fn ($query) => $query
                ->whereIn('action', [
                    'start_packing',
                    'complete_packing',
                    'warehouse_transfer_received',
                    'undo_start_packing',
                ])
                ->with('user.warehouse:id,name')
                ->orderByDesc('created_at')
                ->orderByDesc('id'),
        ])
            ->where(fn ($query) => $this->constrainAssignmentStatuses($query))
            // Màn hình điều phối là sổ theo ngày tạo đơn. Không dùng
            // forWorkflowDate() tại đây vì scope đó cố ý kéo các đơn ngoại lệ
            // được khôi phục từ ngày cũ vào luồng vận hành hôm nay.
            ->whereDate('orders.created_at', $selectedDate)
            ->orderByRaw("CASE WHEN delivery_time IS NULL OR delivery_time = '' THEN 1 ELSE 0 END")
            ->orderBy('delivery_time', 'asc')
            ->orderByRaw('CASE WHEN daily_sequence IS NULL THEN 1 ELSE 0 END')
            ->orderBy('daily_sequence', 'asc')
            ->orderBy('created_at', 'asc');

        $assignedOrdersCount = (clone $ordersQuery)->whereNotNull('shipper_id')->count();
        $unassignedOrdersCount = (clone $ordersQuery)->whereNull('shipper_id')->count();
        $totalOrdersCount = $assignedOrdersCount + $unassignedOrdersCount;

        $unassignedOrders = (clone $ordersQuery)
            ->whereNull('shipper_id')
            ->paginate(15)
            ->withQueryString();

        $this->attachAssignmentOriginWarehouses($unassignedOrders->getCollection());

        $assignedOrderCollection = (clone $ordersQuery)
            ->whereNotNull('shipper_id')
            ->get();

        $this->attachAssignmentOriginWarehouses($assignedOrderCollection);

        $assignedOrders = $assignedOrderCollection
            ->groupBy('shipper_id')
            ->map(function ($orders) {
                return $orders
                    ->sortBy(function ($order) {
                        return [
                            $order->delivery_time ?: '23:59:59',
                            $order->daily_sequence ?? PHP_INT_MAX,
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
        $historyCount = ShipperDispatchHistory::query()
            ->whereDate('schedule_date', $selectedDate)
            ->count();
        $view = $request->routeIs('accounting.*')
            ? 'accounting.ship.manage-assignments'
            : 'shipper.manage-assignments';

        return view($view, compact(
            'unassignedOrders',
            'assignedOrders',
            'shippers',
            'selectedDate',
            'assignedOrdersCount',
            'unassignedOrdersCount',
            'totalOrdersCount',
            'shipperScheduleStatuses',
            'hasUnpublishedSchedules',
            'warehouses',
            'historyCount'
        ));
    }

    private function attachAssignmentOriginWarehouses($orders): void
    {
        foreach ($orders as $order) {
            $warehouse = $this->resolveAssignmentOriginWarehouse($order);
            $order->setAttribute('assignment_origin_warehouse_id', $warehouse?->id);
            $order->setAttribute('assignment_origin_warehouse_name', $warehouse?->name);
        }
    }

    private function resolveAssignmentOriginWarehouse(Order $order): ?Warehouse
    {
        if ($order->warehouse) {
            return $order->warehouse;
        }

        $latestWarehouseHistory = $order->histories
            ->sortByDesc(fn (OrderHistory $history) => sprintf(
                '%s-%020d',
                $history->created_at?->format('YmdHis.u') ?? '',
                (int) $history->id
            ))
            ->first();

        if ($latestWarehouseHistory?->action === 'undo_start_packing') {
            return null;
        }

        if ($latestWarehouseHistory?->user?->warehouse) {
            return $latestWarehouseHistory->user->warehouse;
        }

        return $order->warehouseTransfers
            ->sortByDesc(fn (WarehouseTransfer $transfer) => sprintf(
                '%s-%020d',
                $transfer->received_at?->format('YmdHis.u') ?? '',
                (int) $transfer->id
            ))
            ->first()?->targetWarehouse;
    }

    public function assignmentHistory(Request $request)
    {
        $this->authorizeManagerShipper();

        $latestDate = ShipperDispatchHistory::query()->max('schedule_date');
        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : ($latestDate ?: Carbon::today()->toDateString());

        $versions = ShipperDispatchHistory::query()
            ->with('creator:id,name')
            ->whereDate('schedule_date', $selectedDate)
            ->orderByDesc('version')
            ->get();

        $selectedHistory = $request->filled('history_id')
            ? $versions->firstWhere('id', (int) $request->input('history_id'))
            : $versions->first();
        $selectedHistory ??= $versions->first();
        $routePlan = $selectedHistory?->route_plan ?? [];

        return view('shipper.manage-assignments-review', [
            'selectedDate' => $selectedDate,
            'notes' => $selectedHistory?->notes ?? '',
            'routePlan' => $routePlan,
            'routePlanJson' => json_encode($routePlan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'readOnly' => true,
            'historyVersions' => $versions,
            'selectedHistory' => $selectedHistory,
        ]);
    }

    private function applyDefaultShipperAssignmentsForDate(string $selectedDate): void
    {
        $orders = Order::with(['customer.defaultShipper'])
            ->whereNull('shipper_id')
            ->where(fn ($query) => $this->constrainAssignmentStatuses($query))
            ->whereDate('orders.created_at', $selectedDate)
            ->whereHas('customer', fn ($query) => $query->whereNotNull('default_shipper_id'))
            ->whereDoesntHave('histories', fn ($query) => $query->where('action', 'shipper_unassigned'))
            ->get();

        if ($orders->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($orders): void {
            foreach ($orders as $order) {
                $defaultShipperId = $order->customer?->default_shipper_id;
                if (! $defaultShipperId) {
                    continue;
                }

                $order->update(['shipper_id' => $defaultShipperId]);

                OrderHistory::create([
                    'order_id' => $order->id,
                    'action' => 'shipper_auto_assigned',
                    'user_id' => Auth::id(),
                    'role' => 'manager_shipper',
                    'status_before' => $order->status,
                    'status_after' => $order->status,
                    'note' => 'Tự động gán shipper cố định của khách hàng'
                        .($order->customer?->defaultShipper?->name ? ': '.$order->customer->defaultShipper->name : ''),
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
            ->whereDate('orders.created_at', $selectedDate)
            ->whereIn('order_histories.action', ['schedule_created', 'schedule_confirmed', 'schedule_rejected'])
            ->orderByDesc('order_histories.created_at')
            ->orderByDesc('order_histories.id')
            ->select('order_histories.*')
            ->first();
    }

    private function deliveryScheduleOrdersForShipper(int $shipperId, string $selectedDate)
    {
        $plannedExceptionOrderIds = $this->archivedPlannedOrderIdsForShipperOnDate($shipperId, $selectedDate);

        return Order::with(['customer', 'items.variant'])
            ->where('shipper_id', $shipperId)
            ->where(fn ($query) => $this->constrainAssignmentStatuses($query))
            ->where(function ($dateQuery) use ($selectedDate, $plannedExceptionOrderIds): void {
                $dateQuery->forWorkflowDate($selectedDate);
                if ($plannedExceptionOrderIds !== []) {
                    $dateQuery->orWhere(function ($exceptionQuery) use ($plannedExceptionOrderIds): void {
                        $exceptionQuery->where('skip_auto_cancel', true)
                            ->whereIn('id', $plannedExceptionOrderIds);
                    });
                }
            })
            ->orderByRaw('CASE WHEN daily_sequence IS NULL THEN 1 ELSE 0 END')
            ->orderBy('daily_sequence', 'asc')
            ->orderBy('delivery_time', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc');
    }

    private function archivedPlannedOrderIdsForShipperOnDate(int $shipperId, string $selectedDate): array
    {
        $dispatch = ShipperDispatchHistory::query()
            ->whereDate('schedule_date', $selectedDate)
            ->orderByDesc('version')
            ->orderByDesc('id')
            ->first(['route_plan']);

        if (! $dispatch) {
            return [];
        }

        $shipperPlan = collect($dispatch->route_plan ?? [])
            ->first(fn ($plan) => (int) ($plan['shipper_id'] ?? 0) === $shipperId);

        return collect($shipperPlan['routes'] ?? [])
            ->flatMap(fn ($route) => $route['orders'] ?? [])
            ->pluck('order_id')
            ->filter(fn ($orderId) => (int) $orderId > 0)
            ->map(fn ($orderId) => (int) $orderId)
            ->unique()
            ->values()
            ->all();
    }

    private function deliveryScheduleStatus(?OrderHistory $latestHistory, string $currentSnapshotHash): string
    {
        if (! $latestHistory) {
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

        abort_if(! $this->isAssignmentEligible($order), 422, 'Đơn chưa ở trạng thái có thể gán shipper.');
        abort_if(! ($shipper->hasRole('shipper') || $shipper->hasRole('manager_shipper')), 422, 'Người dùng không phải shipper.');

        $previousShipper = $order->shipper;

        DB::transaction(function () use ($request, $order, $shipper, $previousShipper): void {
            $order->update([
                'shipper_id' => $shipper->id,
            ]);

            $customer = $order->customer;
            if ($customer && (! $customer->default_shipper_id || $request->boolean('set_default_shipper'))) {
                $customer->update(['default_shipper_id' => $shipper->id]);
            }

            OrderHistory::create([
                'order_id' => $order->id,
                'action' => $previousShipper ? 'shipper_reassigned' : 'shipper_assigned',
                'user_id' => Auth::id(),
                'role' => 'manager_shipper',
                'status_before' => $order->status,
                'status_after' => $order->status,
                'note' => 'Quản lý '.($previousShipper ? 'chuyển' : 'gán trước').' đơn cho '.$shipper->name
                    .($customer && (int) $customer->default_shipper_id === (int) $shipper->id ? ' và đặt làm shipper cố định của khách' : '')
                    .($request->filled('notes') ? ' - '.$request->notes : ''),
            ]);
        });

        return $this->assignmentMutationResponse($request, 'Đã gán đơn #'.$order->code.' cho '.$shipper->name.' thành công!');
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
        abort_if(! ($shipper->hasRole('shipper') || $shipper->hasRole('manager_shipper')), 422, 'Người dùng không phải shipper.');

        $previousShipperId = $customer->default_shipper_id ? (int) $customer->default_shipper_id : null;
        $transferredOrders = collect();

        DB::transaction(function () use ($request, $customer, $shipper, $previousShipperId, &$transferredOrders): void {
            $customer->update(['default_shipper_id' => $shipper->id]);

            if (! $request->boolean('transfer_pending_orders') || ! $previousShipperId || $previousShipperId === (int) $shipper->id) {
                return;
            }

            $transferredOrders = Order::query()
                ->where('customer_id', $customer->id)
                ->where('shipper_id', $previousShipperId)
                ->where(fn ($query) => $this->constrainAssignmentStatuses($query))
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
                    'note' => 'Chuyển đơn theo thay đổi shipper cố định của khách sang '.$shipper->name,
                ]);
            }
        });

        $message = 'Đã đổi shipper cố định của khách '.$customer->name.' sang '.$shipper->name.'.';
        if ($transferredOrders->isNotEmpty()) {
            $message .= ' Đã chuyển '.$transferredOrders->count().' đơn đang chờ sang shipper mới.';
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

        abort_if(! ($fromShipper->hasRole('shipper') || $fromShipper->hasRole('manager_shipper')), 422, 'Người chuyển không phải shipper.');
        abort_if(! ($toShipper->hasRole('shipper') || $toShipper->hasRole('manager_shipper')), 422, 'Người nhận không phải shipper.');

        $date = $this->assignmentOrderingDate($request);

        $ordersQuery = Order::query()
            ->where('shipper_id', $fromShipper->id)
            ->where(fn ($query) => $this->constrainAssignmentStatuses($query))
            ->whereDate('orders.created_at', $date);

        $orders = DB::transaction(function () use ($ordersQuery, $fromShipper, $toShipper, $validated) {
            $orders = $ordersQuery->lockForUpdate()->get();

            foreach ($orders as $order) {
                $order->update([
                    'shipper_id' => $toShipper->id,
                ]);

                OrderHistory::create([
                    'order_id' => $order->id,
                    'action' => 'shipper_reassigned',
                    'user_id' => Auth::id(),
                    'role' => 'manager_shipper',
                    'status_before' => $order->status,
                    'status_after' => $order->status,
                    'note' => 'Chuyển đơn từ '.$fromShipper->name.' sang '.$toShipper->name.(! empty($validated['notes']) ? ' - '.$validated['notes'] : ''),
                ]);
            }

            return $orders;
        });

        if ($orders->isEmpty()) {
            return $this->assignmentMutationResponse($request, 'Không có đơn nào phù hợp để chuyển.');
        }

        return $this->assignmentMutationResponse($request, 'Đã chuyển '.$orders->count().' đơn từ '.$fromShipper->name.' sang '.$toShipper->name.'.');
    }

    /**
     * Gỡ ra: Loại đơn hàng khỏi danh sách gán shipper
     */
    public function unassignOrder(Request $request, Order $order)
    {
        $this->authorizeManagerShipper();

        abort_if(! $this->isAssignmentEligible($order), 422, 'Đơn chưa ở trạng thái có thể gỡ ra.');
        abort_if(! $order->shipper_id, 422, 'Đơn chưa được gán cho shipper nào.');

        $previousShipper = $order->shipper;
        $order->update([
            'shipper_id' => null,
        ]);

        OrderHistory::create([
            'order_id' => $order->id,
            'action' => 'shipper_unassigned',
            'user_id' => Auth::id(),
            'role' => 'manager_shipper',
            'status_before' => $order->status,
            'status_after' => $order->status,
            'note' => 'Quản lý gỡ ra đơn từ shipper '.($previousShipper?->name ?? 'N/A'),
        ]);

        return $this->assignmentMutationResponse($request, 'Đã gỡ ra đơn #'.$order->code.' khỏi danh sách '.($previousShipper?->name ?? '').' thành công!');
    }

    /**
     * Tạo lịch trình giao hàng - Hoàn thành & Gửi xác nhận
     */
    public function reviewDeliverySchedule(Request $request)
    {
        $this->authorizeManagerShipper();

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'route_plan' => ['required', 'string'],
        ]);

        $selectedDate = Carbon::parse($validated['date'])->toDateString();
        $routePlan = $this->decodeRoutePlan($validated['route_plan']);
        abort_if(empty($routePlan), 422, 'Vui lòng tạo ít nhất một chuyến trước khi xem lại.');

        return view('shipper.manage-assignments-review', [
            'selectedDate' => $selectedDate,
            'notes' => $validated['notes'] ?? '',
            'routePlan' => $routePlan,
            'routePlanJson' => json_encode($routePlan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function createDeliverySchedule(Request $request)
    {
        $this->authorizeManagerShipper();

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:500'],
            'date' => ['nullable', 'date'],
            'route_plan' => ['nullable', 'string'],
        ]);

        $date = $this->assignmentOrderingDate($request);
        $routePlan = $this->decodeRoutePlan($validated['route_plan'] ?? null);
        try {
            $this->applyRoutePlanFees($routePlan, $date);
        } catch (HttpExceptionInterface $exception) {
            if ($exception->getStatusCode() !== 422) {
                throw $exception;
            }

            $message = $exception->getMessage() ?: 'Lộ trình không còn hợp lệ. Vui lòng kiểm tra lại.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()
                ->route('shipper.manage-assignments', ['date' => $date])
                ->with('error', $message);
        }

        // The reviewed route plan is the source of truth. Re-querying every
        // order by created_at here used to silently drop restored late orders.
        $shipperIds = collect($routePlan)
            ->filter(fn ($shipperPlan) => collect($shipperPlan['routes'] ?? [])
                ->flatMap(fn ($route) => $route['orders'] ?? [])
                ->isNotEmpty())
            ->pluck('shipper_id')
            ->filter(fn ($shipperId) => (int) $shipperId > 0)
            ->map(fn ($shipperId) => (int) $shipperId)
            ->unique()
            ->values()
            ->toArray();

        if (empty($shipperIds)) {
            return $this->assignmentMutationResponse($request, 'Không có shipper nào được gán đơn trong ngày để tạo lịch trình giao hàng.');
        }

        $totalOrdersCount = 0;
        $processedShippers = [];
        foreach ($shipperIds as $shipperId) {
            $shipper = User::query()->findOrFail($shipperId);
            $ordersCount = collect($routePlan)
                ->first(fn ($shipperPlan) => (int) ($shipperPlan['shipper_id'] ?? 0) === (int) $shipperId);
            $ordersCount = collect($ordersCount['routes'] ?? [])->sum(
                fn ($route) => count($route['orders'] ?? [])
            );
            if (app(ShipperAssignmentService::class)->publishDailySchedule(
                (int) $shipperId,
                $date,
                (int) Auth::id(),
                Auth::user()?->hasRole(['account', 'accountant', 'accounting']) ? 'accounting' : 'manager_shipper',
                $validated['notes'] ?? null,
                $routePlan,
            )) {
                $totalOrdersCount += $ordersCount;
                $processedShippers[] = $shipper->name.' ('.$ordersCount.' đơn)';
            }
        }

        if (empty($processedShippers)) {
            $message = 'Lộ trình hiện tại đã được gửi, không có thay đổi mới.';

            return $request->expectsJson() ? response()->json(['message' => $message]) : back()->with('info', $message);
        }

        $shipperList = implode(', ', $processedShippers);
        $this->archiveDeliverySchedule($date, $routePlan, $validated['notes'] ?? null);
        $message = 'Đã gửi lịch trình giao hàng cho '.count($processedShippers).' shipper ('.$totalOrdersCount.' đơn): '.$shipperList.'. Các shipper sẽ nhận được thông báo xác nhận.';

        return $this->assignmentMutationResponse($request, $message);
    }

    private function archiveDeliverySchedule(string $date, array $routePlan, ?string $notes): ShipperDispatchHistory
    {
        $plans = collect($routePlan);
        $routes = $plans->flatMap(fn ($shipperPlan) => $shipperPlan['routes'] ?? []);
        $orders = $routes->flatMap(fn ($route) => $route['orders'] ?? []);

        return DB::transaction(function () use ($date, $routePlan, $notes, $plans, $routes, $orders): ShipperDispatchHistory {
            $latest = ShipperDispatchHistory::query()
                ->whereDate('schedule_date', $date)
                ->orderByDesc('version')
                ->lockForUpdate()
                ->first();

            return ShipperDispatchHistory::create([
                'schedule_date' => $date,
                'version' => ((int) ($latest?->version ?? 0)) + 1,
                'route_plan' => $routePlan,
                'notes' => filled($notes) ? trim($notes) : null,
                'shippers_count' => $plans->filter(fn ($plan) => ! empty($plan['routes']))->count(),
                'trips_count' => $routes->count(),
                'orders_count' => $orders->count(),
                'total_fee' => $orders->sum(fn ($order) => (float) ($order['final_fee'] ?? 0)),
                'created_by' => Auth::id(),
                'published_at' => now(),
            ]);
        });
    }

    private function decodeRoutePlan(?string $routePlanJson): array
    {
        if (! filled($routePlanJson)) {
            return [];
        }

        $decoded = json_decode($routePlanJson, true);
        abort_if(json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded), 422, 'Dữ liệu lộ trình không hợp lệ.');

        return $decoded;
    }

    private function applyRoutePlanFees(array $routePlan, string $date): void
    {
        if (empty($routePlan)) {
            return;
        }

        $plannedOrders = collect($routePlan)
            ->flatMap(fn ($shipperPlan) => $shipperPlan['routes'] ?? [])
            ->flatMap(fn ($route) => $route['orders'] ?? [])
            ->filter(fn ($order) => ! empty($order['order_id']))
            ->keyBy(fn ($order) => (int) $order['order_id']);

        if ($plannedOrders->isEmpty()) {
            return;
        }

        $orders = Order::with(['items', 'accountingReconciliation'])
            ->whereIn('id', $plannedOrders->keys()->all())
            ->where(fn ($query) => $this->constrainAssignmentStatuses($query))
            ->where(function ($dateQuery) use ($date, $plannedOrders): void {
                $dateQuery->forWorkflowDate($date)
                    ->orWhere(function ($exceptionQuery) use ($plannedOrders): void {
                        $exceptionQuery->where('skip_auto_cancel', true)
                            ->whereIn('id', $plannedOrders->keys()->all());
                    });
            })
            ->get()
            ->keyBy('id');

        abort_if($orders->count() !== $plannedOrders->count(), 422, 'Có đơn trong lộ trình không còn hợp lệ. Vui lòng tải lại trang.');
        $hasLockedFeeChange = $orders->contains(function (Order $order) use ($plannedOrders): bool {
            $plannedOrder = $plannedOrders->get((int) $order->id, []);
            $newFee = max(0, (float) ($plannedOrder['final_fee'] ?? $order->shipping_fee ?? 0));
            $oldFee = (float) ($order->shipping_fee ?? 0);

            return abs($oldFee - $newFee) >= 0.01
                && $order->accountingReconciliation?->status === \App\Models\AccountingReconciliation::STATUS_CONFIRMED
                && $order->accounting_sales_import_batch_id === null;
        });
        abort_if($hasLockedFeeChange, 422, 'Có đơn đã được kế toán xác nhận nên không thể đổi phí ship. Bạn vẫn có thể gửi nếu giữ nguyên phí của đơn đó.');

        DB::transaction(function () use ($plannedOrders, $orders): void {
            foreach ($plannedOrders as $orderId => $plannedOrder) {
                $order = $orders->get((int) $orderId);
                if (! $order) {
                    continue;
                }

                $newFee = max(0, (float) ($plannedOrder['final_fee'] ?? $order->shipping_fee ?? 0));
                $oldFee = (float) ($order->shipping_fee ?? 0);
                if (abs($oldFee - $newFee) < 0.01) {
                    continue;
                }

                $itemsSubtotal = (float) $order->items->sum(function ($item) {
                    return (float) ($item->total ?? (($item->price ?? 0) * ($item->display_total_value ?? $item->quantity ?? 0)));
                });
                $foamBoxFee = (float) (($order->charge_foam_box_fee ?? false) ? ($order->foam_box_price ?? 0) : 0);
                [$customerTotal, $vatAmount] = $this->customerOrderTotal($order, $itemsSubtotal, $newFee, $foamBoxFee);

                $updates = ['shipping_fee' => $newFee];
                if ($order->accounting_sales_import_batch_id === null) {
                    $updates['vat_amount'] = $vatAmount;
                    $updates['total'] = $customerTotal;
                }
                $order->update($updates);
                if ($order->accounting_sales_import_batch_id) {
                    $order->accountingReconciliation?->update(['shipping_fee' => $newFee]);
                }

                OrderHistory::create([
                    'order_id' => $order->id,
                    'action' => 'shipping_fee_updated',
                    'user_id' => Auth::id(),
                    'role' => Auth::user()?->hasRole(['account', 'accountant', 'accounting']) ? 'accounting' : 'manager_shipper',
                    'status_before' => $order->status,
                    'status_after' => $order->status,
                    'note' => 'Cập nhật phí ship theo lộ trình ghép chuyến từ '
                        .number_format($oldFee, 0, ',', '.').' đ thành '
                        .number_format($newFee, 0, ',', '.').' đ',
                ]);

                $this->syncCustomerShippingFeeHistory($order, $oldFee, $newFee, $plannedOrder['note'] ?? null);
            }
        });
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
                        $subQuery->where('name', 'like', '%'.$keyword.'%')
                            ->orWhere('phone', 'like', '%'.$keyword.'%')
                            ->orWhere('address', 'like', '%'.$keyword.'%');
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
                ->forWorkflowDate($selectedDate)
                ->orderByRaw('CASE WHEN daily_sequence IS NULL THEN 1 ELSE 0 END')
                ->orderBy('daily_sequence', 'asc')
                ->orderBy('delivery_time', 'asc')
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            abort_if($orders->count() !== count($validated['order_ids']), 403, 'Có đơn không thuộc về bạn.');

            $snapshot = $this->buildDeliveryScheduleSnapshot($orders);
            $snapshotHash = $this->hashDeliveryScheduleSnapshot($snapshot);
            $latestHistory = $this->latestDeliveryScheduleHistoryForShipperOnDate($userId, $selectedDate);
            if ($historyAction === 'schedule_confirmed' && $this->deliveryScheduleStatus($latestHistory, $snapshotHash) === 'confirmed') {
                return back()->with('info', 'Lịch trình đã được xác nhận trước đó.');
            }

            DB::transaction(function () use ($orders, $userId, $historyAction, $snapshotHash, $snapshot): void {
                foreach ($orders as $order) {
                    OrderHistory::create([
                        'order_id' => $order->id,
                        'action' => $historyAction,
                        'user_id' => $userId,
                        'role' => 'shipper',
                        'status_before' => $order->status,
                        'status_after' => $order->status,
                        'note' => 'Shipper '.Auth::user()->name.' đã cập nhật trạng thái lịch trình giao hàng.',
                        'schedule_snapshot_hash' => $snapshotHash,
                        'schedule_snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }
            });

            return back()->with('success', $successMessage);
        }

        $order = Order::findOrFail($schedule);
        abort_if($order->shipper_id !== $userId, 403, 'Đơn này không thuộc về bạn.');

        $orders = Order::query()
            ->where('shipper_id', $userId)
            ->where(fn ($query) => $this->constrainAssignmentStatuses($query))
            ->forWorkflowDate($selectedDate)
            ->orderByRaw('CASE WHEN daily_sequence IS NULL THEN 1 ELSE 0 END')
            ->orderBy('daily_sequence', 'asc')
            ->orderBy('delivery_time', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $snapshot = $this->buildDeliveryScheduleSnapshot($orders);
        $snapshotHash = $this->hashDeliveryScheduleSnapshot($snapshot);
        $latestHistory = $this->latestDeliveryScheduleHistoryForShipperOnDate($userId, $selectedDate);
        if ($historyAction === 'schedule_confirmed' && $this->deliveryScheduleStatus($latestHistory, $snapshotHash) === 'confirmed') {
            return back()->with('info', 'Lịch trình đã được xác nhận trước đó.');
        }

        OrderHistory::create([
            'order_id' => $order->id,
            'action' => $historyAction,
            'user_id' => $userId,
            'role' => 'shipper',
            'status_before' => $order->status,
            'status_after' => $order->status,
            'note' => 'Shipper '.Auth::user()->name.' đã cập nhật trạng thái lịch trình giao hàng.',
            'schedule_snapshot_hash' => $snapshotHash,
            'schedule_snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        return back()->with('success', $successMessage);
    }

    private function assignmentStatuses(): array
    {
        return [
            Order::STATUS_APPROVED,
            Order::STATUS_READY_TO_PACK,
            Order::STATUS_PACKING,
            Order::STATUS_PACKED,
            Order::STATUS_READY_TO_SHIP,
        ];
    }

    private function constrainAssignmentStatuses($query): void
    {
        $query->whereIn('status', $this->assignmentStatuses())
            ->orWhere(function ($imported): void {
                $imported->where('status', Order::STATUS_COMPLETED)
                    ->whereNotNull('accounting_sales_import_batch_id');
            });
    }

    private function isAssignmentEligible(Order $order): bool
    {
        return in_array($order->status, $this->assignmentStatuses(), true)
            || ($order->status === Order::STATUS_COMPLETED && $order->accounting_sales_import_batch_id !== null);
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
        $orders = Order::with(['customer', 'shipper', 'items', 'accountingReconciliation', 'shippingFeeRequest:id,status,request_title'])
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

        $feeRequestOrders = Order::query()
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
            ->forDeliveryDate($selectedDate)
            ->where('shipping_fee', '>', 0)
            ->get(['id', 'shipping_fee', 'shipping_fee_transaction_id']);
        $feeRequestSummary = [
            'total_orders' => $feeRequestOrders->count(),
            'requested_orders' => $feeRequestOrders->whereNotNull('shipping_fee_transaction_id')->count(),
            'pending_orders' => $feeRequestOrders->whereNull('shipping_fee_transaction_id')->count(),
            'pending_total' => (float) $feeRequestOrders->whereNull('shipping_fee_transaction_id')->sum('shipping_fee'),
        ];

        $view = $request->routeIs('accounting.*')
            ? 'accounting.ship.manage-fees'
            : 'shipper.manage-fees';

        return view($view, compact('orders', 'selectedDate', 'orderReturns', 'feeRequestSummary'));
    }

    /**
     * Update shipping fee for single order
     */
    public function updateFee(Request $request, Order $order)
    {
        $this->authorizeManagerShipper();

        abort_if(
            $order->accountingReconciliation?->status === \App\Models\AccountingReconciliation::STATUS_CONFIRMED
                && $order->accounting_sales_import_batch_id === null,
            422,
            'Kế toán đã xác nhận đơn, không thể điều chỉnh phí ship.'
        );
        abort_if($order->shipping_fee_transaction_id, 422, 'Đơn đã được đưa vào phiếu yêu cầu chi phí ship.');

        $request->validate([
            'shipping_fee' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $oldFee = (float) ($order->shipping_fee ?? 0);
        $newFee = round((float) $request->input('shipping_fee'));

        $order->update([
            'shipping_fee' => $newFee,
        ]);

        // Recalculate order total
        $itemsSubtotal = (float) $order->items->sum(function ($item) {
            return (float) ($item->total ?? (($item->price ?? 0) * ($item->display_total_value ?? 0)));
        });
        $foamBoxFee = (float) (($order->charge_foam_box_fee ?? false) ? ($order->foam_box_price ?? 0) : 0);
        [$newTotal, $vatAmount] = $this->customerOrderTotal($order, $itemsSubtotal, $newFee, $foamBoxFee);

        if ($order->accounting_sales_import_batch_id) {
            $order->accountingReconciliation?->update(['shipping_fee' => $newFee]);
        } else {
            $order->update(['vat_amount' => $vatAmount, 'total' => $newTotal]);
        }

        OrderHistory::create([
            'order_id' => $order->id,
            'action' => 'shipping_fee_updated',
            'user_id' => Auth::id(),
            'role' => 'manager_shipper',
            'note' => 'Cập nhật phí ship từ '.number_format($oldFee, 0, ',', '.').' đ thành '.
                              number_format($newFee, 0, ',', '.').' đ'.
                              ($request->filled('notes') ? ' - '.$request->notes : ''),
        ]);

        $this->syncCustomerShippingFeeHistory($order, $oldFee, $newFee, $request->input('notes'));

        $message = 'Cập nhật phí ship cho đơn #'.($order->code ?: $order->id).' thành công!';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'order_id' => (int) $order->id,
                'shipping_fee' => $newFee,
                'total' => $newTotal,
            ]);
        }

        return back()->with('success', $message);
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
        abort_if($orders->contains(fn (Order $order) => $order->accountingReconciliation?->status === \App\Models\AccountingReconciliation::STATUS_CONFIRMED
            && $order->accounting_sales_import_batch_id === null), 422, 'Danh sách có đơn đã được kế toán xác nhận. Vui lòng bỏ các đơn đã chốt.');
        abort_if($orders->contains(fn (Order $order) => $order->shipping_fee_transaction_id), 422, 'Danh sách có đơn đã được đưa vào phiếu yêu cầu chi phí ship.');
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
                [$newTotal, $vatAmount] = $this->customerOrderTotal($order, $itemsSubtotal, $newFee, $foamBoxFee);

                if ($order->accounting_sales_import_batch_id) {
                    $order->accountingReconciliation?->update(['shipping_fee' => $newFee]);
                } else {
                    $order->update(['vat_amount' => $vatAmount, 'total' => $newTotal]);
                }

                OrderHistory::create([
                    'order_id' => $order->id,
                    'action' => 'shipping_fee_updated',
                    'user_id' => Auth::id(),
                    'role' => 'manager_shipper',
                    'note' => 'Cập nhật hàng loạt phí ship từ '.number_format($oldFee, 0, ',', '.').' đ thành '.
                                      number_format($newFee, 0, ',', '.').' đ'.
                                      (! empty($notes) ? ' - '.$notes : ''),
                ]);

                $this->syncCustomerShippingFeeHistory($order, $oldFee, $newFee, $notes);
            }
        });

        return back()->with('success', 'Cập nhật phí ship cho '.count($orders).' đơn hàng thành công!');
    }

    public function updateReturnFee(Request $request, OrderReturn $orderReturn)
    {
        $this->authorizeManagerShipper();

        $validated = $request->validate([
            'return_shipping_fee' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $oldFee = (float) ($orderReturn->return_shipping_fee ?? 0);
        $newFee = (float) $validated['return_shipping_fee'];

        $orderReturn->update([
            'return_shipping_fee' => $newFee,
            'note' => trim((string) ($orderReturn->note ?? '').(! empty($validated['notes']) ? ' | '.$validated['notes'] : ''), ' |'),
        ]);

        if ($orderReturn->order_id) {
            OrderHistory::create([
                'order_id' => $orderReturn->order_id,
                'action' => 'return_shipping_fee_updated',
                'user_id' => Auth::id(),
                'role' => Auth::user()?->hasRole(['account', 'accountant', 'accounting']) ? 'accounting' : 'manager_shipper',
                'note' => 'Cập nhật phí ship trả về từ '.number_format($oldFee, 0, ',', '.').' đ thành '
                    .number_format($newFee, 0, ',', '.').' đ'
                    .(! empty($validated['notes']) ? ' - '.$validated['notes'] : ''),
            ]);
        }

        return back()->with('success', 'Đã cập nhật phí ship trả về.');
    }

    public function createShippingFeeRequest(Request $request)
    {
        $this->authorizeManagerShipper();

        $validated = $request->validate([
            'selected_date' => ['required', 'date'],
            'request_order_ids' => ['required', 'array', 'min:1'],
            'request_order_ids.*' => ['integer', 'distinct', 'exists:orders,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $selectedDate = Carbon::parse($validated['selected_date'])->toDateString();
        $requestedIds = collect($validated['request_order_ids'])->map(fn ($id) => (int) $id)->values();
        $transaction = null;

        DB::transaction(function () use ($selectedDate, $requestedIds, $validated, &$transaction): void {
            $orders = Order::query()
                ->with(['customer:id,name', 'shipper:id,name'])
                ->whereIn('id', $requestedIds)
                ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
                ->forDeliveryDate($selectedDate)
                ->whereNull('shipping_fee_transaction_id')
                ->where('shipping_fee', '>', 0)
                ->lockForUpdate()
                ->get();

            if ($orders->count() !== $requestedIds->count()) {
                abort(422, 'Có đơn không hợp lệ, chưa giao, phí ship bằng 0 hoặc đã được tạo phiếu. Vui lòng tải lại danh sách.');
            }

            $category = TransactionCategory::query()
                ->where('flow_direction', 'out')
                ->where('is_active', true)
                ->where(function ($query): void {
                    $query->whereIn('code', ['SHIP', 'SHIPPING', 'SHIP_FEE'])
                        ->orWhere('name', 'like', '%ship%')
                        ->orWhere('name', 'like', '%giao hàng%');
                })
                ->orderBy('sort_order')
                ->first();

            $category ??= TransactionCategory::query()->updateOrCreate(
                ['code' => 'SHIP_FEE'],
                [
                    'name' => 'Chi phí giao hàng',
                    'flow_direction' => 'out',
                    'sort_order' => (int) TransactionCategory::query()->max('sort_order') + 1,
                    'is_active' => true,
                ]
            );

            $items = $orders->values()->map(function (Order $order, int $index): array {
                $fee = round((float) $order->shipping_fee, 2);

                return [
                    'stt' => $index + 1,
                    'content' => 'Đơn #'.$order->code.' · '.($order->customer?->name ?? 'Khách hàng').' · Shipper: '.($order->shipper?->name ?? 'Chưa gán'),
                    'unit' => 'đơn',
                    'quantity' => 1,
                    'unit_price' => $fee,
                    'line_total' => $fee,
                ];
            });
            $total = round((float) $items->sum('line_total'), 2);

            $transaction = Transaction::create([
                'amount' => $total,
                'type' => 'extra_expense',
                'transaction_category_id' => $category->id,
                'account_id' => null,
                'note' => $validated['note'] ?? ('Tổng hợp '.$orders->count().' đơn giao ngày '.Carbon::parse($selectedDate)->format('d/m/Y')),
                'status' => Transaction::STATUS_PENDING_APPROVAL,
                'submitted_by' => Auth::id(),
                'request_source' => 'shipper',
                'request_department' => 'Điều phối ship',
                'request_form_type' => Transaction::REQUEST_FORM_PAYMENT,
                'request_title' => 'Chi phí ship ngày '.Carbon::parse($selectedDate)->format('d/m/Y'),
                'request_items' => $items->all(),
                'request_subtotal' => $total,
                'request_vat' => 0,
                'request_total' => $total,
            ]);

            Order::query()->whereIn('id', $orders->pluck('id'))->update([
                'shipping_fee_transaction_id' => $transaction->id,
            ]);

            app(\App\Services\ApprovalService::class)->initTransactionApproval($transaction);
        });

        return redirect()->route('shipper.finance-requests.index')
            ->with('success', 'Đã tạo phiếu yêu cầu chi phí ship #'.$transaction->id.' từ '.$requestedIds->count().' đơn.');
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

        $reportMode = in_array($request->input('mode'), ['day', 'range'], true)
            ? $request->input('mode')
            : 'day';
        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : Carbon::today()->toDateString();
        $fromDate = $reportMode === 'day'
            ? $selectedDate
            : ($request->filled('from_date') ? Carbon::parse($request->input('from_date'))->toDateString() : Carbon::today()->startOfMonth()->toDateString());
        $toDate = $reportMode === 'day'
            ? $selectedDate
            : ($request->filled('to_date') ? Carbon::parse($request->input('to_date'))->toDateString() : Carbon::today()->toDateString());

        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $dailyOrders = Order::query()
            ->with(['customer', 'shipper', 'shippingFeeRequest:id,status,request_title'])
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
            ->whereDate('delivery_date', '>=', $fromDate)
            ->whereDate('delivery_date', '<=', $toDate)
            ->orderByDesc('delivery_date')
            ->orderByDesc('updated_at')
            ->get();

        $dailySummary = [
            'total_orders' => $dailyOrders->count(),
            'total_ship_fee' => (float) $dailyOrders->sum('shipping_fee'),
            'customers' => $dailyOrders->pluck('customer_id')->filter()->unique()->count(),
            'shippers' => $dailyOrders->pluck('shipper_id')->filter()->unique()->count(),
            'requested_orders' => $dailyOrders->whereNotNull('shipping_fee_transaction_id')->count(),
            'requested_fee' => (float) $dailyOrders->whereNotNull('shipping_fee_transaction_id')->sum('shipping_fee'),
            'pending_orders' => $dailyOrders->whereNull('shipping_fee_transaction_id')->count(),
            'pending_fee' => (float) $dailyOrders->whereNull('shipping_fee_transaction_id')->sum('shipping_fee'),
        ];

        $dailyBreakdown = $dailyOrders
            ->groupBy(fn (Order $order) => optional($order->delivery_date)->toDateString() ?: $order->updated_at->toDateString())
            ->map(function ($orders, string $date): array {
                return [
                    'date' => Carbon::parse($date),
                    'orders' => $orders->count(),
                    'shippers' => $orders->pluck('shipper_id')->filter()->unique()->count(),
                    'total_fee' => (float) $orders->sum('shipping_fee'),
                    'requested_fee' => (float) $orders->whereNotNull('shipping_fee_transaction_id')->sum('shipping_fee'),
                    'pending_fee' => (float) $orders->whereNull('shipping_fee_transaction_id')->sum('shipping_fee'),
                ];
            })
            ->sortByDesc('date')
            ->values();

        $shipperBreakdown = $dailyOrders
            ->groupBy(fn (Order $order) => (int) ($order->shipper_id ?? 0))
            ->map(function ($orders): array {
                return [
                    'shipper' => $orders->first()?->shipper,
                    'orders' => $orders->count(),
                    'total_fee' => (float) $orders->sum('shipping_fee'),
                    'requested_orders' => $orders->whereNotNull('shipping_fee_transaction_id')->count(),
                    'requested_fee' => (float) $orders->whereNotNull('shipping_fee_transaction_id')->sum('shipping_fee'),
                    'pending_orders' => $orders->whereNull('shipping_fee_transaction_id')->count(),
                    'pending_fee' => (float) $orders->whereNull('shipping_fee_transaction_id')->sum('shipping_fee'),
                ];
            })
            ->sortByDesc('total_fee')
            ->values();

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
            'reportMode',
            'selectedDate',
            'fromDate',
            'toDate',
            'dailyOrders',
            'dailySummary',
            'dailyBreakdown',
            'shipperBreakdown',
            'feeChanges'
        ));
    }

    private function syncCustomerShippingFeeHistory(Order $order, float $oldFee, float $newFee, ?string $note = null): void
    {
        $customer = $order->customer;
        if (! $customer || abs($oldFee - $newFee) < 0.00001) {
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

    /**
     * Tính số tiền khách phải trả. shipping_fee chỉ được cộng khi đơn đánh dấu
     * thu khoản phí nội bộ đó; customer_shipping_fee luôn là khoản thu khách riêng.
     *
     * @return array{0: float, 1: float}
     */
    private function customerOrderTotal(Order $order, float $itemsSubtotal, float $assignedShippingFee, float $foamBoxFee): array
    {
        $orderAdjustment = (float) ($order->extra_discount_total ?? 0);
        $productTotal = max(0, $itemsSubtotal - $orderAdjustment);
        $vatAmount = $order->resolvedVatAmount($productTotal);
        $customerShippingFee = (bool) ($order->collect_customer_shipping_fee ?? false)
            ? max(0, (float) ($order->customer_shipping_fee ?? 0))
            : 0.0;
        $billableAssignedShippingFee = (bool) ($order->charge_shipping_fee ?? false)
            ? max(0, $assignedShippingFee)
            : 0.0;

        return [
            $productTotal + $vatAmount + $customerShippingFee + $billableAssignedShippingFee + max(0, $foamBoxFee),
            $vatAmount,
        ];
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

        return back()->with('success', 'Đã cập nhật phí ship mặc định cho khách hàng '.$customer->name.'.');
    }

    protected function authorizeManagerShipper(): void
    {
        if (! Auth::user()->hasRole(['manager_shipper', 'account', 'accountant', 'accounting', 'admin'])) {
            abort(403, 'Bạn không có quyền truy cập tính năng này.');
        }
    }
}
