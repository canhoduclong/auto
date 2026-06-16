<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\WarehouseDashboardController;
use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryDocumentItem;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\OrderReturn;
use App\Models\ProductVariant;
use App\Models\TaskAssignment;
use App\Models\WarehouseInventoryTransfer;
use App\Models\WarehouseTransfer;
use App\Models\Warehouse;
use App\Services\WarehouseInventorySummaryService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WarehouseApiController extends BaseApiController
{
    public function dashboard(Request $request): JsonResponse
    {
        $this->ensureWarehouseRole($request);
        $user = $request->user();
        $warehouseId = $user->warehouse_id ? (int) $user->warehouse_id : null;
        $date = $request->filled('date')
            ? Carbon::parse($request->query('date'))->toDateString()
            : now()->toDateString();
        $readyToPackStatuses = ['approved', Order::STATUS_READY_TO_PACK];
        $packedStatuses = ['packed', Order::STATUS_READY_TO_SHIP];

        $applyWarehouseScope = function ($query) use ($warehouseId, $user, $readyToPackStatuses) {
            if ($warehouseId && $user?->hasRole('warehouse')) {
                $query->where(function ($warehouseScope) use ($warehouseId, $readyToPackStatuses) {
                    $warehouseScope->where('warehouse_id', $warehouseId)
                        ->orWhere(function ($sharedScope) use ($readyToPackStatuses) {
                            $sharedScope->whereNull('warehouse_id')
                                ->whereIn('status', array_merge($readyToPackStatuses, [Order::STATUS_PACKING]));
                        });
                });
            }

            return $query;
        };

        $dailyOrdersQuery = Order::query()
            ->with('customer:id,name,phone,address')
            ->whereDate('created_at', $date);

        $applyWarehouseScope($dailyOrdersQuery);

        $stats = [
            'ready_to_pack' => $applyWarehouseScope(Order::query())
                ->whereIn('status', $readyToPackStatuses)
                ->whereDate('created_at', $date)
                ->count(),
            'packed' => $applyWarehouseScope(Order::query())
                ->whereIn('status', $packedStatuses)
                ->whereDate('updated_at', $date)
                ->count(),
            'packing' => $applyWarehouseScope(Order::query())
                ->where('status', Order::STATUS_PACKING)
                ->whereDate('created_at', $date)
                ->count(),
            'returning' => $applyWarehouseScope(Order::query())
                ->where('status', Order::STATUS_RETURNING)
                ->whereDate('created_at', $date)
                ->count(),
            'returned' => 0,
            'assigned_tasks' => 0,
            'completed_tasks' => 0,
            'transfers_incoming' => WarehouseTransfer::query()
                ->where('target_warehouse_id', $warehouseId)
                ->where('status', WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE)
                ->count(),
            'transfers_completed' => WarehouseTransfer::query()
                ->where('target_warehouse_id', $warehouseId)
                ->where('status', WarehouseTransfer::STATUS_RECEIVED_COMPLETED)
                ->whereDate('updated_at', $date)
                ->count(),
            'done_today' => $applyWarehouseScope(Order::query())
                ->whereIn('status', $packedStatuses)
                ->whereDate('updated_at', $date)
                ->count(),
            'orders_in_day' => (clone $dailyOrdersQuery)->count(),
            'receiving' => WarehouseInventoryTransfer::query()
                ->where('target_warehouse_id', $warehouseId)
                ->where('status', WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE)
                ->count(),
            'received' => WarehouseInventoryTransfer::query()
                ->where('target_warehouse_id', $warehouseId)
                ->where('status', WarehouseInventoryTransfer::STATUS_RECEIVED_COMPLETED)
                ->whereDate('updated_at', $date)
                ->count(),
        ];

        $tasks = collect([
            [
                'key' => 'orders',
                'label' => 'Đơn cần đóng gói',
                'total' => ($stats['ready_to_pack'] ?? 0) + ($stats['packing'] ?? 0) + ($stats['packed'] ?? 0),
                'done' => $stats['packed'] ?? 0,
                'route_key' => 'orders',
            ],
            [
                'key' => 'incoming_transfers',
                'label' => 'Tiếp nhận Đơn',
                'total' => ($stats['transfers_incoming'] ?? 0) + ($stats['transfers_completed'] ?? 0),
                'done' => $stats['transfers_completed'] ?? 0,
                'route_key' => 'incoming_transfers',
            ],
            [
                'key' => 'incoming_inventory_transfers',
                'label' => 'Tiếp nhận hàng',
                'total' => ($stats['receiving'] ?? 0) + ($stats['received'] ?? 0),
                'done' => $stats['received'] ?? 0,
                'route_key' => 'incoming_inventory_transfers',
            ],
            [
                'key' => 'returns',
                'label' => 'Tiếp nhận đơn hoàn trả',
                'total' => ($stats['returning'] ?? 0) + ($stats['returned'] ?? 0),
                'done' => $stats['returned'] ?? 0,
                'route_key' => 'returns',
            ],
            [
                'key' => 'tasks',
                'label' => 'Nhiệm vụ được giao',
                'total' => ($stats['assigned_tasks'] ?? 0) + ($stats['completed_tasks'] ?? 0),
                'done' => $stats['completed_tasks'] ?? 0,
                'route_key' => 'tasks',
            ],
        ])->map(function (array $task, int $index) {
            $total = (int) $task['total'];
            $done = (int) $task['done'];
            $status = $this->dashboardTaskStatus($total, $done);
            $percent = $total > 0 ? (int) round($done / $total * 100) : 0;

            return array_merge($task, [
                'sequence' => $index + 1,
                'total' => $total,
                'done' => $done,
                'percent' => $percent,
                'status' => $status,
                'status_label' => match ($status) {
                    'todo' => 'Cần làm',
                    'inprogress' => 'Đang làm',
                    'done' => 'Đã hoàn thành',
                    default => 'Chưa có nhiệm vụ',
                },
                'color' => match ($status) {
                    'todo' => '#dc3545',
                    'inprogress' => '#f59e42',
                    'done' => '#198754',
                    default => '#b0b0b0',
                },
            ]);
        })->values();

        $inventorySummaryService = app(WarehouseInventorySummaryService::class);
        $currentInventorySummary = $inventorySummaryService->build($warehouseId);
        $summaryRows = $currentInventorySummary['rows']
            ->filter(fn ($row) => (int) $row['closing'] > 0)
            ->map(function ($row) {
                $row['variants'] = $row['variants']->filter(fn ($variant) => (int) $variant['closing'] > 0)->values();

                return $row;
            })
            ->values();
        $otherWarehouseSummaries = $warehouseId
            ? Warehouse::query()
                ->whereKeyNot($warehouseId)
                ->where('status', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(function (Warehouse $warehouse) use ($inventorySummaryService) {
                    $summary = $inventorySummaryService->build((int) $warehouse->id);
                    $rows = $summary['rows']
                        ->filter(fn ($row) => (int) $row['closing'] > 0)
                        ->map(function ($row) {
                            $row['variants'] = $row['variants']->filter(fn ($variant) => (int) $variant['closing'] > 0)->values();

                            return $row;
                        })
                        ->values();

                    return [
                        'warehouse_id' => (int) $warehouse->id,
                        'warehouse_name' => (string) $warehouse->name,
                        'title' => 'Tồn kho của kho khác (' . $warehouse->name . ')',
                        'totals' => $summary['totals'],
                        'rows' => $rows,
                    ];
                })
                ->filter(fn ($summary) => $summary['rows']->isNotEmpty())
                ->values()
            : collect();

        $recentPacked = $applyWarehouseScope(Order::with('customer:id,name'))
            ->whereIn('status', $packedStatuses)
            ->whereDate('updated_at', $date)
            ->orderByDesc('updated_at')
            ->take(5)
            ->get()
            ->map(fn (Order $order, int $index) => [
                'sequence' => $index + 1,
                'id' => (int) $order->id,
                'code' => (string) ($order->code ?? ('#' . $order->id)),
                'customer_name' => (string) ($order->customer?->name ?? '—'),
                'total' => (float) ($order->total ?? 0),
                'updated_time' => optional($order->updated_at)->format('H:i'),
                'updated_at' => optional($order->updated_at)->toIso8601String(),
            ])
            ->values();

        $reminders = $tasks
            ->filter(fn ($task) => (int) $task['total'] > 0 && (int) $task['done'] < (int) $task['total'])
            ->map(fn ($task) => [
                'label' => $task['label'],
                'percent' => $task['percent'],
                'message' => ((int) $task['done'] === 0) ? 'Hãy bắt đầu ngay!' : 'Hãy tiếp tục...',
            ])
            ->values();

        return $this->ok([
            'selected_date' => $date,
            'stats' => $stats,
            'tasks' => $tasks,
            'receiving_alert' => [
                'show' => ($stats['receiving'] ?? 0) > 0,
                'count' => $stats['receiving'] ?? 0,
                'message' => 'Cần tiếp nhận hàng: Hiện có ' . ($stats['receiving'] ?? 0) . ' phiếu điều chuyển chờ tiếp nhận!',
                'route_key' => 'incoming_inventory_transfers',
            ],
            'legend' => [
                ['label' => 'Cần làm', 'color' => '#dc3545'],
                ['label' => 'Đang làm', 'color' => '#f59e42'],
                ['label' => 'Đã hoàn thành', 'color' => '#198754'],
                ['label' => 'Chưa có nhiệm vụ', 'color' => '#b0b0b0'],
            ],
            'work_reminders' => $reminders,
            'changes' => [
                ['icon' => 'edit', 'label' => 'Yêu cầu thay đổi đơn hàng từ sale', 'badge' => 'Mới', 'color' => '#0d6efd', 'badge_color' => '#0dcaf0'],
                ['icon' => 'chat', 'label' => 'Sale trả lời khách hàng', 'badge' => 'Đã trả lời', 'color' => '#198754', 'badge_color' => '#198754'],
                ['icon' => 'truck', 'label' => 'Phiếu điều chuyển kho chờ ship nhận', 'badge' => 'Chờ ship', 'color' => '#ffc107', 'badge_color' => '#ffc107'],
            ],
            'inventory_summary' => [
                'title' => 'Danh sách thống kê tồn kho (sản phẩm và biến thể cùng một cấu trúc cột)',
                'totals' => [
                    'opening' => (int) $summaryRows->sum('opening'),
                    'import' => (int) $summaryRows->sum('import'),
                    'reserved' => (int) $summaryRows->sum('reserved'),
                    'export' => (int) $summaryRows->sum('export'),
                    'closing' => (int) $summaryRows->sum('closing'),
                ],
                'rows' => $summaryRows,
            ],
            'other_warehouse_summaries' => $otherWarehouseSummaries,
            'recent_packed' => $recentPacked,
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        $this->ensurePackingRole($request);
        $status = (string) $request->query('status', '');
        $date = (string) $request->query('date', now()->toDateString());
        $user = $request->user();
        $warehouseId = $user->warehouse_id ? (int) $user->warehouse_id : null;
        $sharedQueueStatuses = ['approved', Order::STATUS_READY_TO_PACK, Order::STATUS_PACKING];

        $query = Order::query()
            ->with([
                'customer:id,name,phone,address,delivery_time',
                'warehouse:id,name',
                'histories:id,order_id,action,user_id',
                'items.product:id,name,unit',
                'items.variant' => fn ($q) => $q->withAvailableStock()->with('product:id,name,unit'),
            ])
            ->where(function ($q) {
                $q->whereNull('is_return_order')->orWhere('is_return_order', false);
            })
            ->whereDate('created_at', $date)
            ->when($warehouseId && $user->hasRole('warehouse'), function ($q) use ($warehouseId, $sharedQueueStatuses) {
                $q->where(function ($warehouseScope) use ($warehouseId, $sharedQueueStatuses) {
                    $warehouseScope->where('warehouse_id', $warehouseId)
                        ->orWhere(function ($sharedScope) use ($sharedQueueStatuses) {
                            $sharedScope->whereNull('warehouse_id')
                                ->whereIn('status', $sharedQueueStatuses);
                        });
                });
            });

        if ($status !== '') {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', ['approved', Order::STATUS_READY_TO_PACK, Order::STATUS_PACKING, 'packed', Order::STATUS_READY_TO_SHIP]);
        }

        $orders = $query
            ->orderByRaw('CASE WHEN daily_sequence IS NULL THEN 1 ELSE 0 END')
            ->orderBy('daily_sequence')
            ->orderBy('created_at')
            ->orderBy('id')
            ->paginate(50);

        $this->attachCustomerFeedbackContext($orders->getCollection());
        $orders->getCollection()->transform(fn (Order $order) => $this->warehouseOrderPayload($order));

        return $this->paginated($orders);
    }

    public function startPacking(Request $request, Order $order): JsonResponse
    {
        $this->ensurePackingRole($request);

        return $this->callWebWarehouseAction($request, fn () => app(WarehouseDashboardController::class)->startPacking($request, $order));
    }

    public function completePacking(Request $request, Order $order): JsonResponse
    {
        $this->ensurePackingRole($request);

        return $this->callWebWarehouseAction($request, fn () => app(WarehouseDashboardController::class)->completePacking($request, $order));
    }

    public function undoStartPacking(Request $request, Order $order): JsonResponse
    {
        $this->ensurePackingRole($request);

        return $this->callWebWarehouseAction($request, fn () => app(WarehouseDashboardController::class)->returnToReadyToPack($request, $order));
    }

    public function updateLogistics(Request $request, Order $order): JsonResponse
    {
        $this->ensurePackingRole($request);

        return $this->callWebWarehouseAction($request, fn () => app(WarehouseDashboardController::class)->updateLogistics($request, $order));
    }

    public function requestAdjustment(Request $request, Order $order): JsonResponse
    {
        $this->ensurePackingRole($request);

        return $this->callWebWarehouseAction($request, fn () => app(WarehouseDashboardController::class)->requestAdjustment($request, $order));
    }

    public function inventory(Request $request): JsonResponse
    {
        $this->ensureWarehouseRole($request);
        $date = $request->filled('date')
            ? Carbon::parse($request->query('date'))->toDateString()
            : now()->toDateString();
        $summary = app(WarehouseInventorySummaryService::class)->buildConsolidated(
            $date,
            trim((string) $request->query('search', '')),
            $request->query('status')
        );

        return $this->ok([
            'selected_date' => $summary['selectedDate'],
            'warehouses' => $summary['warehouses']->map(fn (Warehouse $warehouse) => [
                'id' => (int) $warehouse->id,
                'name' => (string) $warehouse->name,
            ])->values(),
            'rows' => $summary['rows'],
            'totals' => $summary['totals'],
        ]);
    }

    public function returns(Request $request): JsonResponse
    {
        $this->ensureWarehouseRole($request);
        $warehouseId = $request->user()->warehouse_id ? (int) $request->user()->warehouse_id : null;

        $query = OrderReturn::query()
            ->with(['order:id,code,status', 'customer:id,name,phone', 'warehouse:id,name'])
            ->latest('updated_at');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $this->paginated($query->paginate(20));
    }

    public function receiveReturn(Request $request, OrderReturn $orderReturn): JsonResponse
    {
        $this->ensureWarehouseRole($request);
        $user = $request->user();
        $warehouseId = (int) ($orderReturn->warehouse_id ?? 0);
        if ($user->warehouse_id && (int) $user->warehouse_id !== $warehouseId) {
            return $this->fail('Phieu tra khong thuoc kho ban quan ly.', 403);
        }
        $orderReturn->loadMissing(['order.items', 'returnItems']);
        if (!$orderReturn->order || $orderReturn->returnItems->isEmpty() || $warehouseId <= 0) {
            return $this->fail('Phieu tra thieu thong tin don hang, san pham hoac kho nhan.', 422);
        }

        $document = DB::transaction(function () use ($orderReturn, $user, $warehouseId) {
            $lockedReturn = OrderReturn::query()->lockForUpdate()->findOrFail($orderReturn->id);
            if ($lockedReturn->status === 'warehouse_received') {
                abort(422, 'Phieu tra da duoc nhap kho.');
            }

            $orderReturn->update([
                'status' => 'warehouse_received',
                'warehouse_confirmed_by' => $user->id,
                'warehouse_confirmed_at' => now(),
            ]);
            $orderReturn->order->update(['status' => Order::STATUS_RETURNED_COMPLETED]);

            $marker = '[return_receipt:' . $orderReturn->id . ']';
            $document = InventoryDocument::query()->firstOrCreate(
                [
                    'type' => 'import',
                    'warehouse_id' => $warehouseId,
                    'notes' => 'Đơn nhập hàng từ trả hàng #' . $orderReturn->id . ' ' . $marker,
                ],
                [
                    'document_date' => now()->toDateString(),
                    'shipping_fee' => 0,
                    'user_id' => $user->id,
                ]
            );

            foreach ($orderReturn->returnItems as $returnItem) {
                $quantity = (int) $returnItem->quantity;
                $orderItem = $orderReturn->order->items->firstWhere('product_variant_id', $returnItem->product_variant_id);
                InventoryDocumentItem::query()->updateOrCreate(
                    [
                        'inventory_document_id' => $document->id,
                        'product_variant_id' => $returnItem->product_variant_id,
                    ],
                    [
                        'quantity' => $quantity,
                        'unit_cost' => (float) ($orderItem?->price ?? 0),
                    ]
                );
                $inventory = Inventory::query()->firstOrCreate(
                    ['product_variant_id' => $returnItem->product_variant_id, 'warehouse_id' => $warehouseId],
                    ['quantity' => 0, 'reserved_quantity' => 0]
                );
                $inventory->increment('quantity', $quantity);
            }

            OrderHistory::query()->create([
                'order_id' => $orderReturn->order_id,
                'action' => 'confirm_return',
                'user_id' => $user->id,
                'role' => 'warehouse',
                'status_before' => Order::STATUS_RETURNING,
                'status_after' => Order::STATUS_RETURNED_COMPLETED,
                'note' => 'Kho nhận hàng trả qua mobile, cập nhật tồn kho và tạo phiếu nhập #' . $document->id,
            ]);

            return $document;
        });

        return $this->ok([
            'return_id' => (int) $orderReturn->id,
            'inventory_document_id' => (int) $document->id,
        ], 'Da nhan hang tra va tao phieu nhap kho');
    }

    public function tasks(Request $request): JsonResponse
    {
        $this->ensureWarehouseRole($request);
        $user = $request->user();

        $tasks = TaskAssignment::query()
            ->with(['creator:id,name', 'assignees.user:id,name'])
            ->where(function ($query) use ($user) {
                $query->where('created_by', $user->id)
                    ->orWhereHas('assignees', fn ($q) => $q->where('user_id', $user->id));
            })
            ->latest('updated_at')
            ->paginate(20);

        return $this->paginated($tasks);
    }

    public function products(Request $request): JsonResponse
    {
        $this->ensurePackingRole($request);
        $query = ProductVariant::query()->with('product:id,name')->latest('id');

        if ($request->filled('keyword')) {
            $keyword = (string) $request->query('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('sku', 'like', '%' . $keyword . '%');
            });
        }

        return $this->paginated($query->paginate(30));
    }

    public function scanLookup(Request $request): JsonResponse
    {
        $this->ensureWarehouseRole($request);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:120'],
        ]);

        $code = trim((string) $validated['code']);
        $variant = ProductVariant::query()
            ->with('product:id,name')
            ->where('sku', $code)
            ->orWhere('name', 'like', '%' . $code . '%')
            ->first();

        if (!$variant) {
            return $this->fail('Khong tim thay san pham theo ma scan', 404);
        }

        return $this->ok([
            'id' => (int) $variant->id,
            'sku' => (string) ($variant->sku ?? ''),
            'name' => (string) ($variant->name ?? ''),
            'product_name' => (string) ($variant->product?->name ?? ''),
            'is_priced_by_kg' => (bool) $variant->effective_priced_by_kg,
            'kg' => (float) $variant->effective_kg,
        ]);
    }

    public function notifications(Request $request): JsonResponse
    {
        $this->ensureWarehouseRole($request);
        $limit = min(50, max(1, (int) $request->query('limit', 20)));

        $items = $request->user()
            ->notifications()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($n) => [
                'id' => (string) $n->id,
                'title' => (string) ($n->data['title'] ?? 'Thong bao'),
                'message' => (string) ($n->data['message'] ?? ''),
                'read_at' => optional($n->read_at)->toIso8601String(),
                'created_at' => optional($n->created_at)->toIso8601String(),
            ])
            ->values();

        return $this->ok($items);
    }

    private function ensureWarehouseRole(Request $request): void
    {
        $user = $request->user();
        if (!$user || !($user->hasRole('warehouse') || $user->hasRole('admin'))) {
            abort(403, 'Role khong duoc phep truy cap API warehouse');
        }
    }

    private function ensurePackingRole(Request $request): void
    {
        $user = $request->user();
        if (!$user || !($user->hasRole('warehouse') || $user->hasRole('package') || $user->hasRole('admin'))) {
            abort(403, 'Role khong duoc phep truy cap API dong hang');
        }
    }

    private function dashboardTaskStatus(int $total, int $done): string
    {
        if ($total === 0) {
            return 'none';
        }

        if ($done === 0) {
            return 'todo';
        }

        if ($done < $total) {
            return 'inprogress';
        }

        return 'done';
    }

    private function warehouseOrderPayload(Order $order): array
    {
        $statusMeta = $this->warehouseStatusMeta((string) $order->status);
        $items = $order->items->map(fn ($item) => $this->warehouseOrderItemPayload($item))->values();
        $activePackingHistory = $order->histories
            ->where('action', 'start_packing')
            ->sortByDesc('id')
            ->first();

        return [
            'id' => (int) $order->id,
            'code' => (string) ($order->code ?: '#' . $order->id),
            'daily_sequence' => $order->daily_sequence ? (int) $order->daily_sequence : null,
            'status' => (string) $order->status,
            'status_label' => $statusMeta['label'],
            'status_color' => $statusMeta['color'],
            'priority_state' => $this->priorityState((string) $order->status),
            'customer' => [
                'name' => (string) ($order->customer?->name ?? '—'),
                'phone' => (string) ($order->customer?->phone ?? ''),
                'address' => (string) ($order->customer?->address ?? ''),
            ],
            'shipping_address' => (string) ($order->recipient_address ?: $order->customer?->address ?: ''),
            'delivery_time' => (string) ($order->delivery_time ?: $order->customer?->delivery_time ?: ''),
            'delivery_date' => optional($order->delivery_date)->toDateString(),
            'created_at' => optional($order->created_at)->toIso8601String(),
            'updated_at' => optional($order->updated_at)->toIso8601String(),
            'total' => (float) ($order->total ?? 0),
            'actual_weight' => $order->actual_weight === null ? null : (float) $order->actual_weight,
            'shipping_fee' => $order->shipping_fee === null ? null : (float) $order->shipping_fee,
            'foam_box_price' => $order->foam_box_price === null ? null : (float) $order->foam_box_price,
            'charge_shipping_fee' => (bool) ($order->charge_shipping_fee ?? true),
            'charge_foam_box_fee' => (bool) ($order->charge_foam_box_fee ?? false),
            'warehouse_adjustment_status' => (string) ($order->warehouse_adjustment_status ?? Order::WAREHOUSE_ADJUSTMENT_STATUS_NONE),
            'warehouse_adjustment_note' => (string) ($order->warehouse_adjustment_note ?? ''),
            'warehouse_adjustment_rejected_reason' => (string) ($order->warehouse_adjustment_rejected_reason ?? ''),
            'warehouse_adjustment_changes' => $order->warehouse_adjustment_changes ?? [],
            'warehouse_can_adjust' => (bool) ($order->warehouse_can_adjust ?? false),
            'customer_feedback_context' => $order->getAttribute('customer_feedback_context') ?? [
                'has_feedback' => false,
                'highest_status' => null,
                'highest_meta' => Order::customerFeedbackMeta(null),
                'recent' => [],
            ],
            'can_start_packing' => in_array((string) $order->status, ['approved', Order::STATUS_READY_TO_PACK], true)
                && $order->warehouse_adjustment_status !== Order::WAREHOUSE_ADJUSTMENT_STATUS_PENDING_SALE_CONFIRMATION
                && $order->warehouse_adjustment_status !== Order::WAREHOUSE_ADJUSTMENT_STATUS_SALE_REJECTED,
            'can_complete_packing' => (string) $order->status === Order::STATUS_PACKING
                && $order->warehouse_adjustment_status !== Order::WAREHOUSE_ADJUSTMENT_STATUS_PENDING_SALE_CONFIRMATION
                && $order->warehouse_adjustment_status !== Order::WAREHOUSE_ADJUSTMENT_STATUS_SALE_REJECTED,
            'can_undo_start_packing' => (string) $order->status === Order::STATUS_PACKING
                && (int) ($activePackingHistory?->user_id ?? 0) === (int) Auth::id(),
            'can_request_adjustment' => in_array((string) $order->status, ['approved', Order::STATUS_READY_TO_PACK], true)
                && $order->created_at?->isToday(),
            'items' => $items,
        ];
    }

    private function attachCustomerFeedbackContext($orders): void
    {
        if ($orders->isEmpty() || !Schema::hasColumn('orders', 'customer_feedback_status')) {
            $orders->each(fn (Order $order) => $order->setAttribute('customer_feedback_context', [
                'has_feedback' => false,
                'highest_status' => null,
                'highest_meta' => Order::customerFeedbackMeta(null),
                'recent' => [],
            ]));

            return;
        }

        $customerIds = $orders->pluck('customer_id')->filter()->unique()->values();
        $feedbackByCustomer = Order::query()
            ->with(['customerFeedbackUser:id,name'])
            ->whereIn('customer_id', $customerIds)
            ->whereNotNull('customer_feedback_status')
            ->whereNotNull('customer_feedback_note')
            ->latest('customer_feedback_at')
            ->latest('updated_at')
            ->get()
            ->groupBy('customer_id');

        $orders->each(function (Order $order) use ($feedbackByCustomer): void {
            $rows = $feedbackByCustomer->get($order->customer_id, collect())->take(5);
            $highestStatus = $rows
                ->map(fn (Order $feedbackOrder) => (string) $feedbackOrder->customer_feedback_status)
                ->sortByDesc(fn (string $status) => Order::customerFeedbackMeta($status)['level'] ?? 0)
                ->first();

            $order->setAttribute('customer_feedback_context', [
                'has_feedback' => $rows->isNotEmpty(),
                'highest_status' => $highestStatus,
                'highest_meta' => Order::customerFeedbackMeta($highestStatus),
                'recent' => $rows->map(fn (Order $feedbackOrder) => [
                    'order_id' => (int) $feedbackOrder->id,
                    'code' => (string) ($feedbackOrder->code ?: '#' . $feedbackOrder->id),
                    'status' => (string) $feedbackOrder->customer_feedback_status,
                    'meta' => Order::customerFeedbackMeta((string) $feedbackOrder->customer_feedback_status),
                    'note' => (string) $feedbackOrder->customer_feedback_note,
                    'sale_review' => (string) ($feedbackOrder->customer_feedback_sale_review ?? ''),
                    'user' => (string) ($feedbackOrder->customerFeedbackUser?->name ?? ''),
                    'at' => optional($feedbackOrder->customer_feedback_at ?? $feedbackOrder->updated_at)->toIso8601String(),
                ])->values()->all(),
            ]);
        });
    }

    private function warehouseOrderItemPayload($item): array
    {
        $variant = $item->variant;
        $product = $item->product ?: $variant?->product;
        $quantity = (float) ($item->quantity ?? 0);
        $unitPrice = (float) ($item->price ?? 0);
        $pricedByKg = (bool) $item->effective_priced_by_kg;
        $actualWeight = $item->actual_weight === null ? null : (float) $item->actual_weight;
        $weight = $actualWeight ?? round((float) $item->effective_unit_weight * $quantity, 3);
        $lineTotal = $pricedByKg
            ? ($actualWeight === null ? null : $actualWeight * $unitPrice)
            : $quantity * $unitPrice;

        return [
            'id' => (int) $item->id,
            'product_variant_id' => (int) ($item->product_variant_id ?? 0),
            'product_name' => (string) ($variant?->name ?? $product?->name ?? 'Sản phẩm'),
            'sku' => (string) ($variant?->sku ?? ''),
            'size' => (string) ($variant?->size ?? ''),
            'quantity' => $quantity,
            'display_total' => (string) $item->display_total_label,
            'weight' => $weight,
            'actual_weight' => $actualWeight,
            'weight_label' => $this->formatWeight($weight, $pricedByKg ? 'kg' : ($product?->unit_label ?? 'Cái')),
            'unit_price' => $unitPrice,
            'line_total' => $lineTotal,
            'is_priced_by_kg' => $pricedByKg,
            'available_stock' => (int) ($variant?->available_stock ?? 0),
        ];
    }

    private function warehouseStatusMeta(string $status): array
    {
        return match ($status) {
            'approved', Order::STATUS_READY_TO_PACK => ['label' => 'Chờ đóng gói', 'color' => 'gray'],
            Order::STATUS_PACKING => ['label' => 'Đang đóng', 'color' => 'amber'],
            'packed' => ['label' => 'Đã hoàn thành đóng hàng', 'color' => 'green'],
            Order::STATUS_READY_TO_SHIP => ['label' => 'Đã hoàn thành đóng hàng', 'color' => 'green'],
            Order::STATUS_DELIVERING => ['label' => 'Đang giao', 'color' => 'green'],
            Order::STATUS_DELIVERED => ['label' => 'Đã giao', 'color' => 'green'],
            Order::STATUS_COMPLETED => ['label' => 'Hoàn thành', 'color' => 'green'],
            'pending' => ['label' => 'Chờ duyệt', 'color' => 'gray'],
            'pending_leader_approval' => ['label' => 'Chờ trưởng nhóm duyệt', 'color' => 'gray'],
            'pending_manager_approval' => ['label' => 'Chờ quản lý duyệt', 'color' => 'gray'],
            'pending_warehouse_approval' => ['label' => 'Chờ kho duyệt', 'color' => 'gray'],
            'rejected' => ['label' => 'Từ chối', 'color' => 'red'],
            default => ['label' => $status, 'color' => 'gray'],
        };
    }

    private function priorityState(string $status): string
    {
        if ($status === Order::STATUS_PACKING) {
            return 'packing';
        }

        if (in_array($status, ['packed', Order::STATUS_READY_TO_SHIP, Order::STATUS_DELIVERING, Order::STATUS_DELIVERED, Order::STATUS_COMPLETED], true)) {
            return 'packed';
        }

        return 'unpacked';
    }

    private function formatWeight(float $value, string $unit): string
    {
        $formatted = rtrim(rtrim(number_format($value, 3, ',', '.'), '0'), ',');
        return $formatted . ' ' . $unit;
    }

    private function callWebWarehouseAction(Request $request, callable $callback): JsonResponse
    {
        Auth::setUser($request->user());
        $request->headers->set('Accept', 'application/json');

        $response = $callback();
        if ($response instanceof JsonResponse) {
            $payload = $response->getData(true);
            if (($payload['ok'] ?? true) === false) {
                return $this->fail((string) ($payload['message'] ?? 'Thao tac that bai'), $response->getStatusCode(), $payload);
            }

            return $this->ok($payload, (string) ($payload['message'] ?? 'OK'));
        }

        return $this->ok(null, 'Thao tac thanh cong');
    }
}
