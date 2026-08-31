<?php

namespace App\Http\Controllers;

use App\Models\CuttingComponentImportRequest;
use App\Models\GoogleSheetInventorySync;
use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryDocumentItem;
use App\Models\InventoryDocumentTemplate;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\InventoryStocktake;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\OrderItemPackingSizeAllocation;
use App\Models\OrderReturn;
use App\Models\ProcurementPurchase;
use App\Models\ProcurementPurchaseItem;
use App\Models\Product;
use App\Models\ProductCuttingBatch;
use App\Models\ProductVariant;
use App\Models\ReturnItem;
use App\Models\Setting;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseInventoryTransfer;
use App\Models\WarehouseInventoryTransferItem;
use App\Models\WarehouseTransfer;
use App\Notifications\WarehouseOrderAdjustmentRequested;
use App\Services\ProductCuttingService;
use App\Services\WarehouseInventorySummaryService;
use App\Services\WarehouseOrderAdjustmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WarehouseDashboardController extends Controller
{
    /**
     * Trang điều chuyển đơn hàng (batch order transfer)
     */
    /**
     * Trang xem tất cả thông báo công việc của kho
     */
    public function allNotifications(Request $request)
    {
        $user = Auth::user();
        $warehouseId = $user->warehouse_id;
        $notifications = collect();

        // Đơn mới đã duyệt, chờ kho xử lý
        $newOrders = Order::query()
            ->with(['customer', 'user', 'items.product', 'items.variant'])
            ->when($warehouseId, fn ($query) => $query->where(function ($scope) use ($warehouseId) {
                $scope->where('warehouse_id', $warehouseId)->orWhereNull('warehouse_id');
            }))
            ->whereIn('status', [Order::STATUS_APPROVED, Order::STATUS_READY_TO_PACK])
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();
        foreach ($newOrders as $order) {
            $notifications->push([
                'type' => 'new_order',
                'title' => 'Đơn mới: #'.($order->daily_sequence ?: $order->id).' - '.($order->customer?->name ?: 'Khách hàng'),
                'meta' => 'Sale: '.($order->user?->name ?: 'Chưa xác định').' • Giờ tạo: '.optional($order->created_at)->format('H:i d/m/Y'),
                'details' => $order->items->map(fn ($item) => [
                    'name' => $item->variant?->name ?? $item->product?->name ?? 'Sản phẩm',
                    'quantity' => (float) ($item->quantity ?? 0),
                    'price' => (float) ($item->price ?? 0),
                    'line_total' => (float) ($item->total ?? ((float) ($item->quantity ?? 0) * (float) ($item->price ?? 0))),
                ])->values()->all(),
                'total' => (float) ($order->total ?? 0),
                'note' => (string) ($order->note ?? ''),
                'link' => route('warehouse.orders', ['date' => optional($order->created_at)->toDateString(), 'highlight' => $order->id]),
                'time' => optional($order->created_at)->format('d/m/Y H:i'),
            ]);
        }

        // Đơn đã đóng gói, chờ shipper nhận
        $packedOrders = \App\Models\Order::query()
            ->with('customer')
            ->where('warehouse_id', $warehouseId)
            ->where('status', \App\Models\Order::STATUS_READY_TO_SHIP)
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get();
        foreach ($packedOrders as $order) {
            $notifications->push([
                'type' => 'warehouse',
                'title' => 'Đơn '.($order->code ? '#'.$order->code : '#'.$order->id).' : đã hoàn thành đóng gói, chờ Shipper nhận',
                'meta' => $order->customer?->name ?: 'Khách hàng',
                'link' => route('pages.my_dashboard').'#packed-orders',
                'time' => optional($order->updated_at)->format('d/m/Y H:i'),
            ]);
        }

        // Sale: Phản hồi yêu cầu thay đổi đơn hàng từ Nhà máy
        $saleConfirmOrders = \App\Models\Order::query()
            ->with('customer')
            ->where('warehouse_id', $warehouseId)
            ->where('warehouse_adjustment_status', \App\Models\Order::WAREHOUSE_ADJUSTMENT_STATUS_SALE_CONFIRMED)
            ->orderByDesc('warehouse_adjustment_confirmed_at')
            ->limit(30)
            ->get();
        foreach ($saleConfirmOrders as $order) {
            $notifications->push([
                'type' => 'sale',
                'title' => 'Sale: Phản hồi yêu cầu thay đổi đơn hàng từ Nhà máy',
                'meta' => ($order->code ? '#'.$order->code : '#'.$order->id).' - '.($order->customer?->name ?: 'Khách hàng'),
                'link' => route('pages.my_dashboard').'#sale-confirm-orders',
                'time' => optional($order->warehouse_adjustment_confirmed_at)->format('d/m/Y H:i'),
            ]);
        }

        // Sale: Gửi yêu cầu thay đổi đơn hàng tới kho, cần phê duyệt
        $pendingSaleConfirmOrders = \App\Models\Order::query()
            ->with('customer')
            ->where('warehouse_id', $warehouseId)
            ->where('warehouse_adjustment_status', \App\Models\Order::WAREHOUSE_ADJUSTMENT_STATUS_PENDING_SALE_CONFIRMATION)
            ->orderByDesc('warehouse_adjustment_requested_at')
            ->limit(30)
            ->get();
        foreach ($pendingSaleConfirmOrders as $order) {
            $notifications->push([
                'type' => 'sale',
                'title' => 'Sale: Gửi yêu cầu thay đổi đơn hàng tới kho, cần phê duyệt',
                'meta' => ($order->code ? '#'.$order->code : '#'.$order->id).' - '.($order->customer?->name ?: 'Khách hàng'),
                'link' => route('pages.my_dashboard').'#pending-sale-confirm-orders',
                'time' => optional($order->warehouse_adjustment_requested_at)->format('d/m/Y H:i'),
            ]);
        }

        // Shipper: khách trả hàng cần nhận hàng
        $returnOrders = \App\Models\Order::query()
            ->with('customer')
            ->where('warehouse_id', $warehouseId)
            ->where('status', \App\Models\Order::STATUS_RETURNED)
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get();
        foreach ($returnOrders as $order) {
            $notifications->push([
                'type' => 'shipper',
                'title' => 'Shipper : '.($order->customer?->name ?: 'Khách').' trả hàng cần nhận hàng',
                'meta' => ($order->code ? '#'.$order->code : '#'.$order->id),
                'link' => route('pages.my_dashboard').'#return-orders',
                'time' => optional($order->updated_at)->format('d/m/Y H:i'),
            ]);
        }

        // Shipper: Đã nhận đơn
        $shipperOrders = \App\Models\Order::query()
            ->with(['customer', 'shipper'])
            ->where('warehouse_id', $warehouseId)
            ->where('status', \App\Models\Order::STATUS_IN_DELIVERY)
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get();
        foreach ($shipperOrders as $order) {
            $shipperName = $order->shipper?->name ?: 'Shipper';
            $customerName = $order->customer?->name ?: '';
            $notifications->push([
                'type' => 'shipper',
                'title' => 'Shipper: Đã nhận đơn '.($order->code ? '#'.$order->code : '#'.$order->id).($customerName ? ' - '.$customerName : ''),
                'meta' => $shipperName,
                'link' => route('pages.my_dashboard').'#shipper-orders',
                'time' => optional($order->updated_at)->format('d/m/Y H:i'),
            ]);
        }

        // Shipper: Kho chuyển hàng tới, cần tiếp nhận
        $transferToWarehouse = \App\Models\WarehouseTransfer::query()
            ->with(['order', 'sourceWarehouse'])
            ->where('target_warehouse_id', $warehouseId)
            ->where('status', \App\Models\WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE)
            ->orderByDesc('delivered_at')
            ->limit(30)
            ->get();
        foreach ($transferToWarehouse as $transfer) {
            $order = $transfer->order;
            $sourceWarehouse = $transfer->sourceWarehouse?->name ?: 'Kho khác';
            $notifications->push([
                'type' => 'shipper',
                'title' => 'Shipper : '.$sourceWarehouse.' chuyển hàng tới, Cần tiếp nhận',
                'meta' => $order ? (($order->code ? '#'.$order->code : '#'.$order->id).' - '.($order->customer?->name ?: '')) : '',
                'link' => route('pages.my_dashboard').'#warehouse-transfer-in',
                'time' => optional($transfer->delivered_at)->format('d/m/Y H:i'),
            ]);
        }

        // Shipper: Kho chuyển tiếp đơn hàng, cần kiểm tra và xác nhận
        $transferCheck = \App\Models\WarehouseTransfer::query()
            ->with(['order', 'sourceWarehouse', 'targetWarehouse'])
            ->where('source_warehouse_id', $warehouseId)
            ->where('status', \App\Models\WarehouseTransfer::STATUS_IN_TRANSIT)
            ->orderByDesc('delivered_at')
            ->limit(30)
            ->get();
        foreach ($transferCheck as $transfer) {
            $order = $transfer->order;
            $targetWarehouse = $transfer->targetWarehouse?->name ?: 'Kho khác';
            $notifications->push([
                'type' => 'shipper',
                'title' => 'Shipper : '.$targetWarehouse.' Chuyển tiếp đơn hàng, cần kiểm tra và Xác nhận',
                'meta' => $order ? (($order->code ? '#'.$order->code : '#'.$order->id).' - '.($order->customer?->name ?: '')) : '',
                'link' => route('pages.my_dashboard').'#warehouse-transfer-out',
                'time' => optional($transfer->delivered_at)->format('d/m/Y H:i'),
            ]);
        }

        return view('warehouse.notifications', [
            'notifications' => $notifications,
        ]);
    }

    public function orderTransfers(Request $request)
    {
        // Tạm thời chỉ render view trống
        return view('warehouse.order-transfers');
    }

    private const READY_TO_PACK_STATUSES = [
        'approved',
        Order::STATUS_READY_TO_PACK,
    ];

    private const PACKED_STATUSES = [
        'packed',
        Order::STATUS_READY_TO_SHIP,
    ];

    private const EDITABLE_LOGISTICS_STATUSES = [
        Order::STATUS_PACKING,
    ];

    public function __construct()
    {
        $this->middleware(['auth', 'role:warehouse,admin']);
    }

    public function index(Request $request)
    {
        $currentUser = Auth::user();
        $managedWarehouseId = $currentUser?->warehouse_id ? (int) $currentUser->warehouse_id : null;

        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))->startOfDay()
            : Carbon::today();

        $dateString = $selectedDate->toDateString();

        $applyWarehouseScope = function ($query) use ($managedWarehouseId, $currentUser) {
            if ($managedWarehouseId && $currentUser?->hasRole('warehouse')) {
                $query->where(function ($warehouseScope) use ($managedWarehouseId) {
                    $warehouseScope->where('warehouse_id', $managedWarehouseId)
                        ->orWhere(function ($sharedScope) {
                            $sharedScope->whereNull('warehouse_id')
                                ->whereIn('status', array_merge(self::READY_TO_PACK_STATUSES, [Order::STATUS_PACKING]));
                        });
                });
            }

            return $query;
        };

        $dailyOrdersQuery = Order::with('customer')
            ->whereDate('created_at', $dateString);

        $applyWarehouseScope($dailyOrdersQuery);

        $dailyOrders = (clone $dailyOrdersQuery)
            ->latest('created_at')
            ->take(12)
            ->get();

        $approvalStats = [
            'pending_approval' => (clone $dailyOrdersQuery)
                ->whereIn('status', ['pending_leader_approval', 'pending_manager_approval', 'pending_warehouse_approval'])
                ->count(),
            'approved' => OrderHistory::where('action', 'approve_order')
                ->whereDate('created_at', $dateString)
                ->count(),
            'rejected' => OrderHistory::where('action', 'reject_order')
                ->whereDate('created_at', $dateString)
                ->count(),
        ];

        $stats = [
            'ready_to_pack' => $applyWarehouseScope(Order::query())
                ->whereIn('status', self::READY_TO_PACK_STATUSES)
                ->whereDate('created_at', $dateString)
                ->count(),
            'packed' => $applyWarehouseScope(Order::query())
                ->whereIn('status', self::PACKED_STATUSES)
                ->whereDate('updated_at', $dateString)->count(),
            'packing' => $applyWarehouseScope(Order::query())
                ->where('status', Order::STATUS_PACKING)
                ->whereDate('created_at', $dateString)
                ->count(),
            'returning' => $applyWarehouseScope(Order::query())
                ->where('status', Order::STATUS_RETURNING)
                ->whereDate('created_at', $dateString)
                ->count(),
            'returned' => 0, // Nếu có logic tính thực tế thì thay thế
            'assigned_tasks' => 0, // Nếu có logic tính thực tế thì thay thế
            'completed_tasks' => 0, // Nếu có logic tính thực tế thì thay thế
            'transfers_incoming' => \App\Models\WarehouseTransfer::query()
                ->where('target_warehouse_id', $managedWarehouseId)
                ->where('status', \App\Models\WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE)
                ->count(),
            'transfers_completed' => \App\Models\WarehouseTransfer::query()
                ->where('target_warehouse_id', $managedWarehouseId)
                ->where('status', \App\Models\WarehouseTransfer::STATUS_RECEIVED_COMPLETED)
                ->whereDate('updated_at', $dateString)
                ->count(),
            'done_today' => $applyWarehouseScope(Order::query())
                ->whereIn('status', self::PACKED_STATUSES)
                ->whereDate('updated_at', $dateString)->count(),
            'orders_in_day' => (clone $dailyOrdersQuery)->count(),
            // Thống kê tiếp nhận hàng chuyển kho nội bộ (WarehouseInventoryTransfer)
            'receiving' => \App\Models\WarehouseInventoryTransfer::query()
                ->where('target_warehouse_id', $managedWarehouseId)
                ->where('status', \App\Models\WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE)
                ->count(),
            'received' => \App\Models\WarehouseInventoryTransfer::query()
                ->where('target_warehouse_id', $managedWarehouseId)
                ->where('status', \App\Models\WarehouseInventoryTransfer::STATUS_RECEIVED_COMPLETED)
                ->whereDate('updated_at', $dateString)
                ->count(),
            'stocktakes_completed' => InventoryStocktake::query()
                ->when($managedWarehouseId, fn ($query) => $query->where('warehouse_id', $managedWarehouseId))
                ->whereDate('counted_at', $dateString)
                ->count(),
        ];

        $recentPacked = $applyWarehouseScope(Order::with('customer'))
            ->whereIn('status', self::PACKED_STATUSES)
            ->whereDate('updated_at', $dateString)
            ->orderByDesc('updated_at')
            ->take(5)
            ->get();

        $inventorySummary = app(WarehouseInventorySummaryService::class)
            ->build($managedWarehouseId, Carbon::today()->toDateString());
        $cuttingShortages = app(ProductCuttingService::class)
            ->missingCutProducts($managedWarehouseId);
        $deferredComponentImportRequests = CuttingComponentImportRequest::query()
            ->with(['items.productVariant.product', 'warehouse'])
            ->where('status', CuttingComponentImportRequest::STATUS_OPEN)
            ->when($managedWarehouseId, fn ($query) => $query->where('warehouse_id', $managedWarehouseId))
            ->whereDate('request_date', Carbon::today()->toDateString())
            ->orderByDesc('id')
            ->get();

        return view('warehouse.dashboard', compact(
            'stats',
            'recentPacked',
            'selectedDate',
            'dailyOrders',
            'approvalStats',
            'inventorySummary',
            'cuttingShortages',
            'deferredComponentImportRequests'
        ));
    }

    public function productionDashboard(Request $request)
    {
        $managedWarehouseId = Auth::user()?->warehouse_id
            ? (int) Auth::user()->warehouse_id
            : null;
        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))->startOfDay()
            : Carbon::today();

        return view('warehouse.production-dashboard', [
            'selectedDate' => $selectedDate,
            'productionDashboard' => $this->buildProductionDashboard($managedWarehouseId, $selectedDate),
        ]);
    }

    /**
     * Tổng hợp vận hành nhà máy theo ngày: đầu vào thu mua, phân loại,
     * sản lượng pha lóc, hao hụt và các chi phí trực tiếp liên quan.
     */
    private function buildProductionDashboard(?int $warehouseId, Carbon $selectedDate): array
    {
        $date = $selectedDate->toDateString();
        $purchases = ProcurementPurchase::query()
            ->where('status', ProcurementPurchase::STATUS_RECEIVED)
            ->whereDate('received_at', $date)
            ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
            ->get();

        $purchaseIds = $purchases->pluck('id');
        $classificationLabels = [
            'processed_duck' => 'Vịt đã làm',
            'feathers' => 'Lông',
            'offal' => 'Phụ phẩm',
            'reject' => 'Hàng loại',
        ];
        $classifications = ProcurementPurchaseItem::query()
            ->whereIn('procurement_purchase_id', $purchaseIds)
            ->where('stage', 'received')
            ->get()
            ->groupBy(fn (ProcurementPurchaseItem $item) => $item->item_type.'|'.($item->size ?? ''))
            ->map(function (Collection $items) use ($classificationLabels) {
                $first = $items->first();

                return [
                    'type' => (string) $first->item_type,
                    'type_label' => $classificationLabels[$first->item_type] ?? ucfirst((string) $first->item_type),
                    'size' => $first->size !== null ? (float) $first->size : null,
                    'quantity' => (int) $items->sum('quantity'),
                    'weight' => (float) $items->sum('weight'),
                ];
            })
            ->sortByDesc('weight')
            ->values();

        $batches = ProductCuttingBatch::query()
            ->with('targetVariant.product')
            ->where('status', ProductCuttingBatch::STATUS_COMPLETED)
            ->whereDate('completed_at', $date)
            ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
            ->get();

        $productionByVariant = $batches
            ->groupBy('target_product_variant_id')
            ->map(function (Collection $variantBatches) {
                $variant = $variantBatches->first()?->targetVariant;

                return [
                    'product_name' => (string) ($variant?->product?->name ?? 'Sản phẩm'),
                    'variant_name' => (string) ($variant?->name ?? ''),
                    'size' => is_numeric($variant?->size) ? (float) $variant->size : null,
                    'batch_count' => $variantBatches->count(),
                    'input_weight' => (float) $variantBatches->sum('input_weight'),
                    'finished_weight' => (float) $variantBatches->sum('actual_finished_weight'),
                    'component_weight' => (float) $variantBatches->sum('actual_component_weight'),
                    'loss_weight' => (float) $variantBatches->sum('loss_weight'),
                ];
            })
            ->sortByDesc('finished_weight')
            ->values();

        $inputWeight = (float) $purchases->sum('total_weight');
        $inputQuantity = (int) $purchases->sum('quantity');
        $purchaseCost = (float) $purchases->sum('subtotal');
        $operatingCosts = [
            'Môi giới' => (float) $purchases->sum('broker_fee'),
            'Gia công' => (float) $purchases->sum('processing_fee'),
            'Thu mua' => (float) $purchases->sum('procurement_fee'),
            'Vận chuyển' => (float) $purchases->sum('transportation_fee'),
            'Chi phí khác' => (float) $purchases->sum('other_fee'),
        ];
        $operatingCost = array_sum($operatingCosts);
        $totalCost = (float) $purchases->sum('total_amount');
        $productionInput = (float) $batches->sum('input_weight');
        $finishedWeight = (float) $batches->sum('actual_finished_weight');
        $componentWeight = (float) $batches->sum('actual_component_weight');
        $lossWeight = (float) $batches->sum('loss_weight');
        $averageInputCost = $inputWeight > 0 ? $totalCost / $inputWeight : 0.0;

        $trendStart = $selectedDate->copy()->subDays(6)->startOfDay();
        $trendRows = ProductCuttingBatch::query()
            ->where('status', ProductCuttingBatch::STATUS_COMPLETED)
            ->whereBetween('completed_at', [$trendStart, $selectedDate->copy()->endOfDay()])
            ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
            ->selectRaw('DATE(completed_at) as production_date, SUM(actual_finished_weight) as finished_weight, SUM(loss_weight) as loss_weight')
            ->groupBy('production_date')
            ->get()
            ->keyBy('production_date');
        $trend = collect(range(0, 6))->map(function (int $offset) use ($trendStart, $trendRows) {
            $day = $trendStart->copy()->addDays($offset);
            $row = $trendRows->get($day->toDateString());

            return [
                'date' => $day->toDateString(),
                'label' => $day->format('d/m'),
                'finished_weight' => (float) ($row->finished_weight ?? 0),
                'loss_weight' => (float) ($row->loss_weight ?? 0),
            ];
        })->values();

        $inputTypes = $purchases
            ->groupBy(fn (ProcurementPurchase $purchase) => trim((string) $purchase->duck_type) ?: ($purchase->purchase_type === 'live_duck' ? 'Vịt sống' : 'Vịt đã làm'))
            ->map(fn (Collection $rows, string $label) => [
                'label' => $label,
                'quantity' => (int) $rows->sum('quantity'),
                'weight' => (float) $rows->sum('total_weight'),
                'cost' => (float) $rows->sum('total_amount'),
            ])
            ->sortByDesc('weight')
            ->values();

        return [
            'date' => $date,
            'summary' => [
                'receipt_count' => $purchases->count(),
                'input_quantity' => $inputQuantity,
                'input_weight' => $inputWeight,
                'production_batch_count' => $batches->count(),
                'production_input_weight' => $productionInput,
                'finished_weight' => $finishedWeight,
                'component_weight' => $componentWeight,
                'loss_weight' => $lossWeight,
                'loss_percent' => $productionInput > 0 ? round($lossWeight * 100 / $productionInput, 2) : 0.0,
                'yield_percent' => $productionInput > 0 ? round(($finishedWeight + $componentWeight) * 100 / $productionInput, 2) : 0.0,
                'reject_weight' => (float) $classifications->where('type', 'reject')->sum('weight'),
                'purchase_cost' => $purchaseCost,
                'operating_cost' => $operatingCost,
                'total_cost' => $totalCost,
                'average_input_cost' => $averageInputCost,
                'estimated_loss_cost' => $lossWeight * $averageInputCost,
            ],
            'input_types' => $inputTypes->all(),
            'classifications' => $classifications->all(),
            'production_by_variant' => $productionByVariant->all(),
            'operating_costs' => collect($operatingCosts)
                ->map(fn (float $amount, string $label) => ['label' => $label, 'amount' => $amount])
                ->sortByDesc('amount')
                ->values()
                ->all(),
            'trend' => $trend->all(),
            'trend_max' => max(1, (float) $trend->max('finished_weight')),
        ];
    }

    public function cuttingForm(Request $request, ProductVariant $variant)
    {
        $managedWarehouseId = Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        $variant->loadMissing('product');
        abort_unless($variant->product?->product_type === Product::TYPE_CUT, 404);

        $service = app(ProductCuttingService::class);
        $materials = $service->sourceMaterials($variant, $managedWarehouseId);
        $selectedMaterials = collect($request->input('materials', []))
            ->map(fn ($row) => ['variant_id' => (int) ($row['variant_id'] ?? 0), 'quantity' => max(0, (float) ($row['quantity'] ?? 0))])
            ->filter(fn ($row) => $row['variant_id'] > 0 && $row['quantity'] > 0)
            ->values()
            ->all();
        $preview = $service->preview($variant, $selectedMaterials);
        $openOrders = $this->cuttingOrdersForVariant($variant, $managedWarehouseId, Carbon::today()->toDateString());
        $guardResult = $this->buildPackingQueueStockGuards($openOrders, $managedWarehouseId, Carbon::today()->toDateString());
        $cuttingPlansByOrder = $this->buildCuttingPlansForGuards($guardResult['guards'] ?? [], $managedWarehouseId);
        $cuttingOrders = $openOrders
            ->filter(function (Order $order) use ($cuttingPlansByOrder, $variant) {
                return isset($cuttingPlansByOrder[(int) $order->id][(int) $variant->id]);
            })
            ->values();

        return view('warehouse.cutting.form', [
            'targetVariant' => $variant,
            'materials' => $materials,
            'selectedMaterials' => collect($selectedMaterials)->keyBy('variant_id'),
            'preview' => $preview,
            'demand' => (float) $request->query('demand', 0),
            'cuttingOrders' => $cuttingOrders,
            'cuttingPlansByOrder' => $cuttingPlansByOrder,
        ]);
    }

    public function executeCutting(Request $request, ProductVariant $variant)
    {
        $user = Auth::user();
        $warehouseId = $user?->warehouse_id ? (int) $user->warehouse_id : null;
        if (! $warehouseId && ! $user?->hasRole('admin')) {
            return back()->with('error', 'Tài khoản chưa được gán kho thực hiện.');
        }

        $variant->loadMissing('product');
        abort_unless($variant->product?->product_type === Product::TYPE_CUT, 404);

        $data = $request->validate([
            'materials' => ['required', 'array', 'min:1'],
            'materials.*.variant_id' => ['required', 'exists:product_variants,id'],
            'materials.*.quantity' => ['required', 'numeric', 'min:0'],
            'actual_finished_weight' => ['required', 'numeric', 'min:0.001'],
            'components' => ['nullable', 'array'],
            'components.*.variant_id' => ['required_with:components', 'exists:product_variants,id'],
            'components.*.weight' => ['nullable', 'numeric', 'min:0'],
            'defer_components' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:1000'],
            'order_id' => ['nullable', 'exists:orders,id'],
            'selected_date' => ['nullable', 'date'],
        ]);

        try {
            app(ProductCuttingService::class)->execute(
                (int) $warehouseId,
                $variant,
                $data['materials'],
                (float) $data['actual_finished_weight'],
                $data['components'] ?? [],
                (string) ($data['note'] ?? 'Xuất kho để thực hiện pha lóc.'),
                (int) $user->id,
                $request->boolean('defer_components'),
                ! empty($data['order_id']) ? (int) $data['order_id'] : null
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        if (! empty($data['order_id'])) {
            return redirect()
                ->route('warehouse.orders', [
                    'date' => $data['selected_date'] ?? now()->toDateString(),
                    'highlight' => (int) $data['order_id'],
                ])
                ->with('success', 'Đã thực hiện pha lóc và cập nhật tồn kho.');
        }

        return redirect()->route('warehouse.dashboard')->with('success', 'Đã thực hiện pha lóc và cập nhật tồn kho.');
    }

    public function confirmCuttingMaterials(Request $request, ProductVariant $variant)
    {
        $user = Auth::user();
        $warehouseId = $user?->warehouse_id ? (int) $user->warehouse_id : null;
        if (! $warehouseId && ! $user?->hasRole('admin')) {
            return back()->with('error', 'Tài khoản chưa được gán kho thực hiện.');
        }

        $variant->loadMissing('product');
        abort_unless($variant->product?->product_type === Product::TYPE_CUT, 404);

        $data = $request->validate([
            'materials' => ['required', 'array', 'min:1'],
            'materials.*.variant_id' => ['required', 'exists:product_variants,id'],
            'materials.*.quantity' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
            'order_id' => ['nullable', 'exists:orders,id'],
            'selected_date' => ['nullable', 'date'],
        ]);

        try {
            app(ProductCuttingService::class)->start(
                (int) $warehouseId,
                $variant,
                $data['materials'],
                (string) ($data['note'] ?? 'Xuất kho nguyên con để pha lóc.'),
                (int) $user->id,
                ! empty($data['order_id']) ? (int) $data['order_id'] : null
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('warehouse.orders', [
                'date' => $data['selected_date'] ?? now()->toDateString(),
                'highlight' => $data['order_id'] ?? null,
            ])
            ->with('success', 'Đã xác nhận lấy hàng pha lóc. Đơn sẽ chuyển sang bước đóng hàng hoàn thiện.');
    }

    public function revertCuttingBatch(ProductCuttingBatch $batch)
    {
        $user = Auth::user();
        $managedWarehouseId = $user?->warehouse_id ? (int) $user->warehouse_id : null;
        if (! $managedWarehouseId && ! $user?->hasRole('admin')) {
            return back()->with('error', 'Tài khoản chưa được gán kho thực hiện.');
        }
        if ($managedWarehouseId && (int) $batch->warehouse_id !== $managedWarehouseId) {
            abort(403, 'Bạn không có quyền quay lại mẻ pha lóc của kho khác.');
        }

        try {
            app(ProductCuttingService::class)->revert($batch, (int) $user->id);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã quay lại xác nhận lấy hàng pha lóc và hoàn nguyên tồn nguyên liệu.');
    }

    public function completeCuttingBatch(Request $request, ProductCuttingBatch $batch)
    {
        $user = Auth::user();
        $managedWarehouseId = $user?->warehouse_id ? (int) $user->warehouse_id : null;
        if (! $managedWarehouseId && ! $user?->hasRole('admin')) {
            return back()->with('error', 'Tài khoản chưa được gán kho thực hiện.');
        }
        if ($managedWarehouseId && (int) $batch->warehouse_id !== $managedWarehouseId) {
            abort(403, 'Bạn không có quyền hoàn thiện mẻ pha lóc của kho khác.');
        }

        $data = $request->validate([
            'actual_finished_weight' => ['required', 'numeric', 'min:0.001'],
            'components' => ['nullable', 'array'],
            'components.*.variant_id' => ['required_with:components', 'exists:product_variants,id'],
            'components.*.weight' => ['nullable', 'numeric', 'min:0'],
            'defer_components' => ['nullable', 'boolean'],
        ]);

        try {
            app(ProductCuttingService::class)->complete(
                $batch,
                (float) $data['actual_finished_weight'],
                $data['components'] ?? [],
                (int) $user->id,
                $request->boolean('defer_components')
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã hoàn thiện pha lóc, nhập kho thực tế và ghi nhận hao hụt.');
    }

    public function receiveCuttingComponentImportRequest(CuttingComponentImportRequest $componentImportRequest)
    {
        $user = Auth::user();
        $managedWarehouseId = $user?->warehouse_id ? (int) $user->warehouse_id : null;
        if ($managedWarehouseId && (int) $componentImportRequest->warehouse_id !== $managedWarehouseId) {
            abort(403, 'Bạn không có quyền xử lý phiếu yêu cầu của kho khác.');
        }

        if ($componentImportRequest->status !== CuttingComponentImportRequest::STATUS_OPEN) {
            return back()->with('error', 'Phiếu yêu cầu này đã được xử lý.');
        }

        $componentImportRequest->load('items.productVariant.product');
        if ($componentImportRequest->items->isEmpty()) {
            return back()->with('error', 'Phiếu yêu cầu chưa có thành phần cần nhập.');
        }
        if ((float) $componentImportRequest->items->sum('quantity') <= 0) {
            return back()->with('error', 'Phiếu yêu cầu chưa có khối lượng hợp lệ để nhập kho.');
        }

        DB::transaction(function () use ($componentImportRequest, $user): void {
            $rows = $componentImportRequest->items
                ->groupBy('product_variant_id')
                ->map(fn ($items, $variantId) => [
                    'variant_id' => (int) $variantId,
                    'quantity' => round((float) $items->sum('quantity'), 3),
                ])
                ->filter(fn ($row) => $row['variant_id'] > 0 && $row['quantity'] > 0)
                ->values();

            $document = InventoryDocument::create([
                'type' => 'import',
                'warehouse_id' => (int) $componentImportRequest->warehouse_id,
                'document_date' => now()->toDateString(),
                'notes' => 'Nhập kho thành phần còn lại từ phiếu yêu cầu pha lóc #'.$componentImportRequest->id,
                'shipping_fee' => 0,
                'user_id' => (int) $user->id,
            ]);

            foreach ($rows as $row) {
                $document->items()->create([
                    'product_variant_id' => $row['variant_id'],
                    'quantity' => $row['quantity'],
                    'unit_cost' => 0,
                ]);

                $inventory = Inventory::query()->firstOrCreate(
                    [
                        'warehouse_id' => (int) $componentImportRequest->warehouse_id,
                        'product_variant_id' => $row['variant_id'],
                    ],
                    ['quantity' => 0, 'reserved_quantity' => 0, 'low_stock_threshold' => 10]
                );

                InventoryMovement::create([
                    'inventory_id' => $inventory->id,
                    'quantity' => $row['quantity'],
                    'type' => 'cutting_component_deferred_import',
                    'reference_id' => $document->id,
                    'reference_type' => InventoryDocument::class,
                    'user_id' => (int) $user->id,
                ]);

                $inventory->increment('quantity', $row['quantity']);
                ProductVariant::whereKey($row['variant_id'])->update([
                    'stock' => Inventory::query()->where('product_variant_id', $row['variant_id'])->sum('quantity'),
                ]);
            }

            $componentImportRequest->update([
                'status' => CuttingComponentImportRequest::STATUS_RECEIVED,
                'received_by' => (int) $user->id,
                'received_at' => now(),
                'inventory_document_id' => (int) $document->id,
            ]);
        });

        $this->syncAllQueuedOrdersStockSufficiency($managedWarehouseId);

        return back()->with('success', 'Đã nhập kho các thành phần còn lại từ pha lóc.');
    }

    /**
     * Show the form for creating a new stock-in document.
     */
    public function createStockIn(Request $request)
    {
        $suppliers = \App\Models\Supplier::all();

        $managedWarehouseId = Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        $stockInTemplates = InventoryDocumentTemplate::query()
            ->with(['supplier', 'items.productVariant.product'])
            ->where('warehouse_id', $managedWarehouseId ?? 0)
            ->orderBy('name')
            ->get()
            ->map(fn (InventoryDocumentTemplate $template) => [
                'id' => (int) $template->id,
                'name' => (string) $template->name,
                'supplier_id' => (int) $template->supplier_id,
                'supplier_name' => (string) ($template->supplier?->name ?? ''),
                'items' => $template->items->map(fn ($item) => [
                    'product_variant_id' => (int) $item->product_variant_id,
                    'quantity' => (int) $item->quantity,
                    'label' => trim(
                        ($item->productVariant?->product?->name ?? 'Sản phẩm')
                        .' - '
                        .($item->productVariant?->name ?? 'Biến thể')
                    ),
                ])->values()->all(),
            ])
            ->values();

        $productVariants = ProductVariant::with([
            'product',
            'inventories' => function ($query) use ($managedWarehouseId) {
                if ($managedWarehouseId) {
                    $query->where('warehouse_id', $managedWarehouseId);
                }
            },
            'values.attribute', // lấy thuộc tính variant nếu có
        ])
            ->where('status', true)
            ->get();

        $availableVariants = $productVariants->map(function ($variant) {
            $totalQuantity = (int) $variant->inventories->sum('quantity');
            $totalReserved = (int) $variant->inventories->sum('reserved_quantity');
            $availableQuantity = max(0, $totalQuantity - $totalReserved);
            $lowStockThreshold = (int) ($variant->inventories->min('low_stock_threshold') ?? 5);
            $weightPerUnit = (float) ($variant->effective_kg ?? 1);
            // Thuộc tính dạng: Size: M, Màu: Đỏ...
            $attributes = $variant->values->map(function ($val) {
                return $val->attribute->name.': '.$val->value;
            })->implode(', ');
            $label = ($variant->product->name ?? 'Sản phẩm')
                .' - '.($variant->name ?? 'Biến thể')
                .($variant->sku ? ' ('.$variant->sku.')' : '')
                .($attributes ? ' ['.$attributes.']' : '');

            return [
                'variant_id' => (int) $variant->id,
                'product_id' => (int) $variant->product_id,
                'product_name' => (string) ($variant->product->name ?? 'Sản phẩm'),
                'product_sku' => (string) ($variant->product->sku ?? ''),
                'variant_name' => (string) ($variant->name ?? 'Biến thể'),
                'variant_sku' => (string) ($variant->sku ?? ''),
                'attributes' => $attributes,
                'label' => $label,
                'unit_label' => $variant->product->unit_label ?? 'Cái',
                'weight_per_unit' => $weightPerUnit,
                'available' => $availableQuantity,
                'low_stock_threshold' => $lowStockThreshold,
            ];
        })->values();

        // Chỉ thống kê thiếu hàng theo các đơn được lên trong ngày (FIFO queue của kho).
        $queueStatuses = array_merge(self::READY_TO_PACK_STATUSES, [Order::STATUS_PACKING]);
        $selectedDate = Carbon::today()->toDateString();

        $todayOrdersQuery = Order::with(['items.product', 'items.variant.product'])
            ->whereIn('status', $queueStatuses)
            ->forPackingDate($selectedDate);

        if ($managedWarehouseId && Auth::user()?->hasRole('warehouse')) {
            $todayOrdersQuery->where(function ($warehouseScope) use ($managedWarehouseId, $queueStatuses) {
                $warehouseScope->where('warehouse_id', $managedWarehouseId)
                    ->orWhere(function ($sharedScope) use ($queueStatuses) {
                        $sharedScope->whereNull('warehouse_id')
                            ->whereIn('status', $queueStatuses);
                    });
            });
        }

        $todayOrders = $todayOrdersQuery
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $stockGuardResult = $this->buildPackingQueueStockGuards($todayOrders, $managedWarehouseId, $selectedDate);
        $shortagesByVariant = collect($stockGuardResult['guards'] ?? [])
            ->flatMap(fn (array $guard) => $guard['shortages'] ?? [])
            ->groupBy(fn (array $shortage) => (int) ($shortage['variant_id'] ?? 0))
            ->map(function (Collection $rows) {
                $totalShortQty = (float) $rows->sum(fn (array $row) => (float) ($row['short_qty'] ?? 0));

                return max(1, (int) ceil($totalShortQty));
            });

        $lowStockVariants = $availableVariants
            ->filter(function (array $variant) use ($shortagesByVariant) {
                return $shortagesByVariant->has((int) ($variant['variant_id'] ?? 0));
            })
            ->map(function (array $variant) use ($shortagesByVariant) {
                $variant['shortage_qty'] = (int) ($shortagesByVariant->get((int) $variant['variant_id']) ?? 0);

                return $variant;
            })
            ->sortBy(fn (array $variant) => mb_strtolower((string) ($variant['label'] ?? '')))
            ->values();

        $productVariants = $productVariants->map(function ($variant) {
            return [
                'id' => (int) $variant->id,
                'name' => (string) ($variant->name ?? ''),
                'sku' => (string) ($variant->sku ?? ''),
                'kg' => (float) ($variant->kg ?? 0),
                'weight_per_unit' => (float) ($variant->effective_kg ?? 1),
                'product' => [
                    'id' => (int) ($variant->product?->id ?? 0),
                    'name' => (string) ($variant->product?->name ?? ''),
                    'kg' => (float) ($variant->product?->kg ?? 0),
                    'unit_label' => (string) ($variant->product?->unit_label ?? 'Cái'),
                ],
            ];
        })->values();

        return view('warehouse.stock-in.create', compact('suppliers', 'productVariants', 'availableVariants', 'lowStockVariants', 'stockInTemplates'));
    }

    /**
     * List orders awaiting packing or currently being packed.
     */
    public function orders(Request $request)
    {
        // Auto-cancel overdue orders before loading the page
        \Artisan::call('orders:auto-cancel-overdue');

        $currentUser = Auth::user();
        $managedWarehouseId = $currentUser?->warehouse_id ? (int) $currentUser->warehouse_id : null;
        $sharedQueueStatuses = array_merge(self::READY_TO_PACK_STATUSES, [Order::STATUS_PACKING]);

        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : Carbon::today()->toDateString();

        $status = $request->input('status');
        $today = Carbon::today();
        $startDate = $today->copy()->subDays(6)->toDateString();

        $packingDateSql = 'CASE WHEN accounting_sales_import_batch_id IS NOT NULL '
            .'THEN DATE(delivery_date) ELSE DATE(created_at) END';
        $dailyCountsQuery = Order::query()
            ->selectRaw($packingDateSql.' as day_key, COUNT(*) as total')
            ->where(function ($query) {
                $query->whereNull('is_return_order')
                    ->orWhere('is_return_order', false);
            })
            ->whereRaw($packingDateSql.' >= ?', [$startDate])
            ->whereRaw($packingDateSql.' <= ?', [$today->toDateString()]);

        if ($managedWarehouseId && ($currentUser?->hasRole('warehouse') || $currentUser?->hasRole('package'))) {
            $dailyCountsQuery->where(function ($warehouseScope) use ($managedWarehouseId, $sharedQueueStatuses) {
                $warehouseScope->where('warehouse_id', $managedWarehouseId)
                    ->orWhere(function ($sharedScope) use ($sharedQueueStatuses) {
                        $sharedScope->whereNull('warehouse_id')
                            ->whereIn('status', $sharedQueueStatuses);
                    });
            });
        }

        if (! empty($status)) {
            $dailyCountsQuery->where('status', $status);
        }

        $dailyCounts = $dailyCountsQuery
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

        $ordersQuery = Order::with([
            'customer',
            'user',
            'warehouse',
            'adjustments:id,order_id,status',
            'histories.user.warehouse',
            'items.product.avatar.media',
            'items.packingSizeAllocations.variant',
            'items.variant' => function ($query) {
                $query->withAvailableStock()->with('avatar.media');
            },
        ])
            ->where(function ($query) {
                $query->whereNull('is_return_order')
                    ->orWhere('is_return_order', false);
            })
            ->forPackingDate($selectedDate);

        if ($managedWarehouseId && ($currentUser?->hasRole('warehouse') || $currentUser?->hasRole('package'))) {
            $ordersQuery->where(function ($warehouseScope) use ($managedWarehouseId, $sharedQueueStatuses) {
                $warehouseScope->where('warehouse_id', $managedWarehouseId)
                    ->orWhere(function ($sharedScope) use ($sharedQueueStatuses) {
                        $sharedScope->whereNull('warehouse_id')
                            ->whereIn('status', $sharedQueueStatuses);
                    });
            });
        }

        if (! empty($status)) {
            $ordersQuery->where('status', $status);
        }

        $orders = $ordersQuery
            ->orderByDesc('created_at')
            ->get();

        $this->attachCustomerFeedbackContext($orders);

        $managedWarehouseId = Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        $stockGuardResult = $this->buildPackingQueueStockGuards($orders, $managedWarehouseId, $selectedDate);
        $stockGuardMap = $stockGuardResult['guards'];
        $fifoRemainingStock = $stockGuardResult['remaining_by_variant']; // variantId => float remaining after FIFO

        // The stock drawer is a daily closing-stock report, so it must not be
        // derived only from the products present in that day's orders. Include
        // every variant held by the managed warehouse and reconstruct its
        // quantity at the selected business date. This also keeps historical
        // days visible after their orders have moved out of the packing queue.
        $inventoryVariantIds = Inventory::query()
            ->when($managedWarehouseId, fn ($query) => $query->where('warehouse_id', $managedWarehouseId))
            ->pluck('product_variant_id')
            ->map(fn ($id) => (int) $id);
        $orderVariantIds = $orders
            ->flatMap(fn (Order $order) => $order->items->pluck('product_variant_id'))
            ->filter()
            ->map(fn ($id) => (int) $id);
        $stockPanelVariantIds = $inventoryVariantIds
            ->merge($orderVariantIds)
            ->unique()
            ->values();
        $stockPanelVariants = ProductVariant::query()
            ->with('product')
            ->whereIn('id', $stockPanelVariantIds->all())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $variantStock = $this->getStockAtDate(
            $stockPanelVariantIds,
            $managedWarehouseId,
            $selectedDate
        );

        $availableVariants = Inventory::query()
            ->with(['productVariant.product', 'productVariant.values.attribute'])
            ->where('warehouse_id', $managedWarehouseId)
            ->whereRaw('(quantity - reserved_quantity) > 0')
            ->orderByRaw('(quantity - reserved_quantity) DESC')
            ->get()
            ->map(function (Inventory $inventory) {
                $variant = $inventory->productVariant;
                $product = $variant?->product;
                if (! $variant || ! $product) {
                    return null;
                }
                // Lấy thuộc tính dạng: Size: M, Màu: Đỏ...
                $attributes = $variant->values?->map(function ($val) {
                    return $val->attribute->name.': '.$val->value;
                })->implode(', ');

                return [
                    'variant_id' => (int) $variant->id,
                    'variant_name' => $variant->name ?? '',
                    'variant_sku' => $variant->sku ?? '',
                    'unit_label' => $product->unit_label ?? 'Cái',
                    'available' => max(0, (int) $inventory->quantity - (int) $inventory->reserved_quantity),
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku ?? '',
                    'product_thumbnail' => $product->thumbnail?->media?->file_path ?? null,
                    'product_category' => $product->category?->name ?? null,
                    'attributes' => $attributes,
                ];
            })
            ->filter()
            ->values();

        // Group by product_id
        $availableVariantsGrouped = $availableVariants->groupBy('product_id')->map(function ($variants, $productId) {
            $first = $variants->first();

            return [
                'product' => [
                    'id' => $first['product_id'],
                    'name' => $first['product_name'],
                    'sku' => $first['product_sku'],
                    'thumbnail' => $first['product_thumbnail'],
                    'category' => $first['product_category'],
                ],
                'variants' => $variants->map(function ($v) {
                    return [
                        'variant_id' => $v['variant_id'],
                        'name' => $v['variant_name'],
                        'sku' => $v['variant_sku'],
                        'unit_label' => $v['unit_label'],
                        'available' => $v['available'],
                        'attributes' => $v['attributes'] ?? '',
                    ];
                })->values(),
            ];
        })->values();

        $orders->each(function (Order $order) use ($stockGuardMap) {
            $order->setAttribute('stock_guard', $stockGuardMap[$order->id] ?? [
                'has_shortage' => false,
                'can_start_packing' => true,
                'message' => null,
                'shortages' => [],
            ]);
        });
        $cuttingPlansByOrder = $this->buildCuttingPlansForGuards($stockGuardMap, $managedWarehouseId);
        $packingSizeOptionsByItem = $this->buildPackingSizeOptions($orders, $stockGuardMap, $managedWarehouseId);

        $orderIds = $orders->pluck('id')->all();
        $activeCuttingBatchesByOrder = ProductCuttingBatch::query()
            ->with([
                'targetVariant.product',
                'performer:id,name',
                'exportDocument.items.productVariant.product',
            ])
            ->whereIn('order_id', $orderIds)
            ->where('status', ProductCuttingBatch::STATUS_IN_PROGRESS)
            ->orderBy('id')
            ->get()
            ->groupBy('order_id');
        $activeTransfersByOrder = WarehouseTransfer::query()
            ->with(['targetWarehouse:id,name', 'shipper:id,name'])
            ->whereIn('order_id', $orderIds)
            ->whereIn('status', [
                WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP,
                WarehouseTransfer::STATUS_IN_TRANSIT,
                WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE,
            ])
            ->orderByDesc('id')
            ->get()
            ->unique('order_id')
            ->keyBy('order_id');
        $activePackingGoodsTransfersByOrder = WarehouseInventoryTransfer::query()
            ->with(['sourceWarehouse:id,name', 'targetWarehouse:id,name', 'items.variant:id,name,sku'])
            ->whereIn('order_id', $orderIds)
            ->where('status', WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE)
            ->orderByDesc('id')
            ->get()
            ->unique('order_id')
            ->keyBy('order_id');
        $packingReservedQuantitiesByOrder = InventoryReservation::query()
            ->join('order_items', 'order_items.id', '=', 'inventory_reservations.order_item_id')
            ->join('inventories', 'inventories.id', '=', 'inventory_reservations.inventory_id')
            ->whereIn('order_items.order_id', $orderIds)
            ->when($managedWarehouseId, fn ($query) => $query->where('inventories.warehouse_id', $managedWarehouseId))
            ->selectRaw('order_items.order_id, COALESCE(SUM(inventory_reservations.quantity), 0) as reserved_qty')
            ->groupBy('order_items.order_id')
            ->pluck('reserved_qty', 'order_items.order_id');

        $warehouses = Warehouse::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $shippers = User::query()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['shipper', 'manager_shipper']);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $isPackageModule = $request->routeIs('package.*');
        $ordersLayout = $isPackageModule ? 'layouts.package' : 'layouts.warehouse';
        $orderRoutePrefix = $isPackageModule ? 'package' : 'warehouse';
        $packingInventoryRoute = $isPackageModule ? 'package.inventory' : 'warehouse.stock-in';
        $packingDashboardRoute = $isPackageModule ? 'package.dashboard' : 'warehouse.dashboard';

        return view('warehouse.orders.index', compact(
            'orders',
            'selectedDate',
            'status',
            'quickDates',
            'fifoRemainingStock',
            'variantStock',
            'stockPanelVariants',
            'activeTransfersByOrder',
            'activePackingGoodsTransfersByOrder',
            'packingReservedQuantitiesByOrder',
            'warehouses',
            'shippers',
            'ordersLayout',
            'orderRoutePrefix',
            'packingInventoryRoute',
            'packingDashboardRoute',
            'cuttingPlansByOrder',
            'activeCuttingBatchesByOrder',
            'packingSizeOptionsByItem'
        ));
    }

    /** Move a waiting or partially packed order to another packing warehouse. */
    public function transferPackingWarehouse(Request $request, Order $order)
    {
        $user = $request->user();
        $sourceWarehouseId = $user?->warehouse_id ? (int) $user->warehouse_id : 0;
        if ($sourceWarehouseId <= 0) {
            return back()->with('error', 'Tài khoản chưa được gán kho đang làm việc.');
        }

        $validated = $request->validate([
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'packing_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);
        $targetWarehouse = Warehouse::query()
            ->whereKey((int) $validated['warehouse_id'])
            ->where('status', true)
            ->first();

        if (! $targetWarehouse) {
            return back()->withErrors(['warehouse_id' => 'Kho nhận đã ngừng hoạt động hoặc không tồn tại.']);
        }
        if ((int) $targetWarehouse->id === $sourceWarehouseId) {
            return back()->withErrors(['warehouse_id' => 'Chỉ được chọn kho khác kho bạn đang làm.']);
        }

        if ((int) ($order->warehouse_id ?? 0) !== $sourceWarehouseId) {
            return back()->with('error', 'Đơn không thuộc kho bạn đang làm hoặc đã được chuyển sang kho khác.');
        }
        $transferableStatuses = array_merge(self::READY_TO_PACK_STATUSES, [Order::STATUS_PACKING]);
        if (! in_array((string) $order->status, $transferableStatuses, true)) {
            return back()->with('error', 'Chỉ có thể chuyển tiếp đơn đang chờ hoặc đang đóng dở.');
        }
        if (! $this->canProcessOrderOnCurrentRun($order)) {
            return back()->with('error', 'Đơn không còn thuộc ngày nghiệp vụ được phép xử lý.');
        }

        $defaultPackingDate = $order->accounting_sales_import_batch_id && $order->delivery_date
            ? $order->delivery_date->toDateString()
            : $order->created_at->toDateString();
        $packingDate = (string) ($validated['packing_date'] ?? $defaultPackingDate);
        $order->loadMissing(['items.product', 'items.variant.product']);
        $isPartiallyPacking = (string) $order->status === Order::STATUS_PACKING;
        $stockCheck = $this->evaluateSingleOrderStock($order, $sourceWarehouseId, $packingDate);
        if (! $isPartiallyPacking && ! ($stockCheck['has_shortage'] ?? false)) {
            return back()->with('error', 'Đơn hiện đã đủ hàng tại kho đang làm nên không cần điều chuyển.');
        }

        try {
            [$sourceWarehouseName, $targetWarehouseName, $goodsTransfer, $wasPartiallyPacking] = DB::transaction(function () use ($order, $user, $sourceWarehouseId, $targetWarehouse, $transferableStatuses): array {
                $lockedOrder = Order::query()
                    ->with(['items.variant', 'warehouse:id,name'])
                    ->lockForUpdate()
                    ->findOrFail($order->id);

                if ((int) ($lockedOrder->warehouse_id ?? 0) !== $sourceWarehouseId) {
                    throw new \RuntimeException('Đơn không còn thuộc kho bạn đang làm hoặc đã được chuyển sang kho khác.');
                }
                if (! in_array((string) $lockedOrder->status, $transferableStatuses, true)) {
                    throw new \RuntimeException('Chỉ có thể chuyển tiếp đơn đang chờ hoặc đang đóng dở.');
                }
                if (! $this->canProcessOrderOnCurrentRun($lockedOrder)) {
                    throw new \RuntimeException('Đơn không còn thuộc ngày nghiệp vụ được phép xử lý.');
                }
                if ($lockedOrder->order_transfer_id) {
                    throw new \RuntimeException('Đơn đã nằm trong một phiếu điều chuyển khác.');
                }
                if (ProductCuttingBatch::query()
                    ->where('order_id', $lockedOrder->id)
                    ->where('status', ProductCuttingBatch::STATUS_IN_PROGRESS)
                    ->exists()) {
                    throw new \RuntimeException('Đơn đang thực hiện pha lóc nên chưa thể đổi kho đóng hàng.');
                }
                if (WarehouseTransfer::query()
                    ->where('order_id', $lockedOrder->id)
                    ->whereIn('status', [
                        WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP,
                        WarehouseTransfer::STATUS_IN_TRANSIT,
                        WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE,
                    ])
                    ->exists()) {
                    throw new \RuntimeException('Đơn đang có điều chuyển kho hoạt động nên chưa thể đổi kho đóng hàng.');
                }
                if (WarehouseInventoryTransfer::query()
                    ->where('order_id', $lockedOrder->id)
                    ->where('status', WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE)
                    ->exists()) {
                    throw new \RuntimeException('Đơn đang có hàng gửi sang kho khác chờ tiếp nhận nên chưa thể chuyển tiếp.');
                }

                $sourceWarehouseName = $lockedOrder->warehouse?->name ?: 'Kho hiện tại';
                $wasPartiallyPacking = (string) $lockedOrder->status === Order::STATUS_PACKING;
                $goodsTransfer = $this->createPackingOrderGoodsTransfer(
                    $lockedOrder,
                    $sourceWarehouseId,
                    (int) $targetWarehouse->id,
                    (int) $user->id
                );
                $this->releaseOrderReservations($lockedOrder);
                $incomingQuantities = $goodsTransfer
                    ? $goodsTransfer->items()->selectRaw('product_variant_id, SUM(quantity) as quantity')->groupBy('product_variant_id')->pluck('quantity', 'product_variant_id')->all()
                    : [];
                $this->reserveOrderStockAtWarehouse($lockedOrder, (int) $targetWarehouse->id, $incomingQuantities);
                $lockedOrder->forceFill([
                    'warehouse_id' => $targetWarehouse->id,
                    'stock_sufficient' => null,
                    'stock_shortage_detail' => null,
                    'stock_alert_status' => null,
                ])->save();

                OrderHistory::create([
                    'order_id' => $lockedOrder->id,
                    'action' => $wasPartiallyPacking ? 'warehouse_forward_partial_packing' : 'warehouse_transfer_packing_warehouse',
                    'user_id' => $user->id,
                    'role' => 'warehouse',
                    'status_before' => $lockedOrder->status,
                    'status_after' => $lockedOrder->status,
                    'note' => ($wasPartiallyPacking
                        ? "Kho chuyển tiếp đơn đang đóng dở từ {$sourceWarehouseName} sang {$targetWarehouse->name}; giữ nguyên kg thực tế, số bọc và quy cách đã nhập."
                        : "Kho điều chuyển đơn thiếu hàng từ {$sourceWarehouseName} sang {$targetWarehouse->name} để kho mới hoàn thiện đóng hàng.")
                        .($goodsTransfer ? ' Hàng đã gom gửi kèm phiếu '.$goodsTransfer->transfer_code.'.' : ' Chưa có hàng đã giữ tại kho nguồn để gửi kèm.'),
                ]);

                return [$sourceWarehouseName, (string) $targetWarehouse->name, $goodsTransfer, $wasPartiallyPacking];
            });
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $goodsMessage = $goodsTransfer
            ? ' Đã tạo phiếu '.$goodsTransfer->transfer_code.' gửi phần hàng đang giữ cho đơn; kho nhận xác nhận tại mục Tiếp nhận điều chuyển.'
            : ' Đơn chưa có hàng được giữ tại kho nguồn nên không phát sinh phiếu hàng gửi kèm.';

        $actionMessage = $wasPartiallyPacking
            ? "Đã chuyển tiếp đơn đang đóng dở từ {$sourceWarehouseName} sang {$targetWarehouseName}. Kg thực tế và thông tin đóng gói đã được giữ nguyên."
            : "Đã chuyển đơn thiếu hàng từ {$sourceWarehouseName} sang {$targetWarehouseName}.";

        return back()->with('success', $actionMessage.' Đơn sẽ chỉ xuất hiện tại kho mới để tiếp tục đóng hàng.'.$goodsMessage);
    }

    private function createPackingOrderGoodsTransfer(
        Order $order,
        int $sourceWarehouseId,
        int $targetWarehouseId,
        int $userId
    ): ?WarehouseInventoryTransfer {
        $order->loadMissing(['items.variant.product']);
        $itemsById = $order->items->keyBy('id');
        $reservations = InventoryReservation::query()
            ->whereIn('order_item_id', $itemsById->keys()->all())
            ->lockForUpdate()
            ->get();
        $shipmentEntries = collect();

        foreach ($reservations as $reservation) {
            $item = $itemsById->get((int) $reservation->order_item_id);
            $inventory = Inventory::query()->lockForUpdate()->find($reservation->inventory_id);
            if (! $item || ! $inventory || (int) $inventory->warehouse_id !== $sourceWarehouseId) {
                continue;
            }

            $quantity = min(
                max(0, (int) $reservation->quantity),
                max(0, (int) $inventory->quantity),
                max(0, (int) $inventory->reserved_quantity)
            );
            if ($quantity <= 0) {
                continue;
            }

            $shipmentEntries->push(compact('reservation', 'inventory', 'item', 'quantity'));
        }

        if ($shipmentEntries->isEmpty()) {
            return null;
        }

        $targetWarehouse = Warehouse::query()->find($targetWarehouseId);
        $transfer = WarehouseInventoryTransfer::create([
            'order_id' => $order->id,
            'source_warehouse_id' => $sourceWarehouseId,
            'target_warehouse_id' => $targetWarehouseId,
            'requested_by' => $userId,
            'status' => WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE,
            'note' => 'Hàng đã gom cho đơn '.($order->code ?: '#'.$order->id)
                .' để '.($targetWarehouse?->name ?? ('Kho #'.$targetWarehouseId)).' tiếp tục đóng hàng.',
            'requested_at' => now(),
        ]);
        $exportDocument = InventoryDocument::create([
            'type' => 'export',
            'document_date' => now()->toDateString(),
            'warehouse_id' => $sourceWarehouseId,
            'supplier_id' => null,
            'shipping_fee' => 0,
            'notes' => 'Gửi hàng đã gom cho đơn '.($order->code ?: '#'.$order->id)
                .' theo phiếu '.$transfer->transfer_code.' sang '.($targetWarehouse?->name ?? ('Kho #'.$targetWarehouseId)),
            'user_id' => $userId,
        ]);

        $groupedItems = [];
        foreach ($shipmentEntries as $entry) {
            $reservation = $entry['reservation'];
            $inventory = $entry['inventory'];
            $item = $entry['item'];
            $quantity = (int) $entry['quantity'];
            $variantId = (int) $inventory->product_variant_id;
            $physicalVariant = ProductVariant::query()->find($variantId);
            $weight = round($quantity * (float) ($physicalVariant?->effective_kg ?? $item->effective_unit_weight), 3);

            $inventory->update([
                'quantity' => max(0, (float) $inventory->quantity - $quantity),
                'reserved_quantity' => max(0, (float) $inventory->reserved_quantity - $quantity),
            ]);
            $reservation->delete();
            InventoryMovement::create([
                'inventory_id' => $inventory->id,
                'quantity' => -$quantity,
                'type' => 'transfer_out',
                'reference_id' => $transfer->id,
                'reference_type' => WarehouseInventoryTransfer::class,
                'user_id' => $userId,
            ]);

            $groupedItems[$variantId] ??= ['quantity' => 0, 'weight_kg' => 0.0];
            $groupedItems[$variantId]['quantity'] += $quantity;
            $groupedItems[$variantId]['weight_kg'] = round($groupedItems[$variantId]['weight_kg'] + $weight, 3);
        }

        foreach ($groupedItems as $variantId => $row) {
            $transfer->items()->create([
                'product_variant_id' => $variantId,
                'quantity' => $row['quantity'],
                'weight_kg' => $row['weight_kg'],
                'unit_cost' => 0,
            ]);
            $exportDocument->items()->create([
                'product_variant_id' => $variantId,
                'quantity' => $row['quantity'],
                'unit_cost' => 0,
                'note' => 'Hàng đã gom cho đơn '.($order->code ?: '#'.$order->id),
            ]);
            ProductVariant::query()->whereKey($variantId)->update([
                'stock' => Inventory::query()->where('product_variant_id', $variantId)->sum('quantity'),
            ]);
        }
        $transfer->update(['export_document_id' => $exportDocument->id]);

        return $transfer;
    }

    private function releaseOrderReservations(Order $order): void
    {
        $order->loadMissing('items');
        foreach ($order->items as $item) {
            $reservations = InventoryReservation::query()
                ->where('order_item_id', $item->id)
                ->lockForUpdate()
                ->get();

            foreach ($reservations as $reservation) {
                $inventory = Inventory::query()->lockForUpdate()->find($reservation->inventory_id);
                if ($inventory) {
                    $inventory->reserved_quantity = max(0, (int) $inventory->reserved_quantity - (int) $reservation->quantity);
                    $inventory->save();
                }
            }

            InventoryReservation::query()->where('order_item_id', $item->id)->delete();
        }
    }

    /** Reserve as much as possible at the destination; missing stock remains a backorder. */
    public function reserveOrderStockAtWarehouse(Order $order, int $warehouseId, array $incomingQuantitiesByVariant = []): void
    {
        $order->loadMissing(['items.variant', 'items.packingSizeAllocations']);
        $incomingRemaining = collect($incomingQuantitiesByVariant)
            ->mapWithKeys(fn ($quantity, $variantId) => [(int) $variantId => max(0, (int) $quantity)])
            ->all();
        foreach ($order->items as $item) {
            $allocations = $item->packingSizeAllocations;
            $requirements = $allocations->sum('quantity') === (int) $item->quantity
                ? $allocations->map(fn ($allocation) => [(int) $allocation->product_variant_id, (int) $allocation->quantity])
                : collect([[(int) $item->product_variant_id, (int) $item->quantity]]);

            foreach ($requirements as [$variantId, $desiredQty]) {
                $alreadyReserved = (int) InventoryReservation::query()
                    ->join('inventories', 'inventories.id', '=', 'inventory_reservations.inventory_id')
                    ->where('inventory_reservations.order_item_id', $item->id)
                    ->where('inventories.warehouse_id', $warehouseId)
                    ->where('inventories.product_variant_id', $variantId)
                    ->sum('inventory_reservations.quantity');
                $outstandingQty = max(0, $desiredQty - $alreadyReserved);
                $incomingCredit = min($outstandingQty, (int) ($incomingRemaining[$variantId] ?? 0));
                $incomingRemaining[$variantId] = max(0, (int) ($incomingRemaining[$variantId] ?? 0) - $incomingCredit);
                $requiredQty = max(0, $outstandingQty - $incomingCredit);
                if ($requiredQty <= 0) {
                    continue;
                }

                $inventory = Inventory::query()
                    ->lockForUpdate()
                    ->where('product_variant_id', $variantId)
                    ->where('warehouse_id', $warehouseId)
                    ->first();
                if (! $inventory) {
                    continue;
                }

                $availableQty = max(0, (int) $inventory->quantity - (int) $inventory->reserved_quantity);
                $reserveQty = min($requiredQty, $availableQty);
                if ($reserveQty <= 0) {
                    continue;
                }

                $inventory->increment('reserved_quantity', $reserveQty);
                InventoryReservation::create([
                    'order_item_id' => $item->id,
                    'inventory_id' => $inventory->id,
                    'quantity' => $reserveQty,
                    'reserved_at' => now(),
                ]);
            }
        }
    }

    private function attachCustomerFeedbackContext(Collection $orders): void
    {
        if ($orders->isEmpty() || ! Schema::hasColumn('orders', 'customer_feedback_status')) {
            $orders->each(fn (Order $order) => $order->setAttribute('customer_feedback_context', [
                'has_feedback' => false,
                'highest_status' => null,
                'highest_meta' => Order::customerFeedbackMeta(null),
                'recent' => [],
            ]));

            return;
        }

        $customerIds = $orders->pluck('customer_id')->filter()->unique()->values();
        if ($customerIds->isEmpty()) {
            return;
        }

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
                    'code' => (string) ($feedbackOrder->code ?: '#'.$feedbackOrder->id),
                    'status' => (string) $feedbackOrder->customer_feedback_status,
                    'meta' => Order::customerFeedbackMeta((string) $feedbackOrder->customer_feedback_status),
                    'note' => (string) $feedbackOrder->customer_feedback_note,
                    'sale_review' => (string) ($feedbackOrder->customer_feedback_sale_review ?? ''),
                    'images' => collect($feedbackOrder->customer_feedback_images ?? [])->map(fn ($path) => [
                        'path' => (string) $path,
                        'url' => asset('storage/'.ltrim((string) $path, '/')),
                    ])->values()->all(),
                    'user' => (string) ($feedbackOrder->customerFeedbackUser?->name ?? ''),
                    'at' => optional($feedbackOrder->customer_feedback_at ?? $feedbackOrder->updated_at)->format('d/m/Y H:i'),
                ])->values()->all(),
            ]);
        });
    }

    public function createTransferRequest(Request $request, Order $order)
    {
        $this->authorizePackingOrderAccess($order);

        $request->validate([
            'target_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'shipper_id' => ['required', 'integer', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! in_array($order->status, self::PACKED_STATUSES, true)) {
            return back()->with('error', 'Chỉ có thể điều chuyển đơn đã đóng gói xong.');
        }

        $managedWarehouseId = Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null;

        // Lấy kho nguồn từ order, nếu không có thì tìm từ lịch sử đóng gói
        $sourceWarehouseId = (int) ($order->warehouse_id ?? 0);
        if ($sourceWarehouseId <= 0) {
            $order->loadMissing('histories.user');
            $packingHistory = $order->histories
                ->whereIn('action', ['complete_packing', 'warehouse_complete_packing'])
                ->sortByDesc('id')
                ->first();
            $sourceWarehouseId = (int) ($packingHistory?->user?->warehouse_id ?? 0);
        }
        // Nếu vẫn không xác định được kho nguồn thì fallback vào kho của user hiện tại
        if ($sourceWarehouseId <= 0 && $managedWarehouseId) {
            $sourceWarehouseId = $managedWarehouseId;
        }
        if ($sourceWarehouseId <= 0) {
            return back()->with('error', 'Không xác định được kho nguồn của đơn. Vui lòng kiểm tra lại thông tin đơn hàng.');
        }
        // Nếu đơn chưa có warehouse_id thì cập nhật luôn
        if ((int) ($order->warehouse_id ?? 0) <= 0) {
            $order->update(['warehouse_id' => $sourceWarehouseId]);
        }

        if ($managedWarehouseId && $managedWarehouseId !== $sourceWarehouseId) {
            return back()->with('error', 'Bạn chỉ có thể tạo điều chuyển cho đơn thuộc kho mình quản lý.');
        }

        $targetWarehouseId = (int) $request->input('target_warehouse_id');
        if ($targetWarehouseId === $sourceWarehouseId) {
            return back()->with('error', 'Kho nhận phải khác kho gửi.');
        }

        $shipperId = (int) $request->input('shipper_id');
        $shipper = User::query()
            ->where('id', $shipperId)
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['shipper', 'manager_shipper']);
            })
            ->first();

        if (! $shipper) {
            return back()->with('error', 'Người nhận vận chuyển không phải shipper hợp lệ.');
        }

        $activeTransfer = WarehouseTransfer::query()
            ->where('order_id', $order->id)
            ->whereIn('status', [
                WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP,
                WarehouseTransfer::STATUS_IN_TRANSIT,
                WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE,
            ])
            ->exists();

        if ($activeTransfer) {
            return back()->with('error', 'Đơn này đang có phiếu điều chuyển chưa hoàn tất.');
        }

        $packedTotalWeight = $order->transferBaselineWeight();

        $transfer = WarehouseTransfer::create([
            'order_id' => $order->id,
            'source_warehouse_id' => $sourceWarehouseId,
            'target_warehouse_id' => $targetWarehouseId,
            'shipper_id' => $shipperId,
            'status' => WarehouseTransfer::STATUS_PENDING_SHIPPER_PICKUP,
            'note' => trim((string) $request->input('note', '')) ?: null,
            'packed_total_weight' => $packedTotalWeight,
        ]);

        $targetWarehouse = Warehouse::query()->find($targetWarehouseId);

        OrderHistory::create([
            'order_id' => $order->id,
            'action' => 'warehouse_transfer_requested',
            'user_id' => Auth::id(),
            'role' => $this->packingActorRole(),
            'status_before' => $order->status,
            'status_after' => $order->status,
            'note' => 'Tạo phiếu điều chuyển #'.$transfer->id
                .' đến kho '.($targetWarehouse?->name ?? ('ID '.$targetWarehouseId))
                .' và giao shipper '.$shipper->name,
        ]);

        return back()->with('success', 'Đã tạo phiếu điều chuyển và chờ shipper nhận hàng.');
    }

    public function incomingTransfers(Request $request)
    {
        $managedWarehouseId = Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : Carbon::today()->toDateString();

        $transfers = WarehouseTransfer::query()
            ->with([
                'order.customer',
                'order.items.variant.product',
                'order.orderTransfer.dispatchEntry.slip',
                'sourceWarehouse',
                'targetWarehouse',
                'shipper',
            ])
            ->when($managedWarehouseId, fn ($query) => $query->where('target_warehouse_id', $managedWarehouseId))
            ->whereHas('order')
            ->whereIn('status', [
                WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE,
                WarehouseTransfer::STATUS_RECEIVED_COMPLETED,
            ])
            ->where(function ($query) use ($selectedDate): void {
                // Hàng đã được shipper giao phải luôn hiện cho kho đích để tiếp nhận,
                // kể cả ngày giao của đơn khác ngày đang xem. Chỉ lịch sử hoàn tất
                // mới tuân theo bộ lọc ngày.
                $query->where('status', WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE)
                    ->orWhere(function ($completedQuery) use ($selectedDate): void {
                        $completedQuery
                            ->where('status', WarehouseTransfer::STATUS_RECEIVED_COMPLETED)
                            ->whereHas('order', fn ($orderQuery) => $orderQuery->forDeliveryDate($selectedDate));
                    });
            })
            ->orderByRaw("CASE WHEN status = 'delivered_waiting_receive' THEN 0 ELSE 1 END")
            ->orderByDesc('delivered_at')
            ->orderByDesc('id')
            ->get();

        // Đánh số thứ tự cho từng transfer (giống daily_sequence)
        $sequence = 1;
        foreach ($transfers as $transfer) {
            $transfer->sequence_number = $sequence;
            if ($transfer->order) {
                $transfer->order->daily_sequence = $sequence;
            }
            $sequence++;
        }

        return view('warehouse.transfers.incoming', compact('transfers', 'managedWarehouseId', 'selectedDate'));
    }

    public function confirmTransferReceipt(Request $request, WarehouseTransfer $transfer)
    {
        if ($transfer->status !== WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE) {
            return back()->with('error', 'Phiếu điều chuyển không ở trạng thái chờ tiếp nhận.');
        }

        $managedWarehouseId = Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        if ($managedWarehouseId && (int) $transfer->target_warehouse_id !== $managedWarehouseId) {
            return back()->with('error', 'Bạn chỉ có thể tiếp nhận hàng cho kho mình quản lý.');
        }

        $validated = $request->validate([
            'item_weights' => ['required', 'array', 'min:1'],
            'item_weights.*.order_item_id' => ['required', 'integer'],
            'item_weights.*.received_weight' => ['required', 'numeric', 'min:0'],
            'receive_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $transfer->loadMissing(['order.items']);
        $order = $transfer->order;
        if (! $order) {
            return back()->with('error', 'Không tìm thấy đơn hàng của phiếu điều chuyển.');
        }

        $orderItemsById = $order->items->keyBy('id');
        $requiredIds = $order->items->pluck('id')->map(fn ($id) => (int) $id)->sort()->values();
        $submittedIds = collect($validated['item_weights'])
            ->pluck('order_item_id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values();

        if ($requiredIds->all() !== $submittedIds->all()) {
            return back()->with('error', 'Vui lòng nhập đủ cân nặng tiếp nhận cho tất cả sản phẩm.')->withInput();
        }

        $receivedWeights = [];
        $receivedTotalWeight = 0.0;

        DB::transaction(function () use ($transfer, $order, $validated, $orderItemsById, &$receivedWeights, &$receivedTotalWeight): void {
            $document = InventoryDocument::create([
                'type' => 'import',
                'document_date' => now()->toDateString(),
                'warehouse_id' => $transfer->target_warehouse_id,
                'notes' => 'Nhap kho dieu chuyen don #'.$order->code.' [WHT#'.$transfer->id.']',
                'shipping_fee' => 0,
                'user_id' => Auth::id(),
            ]);

            foreach ($validated['item_weights'] as $weightData) {
                $orderItemId = (int) $weightData['order_item_id'];
                $receivedWeight = round((float) $weightData['received_weight'], 3);
                $orderItem = $orderItemsById->get($orderItemId);
                if (! $orderItem) {
                    continue;
                }

                $qty = (int) ($orderItem->quantity ?? 0);
                if ($qty > 0) {
                    $document->items()->create([
                        'product_variant_id' => $orderItem->product_variant_id,
                        'quantity' => $qty,
                        'unit_cost' => (float) ($orderItem->price ?? 0),
                    ]);

                    $inventory = Inventory::firstOrCreate(
                        [
                            'product_variant_id' => $orderItem->product_variant_id,
                            'warehouse_id' => $transfer->target_warehouse_id,
                        ],
                        ['quantity' => 0, 'reserved_quantity' => 0]
                    );

                    InventoryMovement::create([
                        'inventory_id' => $inventory->id,
                        'quantity' => $qty,
                        'type' => 'import',
                        'reference_id' => $document->id,
                        'reference_type' => InventoryDocument::class,
                        'user_id' => Auth::id(),
                    ]);

                    $inventory->increment('quantity', $qty);

                    $totalStock = (int) Inventory::query()
                        ->where('product_variant_id', $orderItem->product_variant_id)
                        ->sum('quantity');

                    ProductVariant::query()
                        ->where('id', $orderItem->product_variant_id)
                        ->update(['stock' => $totalStock]);
                }

                $receivedWeights[] = [
                    'order_item_id' => $orderItemId,
                    'product_variant_id' => (int) $orderItem->product_variant_id,
                    'received_weight' => $receivedWeight,
                ];
                $receivedTotalWeight += $receivedWeight;
            }

            if (Schema::hasColumn('orders', 'warehouse_id')) {
                $order->update([
                    'warehouse_id' => $transfer->target_warehouse_id,
                ]);
            }

            $packedTotalWeight = (float) ($transfer->packed_total_weight ?? 0);
            $weightLoss = round($packedTotalWeight - $receivedTotalWeight, 3);

            $transfer->update([
                'status' => WarehouseTransfer::STATUS_RECEIVED_COMPLETED,
                'import_document_id' => $document->id,
                'received_by' => Auth::id(),
                'received_at' => now(),
                'received_weights' => $receivedWeights,
                'received_total_weight' => round($receivedTotalWeight, 3),
                'weight_loss' => $weightLoss,
                'note' => trim((string) ($validated['receive_note'] ?? '')) ?: $transfer->note,
            ]);

            OrderHistory::create([
                'order_id' => $order->id,
                'action' => 'warehouse_transfer_received',
                'user_id' => Auth::id(),
                'role' => 'warehouse',
                'status_before' => $order->status,
                'status_after' => $order->status,
                'note' => 'Kho da tiep nhan dieu chuyen #'.$transfer->id.' | Hao hụt KL: '.$weightLoss.' kg',
            ]);
        });

        return back()->with('success', 'Đã tiếp nhận hàng điều chuyển, tạo phiếu nhập kho và cập nhật tồn kho thành công.');
    }

    public function rollbackIncomingTransfer(Request $request, WarehouseTransfer $transfer)
    {
        if ($transfer->status !== WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE) {
            return back()->with('error', 'Phiếu điều chuyển không còn ở trạng thái chờ tiếp nhận.');
        }

        $managedWarehouseId = Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        if ($managedWarehouseId && (int) $transfer->target_warehouse_id !== $managedWarehouseId) {
            return back()->with('error', 'Bạn chỉ có thể từ chối phiếu điều chuyển của kho mình quản lý.');
        }

        $validated = $request->validate([
            'rollback_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $transfer->loadMissing(['order', 'sourceWarehouse', 'targetWarehouse']);
        $order = $transfer->order;

        $reason = trim((string) ($validated['rollback_note'] ?? ''));
        $noteParts = [
            'Kho nhan tu choi tiep nhan truoc khi nhap kho (giu nguyen trang dieu chuyen).',
            'Can xem xet nghiep vu dieu chinh kho neu co chenh lech.',
        ];

        if ($reason !== '') {
            $noteParts[] = 'Ly do: '.$reason;
        }

        $transfer->update([
            'status' => WarehouseTransfer::STATUS_CANCELLED,
            'note' => implode(' | ', $noteParts),
        ]);

        if ($order) {
            OrderHistory::create([
                'order_id' => $order->id,
                'action' => 'warehouse_transfer_rolled_back_before_receive',
                'user_id' => Auth::id(),
                'role' => 'warehouse',
                'status_before' => $order->status,
                'status_after' => $order->status,
                'note' => 'Kho nhan tu choi phieu dieu chuyen #'.$transfer->id
                    .' truoc khi nhap kho. Kho gui: '.($transfer->sourceWarehouse?->name ?? 'N/A')
                    .'; Kho nhan: '.($transfer->targetWarehouse?->name ?? 'N/A')
                    .($reason !== '' ? '; Ly do: '.$reason : ''),
            ]);
        }

        return back()->with('success', 'Đã từ chối tiếp nhận phiếu điều chuyển.');
    }

    /**
     * Start packing: ready_to_pack → packing
     */
    public function startPacking(Request $request, Order $order)
    {
        $this->authorizePackingOrderAccess($order);

        $validated = $request->validate([
            'packing_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);
        $hasPackingDate = $request->filled('packing_date');
        $packingDate = (string) ($validated['packing_date'] ?? now()->toDateString());

        $belongsToPackingDate = ! $hasPackingDate || Order::query()
            ->whereKey($order->id)
            ->forPackingDate($packingDate)
            ->exists();

        if (! $belongsToPackingDate) {
            $message = 'Đơn hàng không thuộc ngày đóng hàng đã chọn.';

            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        if ($order->warehouse_adjustment_status === Order::WAREHOUSE_ADJUSTMENT_STATUS_PENDING_SALE_CONFIRMATION) {
            $message = 'Đơn đang chờ sale xác nhận thay đổi từ kho. Tạm thời chưa thể đóng hàng.';

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $message,
                ], 422);
            }

            return back()->with('error', $message);
        }

        if ($order->warehouse_adjustment_status === Order::WAREHOUSE_ADJUSTMENT_STATUS_SALE_REJECTED) {
            $message = 'Sale đã từ chối yêu cầu điều chỉnh. Vui lòng cập nhật lại và gửi yêu cầu mới trước khi đóng hàng.';

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $message,
                ], 422);
            }

            return back()->with('error', $message);
        }

        if (! $this->canProcessOrderOnCurrentRun($order)) {
            $message = 'Chỉ được xử lý đơn có ngày hôm nay.';

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $message,
                ], 422);
            }

            return back()->with('error', $message);
        }

        if (! in_array($order->status, self::READY_TO_PACK_STATUSES, true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Đơn hàng không ở trạng thái Chờ đóng gói.',
                ], 422);
            }

            return back()->with('error', 'Đơn hàng không ở trạng thái Chờ đóng gói.');
        }

        $currentUser = Auth::user();
        $managedWarehouseId = $currentUser?->warehouse_id ? (int) $currentUser->warehouse_id : null;
        $currentOrderWarehouseId = (int) ($order->warehouse_id ?? 0);

        if ($managedWarehouseId && ($currentUser?->hasRole('warehouse') || $currentUser?->hasRole('package'))) {
            if ($currentOrderWarehouseId > 0 && $currentOrderWarehouseId !== $managedWarehouseId) {
                $message = 'Đơn hàng này thuộc kho khác, bạn không thể bắt đầu đóng gói.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'ok' => false,
                        'message' => $message,
                    ], 403);
                }

                return back()->with('error', $message);
            }
        }

        $stockCheck = $this->evaluateSingleOrderStock($order, $managedWarehouseId, $packingDate);

        if (! ($stockCheck['can_start_packing'] ?? false)) {
            $message = 'Không đủ tồn kho để đóng hàng';

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $message,
                    'stock_check' => [
                        'has_shortage' => true,
                        'can_start_packing' => false,
                        'shortages' => $stockCheck['shortages'] ?? [],
                        'import_url' => route($request->routeIs('package.*') ? 'package.inventory' : 'warehouse.stock-in'),
                        'import_hint' => 'Bạn cần Nhập kho để thực hiện công việc tiếp',
                    ],
                ], 422);
            }

            return back()->with('error', $message);
        }

        $statusBefore = $order->status;

        $updatePayload = ['status' => Order::STATUS_PACKING];
        $assignedWarehouseOnStart = false;
        if ($managedWarehouseId && $currentOrderWarehouseId <= 0) {
            $updatePayload['warehouse_id'] = $managedWarehouseId;
            $assignedWarehouseOnStart = true;
        }
        $order->update($updatePayload);

        OrderHistory::create([
            'order_id' => $order->id,
            'action' => 'start_packing',
            'user_id' => Auth::id(),
            'role' => $this->packingActorRole(),
            'status_before' => $statusBefore,
            'status_after' => Order::STATUS_PACKING,
            'note' => 'Bắt đầu đóng gói đơn hàng'
                .' cho ngày '.Carbon::parse($packingDate)->format('d/m/Y')
                .($assignedWarehouseOnStart ? ' [assigned_warehouse_on_start]' : ''),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Đã bắt đầu đóng gói đơn #'.$order->code.' cho ngày '.Carbon::parse($packingDate)->format('d/m/Y'),
                'order' => [
                    'id' => $order->id,
                    'status' => Order::STATUS_PACKING,
                    'status_label' => 'Đang đóng',
                    'status_class' => 'bg-warning text-dark',
                ],
            ]);
        }

        return back()->with('success', 'Đã bắt đầu đóng gói đơn #'.$order->code.' cho ngày '.Carbon::parse($packingDate)->format('d/m/Y'));
    }

    private function evaluateSingleOrderStock(Order $order, ?int $warehouseId, ?string $packingDate = null): array
    {
        $singleCollection = collect([$order]);
        $result = $this->buildPackingQueueStockGuards(
            $singleCollection,
            $warehouseId,
            $packingDate ?? now()->toDateString()
        );

        return $result['guards'][$order->id] ?? [
            'has_shortage' => false,
            'can_start_packing' => true,
            'message' => null,
            'shortages' => [],
        ];
    }

    private function buildPackingSizeOptions(Collection $orders, array $stockGuards, ?int $warehouseId): array
    {
        $eligibleItems = $orders->flatMap(function (Order $order) use ($stockGuards) {
            $shortItemIds = collect($stockGuards[$order->id]['shortages'] ?? [])
                ->pluck('order_item_id')
                ->map(fn ($id) => (int) $id);

            return $order->items->filter(function ($item) use ($shortItemIds) {
                return abs((float) ($item->variant?->size ?? 0) - 2.5) < 0.0001
                    && ($shortItemIds->contains((int) $item->id) || $item->packingSizeAllocations->isNotEmpty());
            });
        })->values();

        if ($eligibleItems->isEmpty()) {
            return [];
        }

        $productIds = $eligibleItems->pluck('variant.product_id')->filter()->unique()->values();
        $variantsByProduct = ProductVariant::query()
            ->with(['inventories' => fn ($query) => $query->when($warehouseId, fn ($scope) => $scope->where('warehouse_id', $warehouseId))])
            ->whereIn('product_id', $productIds->all())
            ->get()
            ->filter(fn (ProductVariant $variant) => in_array(round((float) $variant->size, 1), [2.4, 2.5, 2.6], true))
            ->groupBy('product_id');

        $reservationByItemAndInventory = InventoryReservation::query()
            ->whereIn('order_item_id', $eligibleItems->pluck('id')->all())
            ->get()
            ->groupBy('order_item_id')
            ->map(fn ($rows) => $rows->pluck('quantity', 'inventory_id'));

        return $eligibleItems->mapWithKeys(function ($item) use ($variantsByProduct, $reservationByItemAndInventory) {
            $saved = $item->packingSizeAllocations->pluck('quantity', 'product_variant_id');
            $options = collect($variantsByProduct->get((int) $item->variant->product_id, collect()))
                ->map(function (ProductVariant $variant) use ($item, $saved, $reservationByItemAndInventory) {
                    $inventories = $variant->inventories;
                    $ownReserved = (int) $inventories->sum(function (Inventory $inventory) use ($item, $reservationByItemAndInventory) {
                        return (int) ($reservationByItemAndInventory->get($item->id)?->get($inventory->id) ?? 0);
                    });
                    $available = (int) $inventories->sum(fn (Inventory $inventory) => max(0, (int) $inventory->quantity - (int) $inventory->reserved_quantity));

                    return [
                        'variant_id' => (int) $variant->id,
                        'size' => (float) $variant->size,
                        'name' => (string) ($variant->name ?: $variant->sku),
                        'available' => $available + $ownReserved,
                        'quantity' => (int) ($saved[$variant->id] ?? ((int) $variant->id === (int) $item->product_variant_id ? $item->quantity : 0)),
                    ];
                })
                ->sortBy('size')
                ->values();

            return [(int) $item->id => $options];
        })->all();
    }

    private function cuttingOrdersForVariant(ProductVariant $variant, ?int $warehouseId, string $forDate): Collection
    {
        $queueStatuses = array_merge(self::READY_TO_PACK_STATUSES, [Order::STATUS_PACKING]);

        return Order::query()
            ->with(['customer', 'items.product', 'items.variant.product'])
            ->where(function ($query) {
                $query->whereNull('is_return_order')
                    ->orWhere('is_return_order', false);
            })
            ->whereIn('status', $queueStatuses)
            ->forPackingDate($forDate)
            ->when($warehouseId, fn ($query) => $query->where(function ($scope) use ($warehouseId) {
                $scope->where('warehouse_id', $warehouseId)->orWhereNull('warehouse_id');
            }))
            ->whereHas('items', fn ($query) => $query->where('product_variant_id', $variant->id))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    private function buildCuttingPlansForGuards(array $guards, ?int $warehouseId): array
    {
        $shortages = collect($guards)
            ->flatMap(function (array $guard, int|string $orderId) {
                return collect($guard['shortages'] ?? [])->map(function (array $shortage) use ($orderId) {
                    $shortage['order_id'] = (int) ($shortage['order_id'] ?? $orderId);

                    return $shortage;
                });
            })
            ->filter(fn (array $shortage) => (float) ($shortage['short_qty'] ?? 0) > 0)
            ->values();

        if ($shortages->isEmpty()) {
            return [];
        }

        $variantIds = $shortages
            ->pluck('variant_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $cutVariants = ProductVariant::query()
            ->with('product')
            ->whereIn('id', $variantIds->all())
            ->whereHas('product', fn ($query) => $query->where('product_type', Product::TYPE_CUT))
            ->get()
            ->keyBy('id');

        if ($cutVariants->isEmpty()) {
            return [];
        }

        $service = app(ProductCuttingService::class);
        $plans = [];

        foreach ($shortages as $shortage) {
            $variantId = (int) ($shortage['variant_id'] ?? 0);
            $orderId = (int) ($shortage['order_id'] ?? 0);
            $targetVariant = $cutVariants->get($variantId);
            if (! $targetVariant || $orderId <= 0) {
                continue;
            }

            $plan = $service->planForDemand($targetVariant, $warehouseId, (float) ($shortage['short_qty'] ?? 0));
            $plan['material_options'] = $service->sourceMaterialOptions($targetVariant, $warehouseId);
            $plan['shortage'] = $shortage;
            $plans[$orderId][$variantId] = $plan;
        }

        return $plans;
    }

    private function buildPackingQueueStockGuards(Collection $orders, ?int $warehouseId, ?string $forDate = null): array
    {
        $queueStatuses = array_merge(self::READY_TO_PACK_STATUSES, [Order::STATUS_PACKING]);
        $forDate = $forDate ?? now()->toDateString();

        // IDs of the orders currently displayed on the page
        $displayedOrderIds = $orders
            ->filter(fn (Order $order) => in_array($order->status, $queueStatuses, true))
            ->pluck('id')
            ->flip()
            ->toArray();

        if (empty($displayedOrderIds)) {
            return ['guards' => [], 'remaining_by_variant' => []];
        }

        // Load ALL queued orders from the SAME packing date for FIFO simulation
        // (oldest first). Orders belonging to another date never enter this pool.
        $allQueueOrders = Order::with(['items.product', 'items.variant.product', 'items.packingSizeAllocations.variant'])
            ->whereIn('status', $queueStatuses)
            ->forPackingDate($forDate)
            ->when($warehouseId, fn ($query) => $query->where(function ($scope) use ($warehouseId) {
                $scope->where('warehouse_id', $warehouseId)->orWhereNull('warehouse_id');
            }))
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($allQueueOrders->isEmpty()) {
            return ['guards' => [], 'remaining_by_variant' => []];
        }

        $variantIds = $allQueueOrders
            ->flatMap(fn (Order $order) => $order->items->flatMap(function ($item) {
                return collect([(int) $item->product_variant_id])
                    ->merge($item->packingSizeAllocations->pluck('product_variant_id'));
            }))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        // Each date owns an independent packing pool. Its starting quantity is
        // the closing inventory snapshot of that date. Global reservation rows
        // can belong to orders from other dates, so they must not reduce this
        // pool. Orders from this date are applied exactly once by the FIFO loop
        // below; orders from every other date are excluded by forPackingDate().
        $stockByVariant = $this->getStockAtDate($variantIds, $warehouseId, $forDate);

        // Running pool — decremented only when a complete order can be packed (FIFO)
        $remainingByVariant = array_map(fn ($v) => (float) $v, $stockByVariant);

        $guards = [];

        foreach ($allQueueOrders as $order) {
            $shortages = [];
            $pendingDeductions = [];  // staged; applied only if order has NO shortage

            foreach ($order->items as $item) {
                $neededQty = (float) $item->quantity;
                if ($neededQty <= 0) {
                    continue;
                }

                $savedAllocations = $item->packingSizeAllocations;
                $requirements = $savedAllocations->sum('quantity') === (int) $neededQty
                    ? $savedAllocations->map(fn ($allocation) => [
                        'variant_id' => (int) $allocation->product_variant_id,
                        'quantity' => (float) $allocation->quantity,
                        'name' => (string) ($allocation->variant?->name ?? ('SP #'.$allocation->product_variant_id)),
                    ])
                    : collect([[
                        'variant_id' => (int) $item->product_variant_id,
                        'quantity' => $neededQty,
                        'name' => (string) ($item->variant?->name ?? $item->product?->name ?? ('SP #'.$item->product_variant_id)),
                    ]]);

                foreach ($requirements as $requirement) {
                    $variantId = (int) $requirement['variant_id'];
                    $requiredQty = (float) $requirement['quantity'];
                    $remaining = max(0, (float) ($remainingByVariant[$variantId] ?? 0.0) - (float) ($pendingDeductions[$variantId] ?? 0.0));
                    if ($variantId <= 0 || $requiredQty <= 0) {
                        continue;
                    }
                    if ($remaining < $requiredQty) {
                        $shortages[] = [
                            'order_id' => (int) $order->id,
                            'order_code' => (string) $order->code,
                            'order_item_id' => (int) $item->id,
                            'variant_id' => $variantId,
                            'variant_name' => (string) $requirement['name'],
                            'required_qty' => $requiredQty,
                            'available_qty' => $remaining,
                            'short_qty' => round($requiredQty - $remaining, 3),
                            'reason' => $remaining <= 0 ? 'blocked_by_prior_order' : 'insufficient_stock',
                        ];
                    } else {
                        $pendingDeductions[$variantId] = ($pendingDeductions[$variantId] ?? 0.0) + $requiredQty;
                    }
                }
            }

            $hasShortage = ! empty($shortages);

            if (! $hasShortage) {
                foreach ($pendingDeductions as $vid => $consume) {
                    $remainingByVariant[$vid] = max(0.0, ($remainingByVariant[$vid] ?? 0.0) - $consume);
                }
            }
            // Order with shortage: deduct NOTHING — stock stays for later orders

            if (isset($displayedOrderIds[$order->id])) {
                $guards[$order->id] = [
                    'has_shortage' => $hasShortage,
                    'can_start_packing' => ! $hasShortage,
                    'message' => $hasShortage ? 'Không đủ tồn kho để đóng hàng' : null,
                    'shortages' => $shortages,
                ];
            }
        }

        return [
            'guards' => $guards,
            'remaining_by_variant' => $remainingByVariant,  // stock left after FIFO queue consumed
        ];
    }

    /**
     * Reconstruct stock quantity for each variant at the END of a given date.
     * Formula: qty_at_date = current_quantity - SUM(movements after date)
     * Movements after the selected date are reversed to get the historical snapshot.
     */
    private function getStockAtDate(Collection $variantIds, ?int $warehouseId, string $date): array
    {
        if ($variantIds->isEmpty()) {
            return [];
        }

        $inventoryQuery = Inventory::query()
            ->select('id', 'product_variant_id', 'quantity', 'reserved_quantity')
            ->whereIn('product_variant_id', $variantIds->all());

        if ($warehouseId) {
            $inventoryQuery->where('warehouse_id', $warehouseId);
        }

        $inventories = $inventoryQuery->get();

        if ($inventories->isEmpty()) {
            return [];
        }

        // Reverse movements whose BUSINESS date is after the selected date.
        // A stock document can be entered today for a prior document_date; in that
        // case it belongs to the prior day's snapshot, not today's creation time.
        $movementsAfter = $this->movementDeltasAfterEffectiveDate(
            $inventories->pluck('id'),
            $date
        );

        $result = [];
        foreach ($inventories as $inv) {
            $vid = (int) $inv->product_variant_id;
            $currentQty = (int) $inv->quantity;
            $afterDelta = (int) ($movementsAfter[$inv->id] ?? 0);
            $qtyAtDate = $currentQty - $afterDelta;

            $result[$vid] = ($result[$vid] ?? 0) + max(0, $qtyAtDate);
        }

        return $result;
    }

    private function movementDeltasAfterEffectiveDate(Collection $inventoryIds, string $date): array
    {
        if ($inventoryIds->isEmpty()) {
            return [];
        }

        return InventoryMovement::query()
            ->leftJoin('inventory_documents as movement_documents', function ($join): void {
                $join->on('movement_documents.id', '=', 'inventory_movements.reference_id')
                    ->where('inventory_movements.reference_type', InventoryDocument::class);
            })
            ->leftJoin('google_sheet_inventory_syncs as movement_sheet_syncs', function ($join): void {
                $join->on('movement_sheet_syncs.id', '=', 'inventory_movements.reference_id')
                    ->where('inventory_movements.reference_type', GoogleSheetInventorySync::class);
            })
            ->whereIn('inventory_movements.inventory_id', $inventoryIds->all())
            ->where(function ($query) use ($date): void {
                $query->where(function ($documentMovement) use ($date): void {
                    $documentMovement
                        ->where('inventory_movements.reference_type', InventoryDocument::class)
                        ->where(function ($effectiveDate) use ($date): void {
                            $effectiveDate->whereDate('movement_documents.document_date', '>', $date)
                                ->orWhere(function ($missingDocument) use ($date): void {
                                    $missingDocument->whereNull('movement_documents.id')
                                        ->whereDate('inventory_movements.created_at', '>', $date);
                                });
                        });
                })->orWhere(function ($sheetMovement) use ($date): void {
                    $sheetMovement
                        ->where('inventory_movements.reference_type', GoogleSheetInventorySync::class)
                        ->where(function ($effectiveDate) use ($date): void {
                            $effectiveDate->whereDate('movement_sheet_syncs.inventory_date', '>', $date)
                                ->orWhere(function ($missingSync) use ($date): void {
                                    $missingSync->whereNull('movement_sheet_syncs.id')
                                        ->whereDate('inventory_movements.created_at', '>', $date);
                                });
                        });
                })->orWhere(function ($otherMovement) use ($date): void {
                    $otherMovement
                        ->where(function ($referenceType): void {
                            $referenceType->whereNull('inventory_movements.reference_type')
                                ->orWhere(function ($knownTypes): void {
                                    $knownTypes->where('inventory_movements.reference_type', '!=', InventoryDocument::class)
                                        ->where('inventory_movements.reference_type', '!=', GoogleSheetInventorySync::class);
                                });
                        })
                        ->whereDate('inventory_movements.created_at', '>', $date);
                });
            })
            ->selectRaw('inventory_movements.inventory_id, COALESCE(SUM(inventory_movements.quantity), 0) as qty_delta')
            ->groupBy('inventory_movements.inventory_id')
            ->pluck('qty_delta', 'inventory_movements.inventory_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    /**
     * Re-run Ráp đơn hàng for ALL dates that still have queued orders.
     * Persists stock_sufficient and stock_shortage_detail on every queued order.
     * Called automatically after any stock-in creation or adjustment.
     */
    private function syncAllQueuedOrdersStockSufficiency(?int $warehouseId): void
    {
        $queueStatuses = array_merge(self::READY_TO_PACK_STATUSES, [Order::STATUS_PACKING]);

        $packingDateSql = 'CASE WHEN accounting_sales_import_batch_id IS NOT NULL '
            .'THEN DATE(delivery_date) ELSE DATE(created_at) END';

        // Find all distinct packing dates that have queued orders.
        $dates = Order::whereIn('status', $queueStatuses)
            ->selectRaw($packingDateSql.' as order_date')
            ->whereRaw($packingDateSql.' IS NOT NULL')
            ->groupByRaw($packingDateSql)
            ->pluck('order_date');

        foreach ($dates as $forDate) {
            $allQueueOrders = Order::with(['items.variant'])
                ->whereIn('status', $queueStatuses)
                ->forPackingDate($forDate)
                ->when($warehouseId, fn ($query) => $query->where(function ($scope) use ($warehouseId) {
                    $scope->where('warehouse_id', $warehouseId)->orWhereNull('warehouse_id');
                }))
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            if ($allQueueOrders->isEmpty()) {
                continue;
            }

            $result = $this->buildPackingQueueStockGuards($allQueueOrders, $warehouseId, $forDate);
            $guards = $result['guards'];

            $sequence = 0;
            foreach ($allQueueOrders as $order) {
                $sequence++;
                $guard = $guards[$order->id] ?? ['has_shortage' => false, 'shortages' => []];
                $hasShortage = (bool) ($guard['has_shortage'] ?? false);

                $order->update([
                    'daily_sequence' => $sequence,
                    'stock_sufficient' => $hasShortage ? 0 : 1,
                    'stock_shortage_detail' => $hasShortage ? ($guard['shortages'] ?? []) : null,
                ]);
            }
        }
    }

    public function refreshQueuedOrdersAfterInventoryChange(int $warehouseId): void
    {
        $this->syncAllQueuedOrdersStockSufficiency($warehouseId);
    }

    private function getTotalQuantityByVariant(Collection $variantIds, ?int $warehouseId): array
    {
        if ($variantIds->isEmpty()) {
            return [];
        }

        $query = Inventory::query()
            ->selectRaw('product_variant_id, COALESCE(SUM(quantity), 0) as total_qty')
            ->whereIn('product_variant_id', $variantIds->all());

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query
            ->groupBy('product_variant_id')
            ->pluck('total_qty', 'product_variant_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    private function getAvailableByVariant(Collection $variantIds, ?int $warehouseId): array
    {
        if ($variantIds->isEmpty()) {
            return [];
        }

        $query = Inventory::query()
            ->selectRaw('product_variant_id, COALESCE(SUM(quantity - reserved_quantity), 0) as available_qty')
            ->whereIn('product_variant_id', $variantIds->all());

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query
            ->groupBy('product_variant_id')
            ->pluck('available_qty', 'product_variant_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    private function getInventorySnapshotByVariant(Collection $variantIds, ?int $warehouseId, string $date): array
    {
        if ($variantIds->isEmpty()) {
            return [];
        }

        $selectedDate = Carbon::parse($date)->toDateString();

        if ($selectedDate === Carbon::today()->toDateString()) {
            return Inventory::query()
                ->selectRaw('product_variant_id, COALESCE(SUM(quantity), 0) as total_qty, COALESCE(SUM(reserved_quantity), 0) as total_reserved, COALESCE(MIN(low_stock_threshold), 5) as low_stock_threshold')
                ->whereIn('product_variant_id', $variantIds->all())
                ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
                ->groupBy('product_variant_id')
                ->get()
                ->mapWithKeys(function ($row) {
                    $quantity = (int) ($row->total_qty ?? 0);
                    $reserved = (int) ($row->total_reserved ?? 0);
                    $lowStockThreshold = (int) ($row->low_stock_threshold ?? 5);

                    return [
                        (int) $row->product_variant_id => [
                            'quantity' => $quantity,
                            'reserved' => $reserved,
                            'available' => max(0, $quantity - $reserved),
                            'low_stock_threshold' => $lowStockThreshold,
                        ],
                    ];
                })
                ->all();
        }

        $historicalQuantity = $this->getStockAtDate($variantIds, $warehouseId, $selectedDate);
        $lowStockThresholds = Inventory::query()
            ->selectRaw('product_variant_id, COALESCE(MIN(low_stock_threshold), 5) as low_stock_threshold')
            ->whereIn('product_variant_id', $variantIds->all())
            ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
            ->groupBy('product_variant_id')
            ->pluck('low_stock_threshold', 'product_variant_id')
            ->map(fn ($value) => (int) $value)
            ->all();

        return collect($variantIds)
            ->mapWithKeys(function ($variantId) use ($historicalQuantity, $lowStockThresholds) {
                $quantity = (int) ($historicalQuantity[(int) $variantId] ?? 0);

                return [
                    (int) $variantId => [
                        'quantity' => $quantity,
                        'reserved' => 0,
                        'available' => $quantity,
                        'low_stock_threshold' => (int) ($lowStockThresholds[(int) $variantId] ?? 5),
                    ],
                ];
            })
            ->all();
    }

    private function getInventorySnapshotStats(?int $warehouseId, string $date): array
    {
        $selectedDate = Carbon::parse($date)->toDateString();

        if ($selectedDate === Carbon::today()->toDateString()) {
            $inventoryBase = Inventory::query()
                ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId));

            $stockAgg = (clone $inventoryBase)
                ->selectRaw('COALESCE(SUM(quantity), 0) as total_qty, COALESCE(SUM(reserved_quantity), 0) as total_reserved')
                ->first();

            return [
                'total_quantity' => (int) ($stockAgg->total_qty ?? 0),
                'total_reserved' => (int) ($stockAgg->total_reserved ?? 0),
                'total_available' => max(0, (int) ($stockAgg->total_qty ?? 0) - (int) ($stockAgg->total_reserved ?? 0)),
                'low_stock' => (clone $inventoryBase)
                    ->whereColumn('quantity', '<=', 'low_stock_threshold')
                    ->where('quantity', '>', 0)
                    ->count(),
                'out_of_stock' => (clone $inventoryBase)
                    ->where('quantity', 0)
                    ->count(),
            ];
        }

        $inventories = Inventory::query()
            ->select('id', 'quantity', 'low_stock_threshold')
            ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
            ->get();

        if ($inventories->isEmpty()) {
            return [
                'total_quantity' => 0,
                'total_reserved' => 0,
                'total_available' => 0,
                'low_stock' => 0,
                'out_of_stock' => 0,
            ];
        }

        $movementsAfter = $this->movementDeltasAfterEffectiveDate(
            $inventories->pluck('id'),
            $selectedDate
        );

        $totalQuantity = 0;
        $lowStock = 0;
        $outOfStock = 0;

        foreach ($inventories as $inventory) {
            $quantity = max(0, (int) $inventory->quantity - (int) ($movementsAfter[$inventory->id] ?? 0));
            $threshold = (int) ($inventory->low_stock_threshold ?: 0);

            $totalQuantity += $quantity;

            if ($quantity <= 0) {
                $outOfStock++;

                continue;
            }

            if ($threshold > 0 && $quantity <= $threshold) {
                $lowStock++;
            }
        }

        return [
            'total_quantity' => $totalQuantity,
            'total_reserved' => 0,
            'total_available' => $totalQuantity,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
        ];
    }

    private function getReservedByOrderItem(Collection $orderItemIds, ?int $warehouseId): array
    {
        if ($orderItemIds->isEmpty()) {
            return [];
        }

        $query = InventoryReservation::query()
            ->selectRaw('inventory_reservations.order_item_id, COALESCE(SUM(inventory_reservations.quantity), 0) as reserved_qty')
            ->whereIn('inventory_reservations.order_item_id', $orderItemIds->all());

        if ($warehouseId) {
            $query->join('inventories', 'inventories.id', '=', 'inventory_reservations.inventory_id')
                ->where('inventories.warehouse_id', $warehouseId);
        }

        return $query
            ->groupBy('inventory_reservations.order_item_id')
            ->pluck('reserved_qty', 'inventory_reservations.order_item_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    /** Save the physical 2.4/2.5/2.6 mix used to fulfil a size-2.5 order line. */
    public function updatePackingSizeAllocation(Request $request, Order $order)
    {
        $this->authorizePackingOrderAccess($order);

        if (! $this->canProcessOrderOnCurrentRun($order)) {
            return back()->with('error', 'Chỉ được bổ sung cơ cấu size cho đơn của ngày hôm nay.');
        }
        if (! in_array($order->status, array_merge(self::READY_TO_PACK_STATUSES, [Order::STATUS_PACKING]), true)) {
            return back()->with('error', 'Chỉ được bổ sung size khi đơn đang chờ hoặc đang đóng hàng.');
        }

        $validated = $request->validate([
            'order_item_id' => ['required', 'integer'],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*' => ['nullable', 'integer', 'min:0'],
        ]);
        $order->loadMissing(['items.variant.product']);
        $item = $order->items->firstWhere('id', (int) $validated['order_item_id']);
        if (! $item || abs((float) ($item->variant?->size ?? 0) - 2.5) > 0.0001) {
            return back()->withErrors(['allocations' => 'Dòng hàng không phải sản phẩm size 2.5 của đơn này.']);
        }

        $allocationInput = collect($validated['allocations'])
            ->mapWithKeys(fn ($quantity, $variantId) => [(int) $variantId => (int) ($quantity ?? 0)])
            ->filter(fn (int $quantity, int $variantId) => $variantId > 0 && $quantity > 0);
        $variants = ProductVariant::query()
            ->whereIn('id', $allocationInput->keys()->all())
            ->where('product_id', (int) $item->variant->product_id)
            ->get()
            ->keyBy('id');
        if ($variants->count() !== $allocationInput->count()
            || $variants->contains(fn (ProductVariant $variant) => ! in_array(round((float) $variant->size, 1), [2.4, 2.5, 2.6], true))) {
            return back()->withErrors(['allocations' => 'Chỉ được dùng size 2.4, 2.5 hoặc 2.6 của cùng sản phẩm.']);
        }

        $orderedQuantity = (int) $item->quantity;
        if ((int) $allocationInput->sum() !== $orderedQuantity) {
            return back()->withErrors(['allocations' => "Tổng số lượng đóng phải bằng {$orderedQuantity} sản phẩm của đơn."]);
        }
        $mainSizeQuantity = (int) $allocationInput
            ->map(fn (int $quantity, int $variantId) => abs((float) $variants[$variantId]->size - 2.5) < 0.0001 ? $quantity : 0)
            ->sum();
        if ($orderedQuantity <= 0 || $mainSizeQuantity * 100 <= $orderedQuantity * 70) {
            return back()->withErrors(['allocations' => 'Tỷ lệ size 2.5 phải lớn hơn 70% tổng số lượng.']);
        }
        $weightedAverage = (float) $allocationInput
            ->map(fn (int $quantity, int $variantId) => $quantity * (float) $variants[$variantId]->size)
            ->sum() / $orderedQuantity;
        if ($weightedAverage < 2.47 - 0.000001 || $weightedAverage > 2.57 + 0.000001) {
            return back()->withErrors(['allocations' => 'Size bình quân phải nằm trong khoảng 2.47–2.57 kg (hiện tại '.number_format($weightedAverage, 3, '.', '').' kg).']);
        }

        $warehouseId = (int) ($request->user()?->warehouse_id ?: $order->warehouse_id ?: 0);
        if ($warehouseId <= 0) {
            return back()->withErrors(['allocations' => 'Không xác định được kho đang đóng hàng.']);
        }

        try {
            DB::transaction(function () use ($item, $allocationInput, $warehouseId): void {
                $oldReservations = InventoryReservation::query()->where('order_item_id', $item->id)->lockForUpdate()->get();
                foreach ($oldReservations as $reservation) {
                    $inventory = Inventory::query()->lockForUpdate()->find($reservation->inventory_id);
                    if ($inventory) {
                        $inventory->update(['reserved_quantity' => max(0, (float) $inventory->reserved_quantity - (float) $reservation->quantity)]);
                    }
                    $reservation->delete();
                }

                foreach ($allocationInput as $variantId => $quantity) {
                    $inventory = Inventory::query()
                        ->where('warehouse_id', $warehouseId)
                        ->where('product_variant_id', $variantId)
                        ->lockForUpdate()
                        ->first();
                    $available = $inventory ? max(0, (int) $inventory->quantity - (int) $inventory->reserved_quantity) : 0;
                    if (! $inventory || $available < $quantity) {
                        $variant = ProductVariant::query()->find($variantId);
                        throw new \RuntimeException('Không đủ tồn kho size '.($variant?->size ?? $variantId)." (cần {$quantity}, khả dụng {$available}).");
                    }
                    $inventory->increment('reserved_quantity', $quantity);
                    InventoryReservation::create([
                        'order_item_id' => $item->id,
                        'inventory_id' => $inventory->id,
                        'quantity' => $quantity,
                        'reserved_at' => now(),
                    ]);
                }

                OrderItemPackingSizeAllocation::query()->where('order_item_id', $item->id)->delete();
                foreach ($allocationInput as $variantId => $quantity) {
                    OrderItemPackingSizeAllocation::create([
                        'order_item_id' => $item->id,
                        'product_variant_id' => $variantId,
                        'quantity' => $quantity,
                    ]);
                }
            });
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['allocations' => $exception->getMessage()]);
        }

        OrderHistory::create([
            'order_id' => $order->id,
            'action' => 'warehouse_update_packing_size_mix',
            'user_id' => Auth::id(),
            'role' => $this->packingActorRole(),
            'status_before' => $order->status,
            'status_after' => $order->status,
            'note' => 'Cơ cấu thực đóng size 2.5: '.$allocationInput->map(
                fn (int $quantity, int $variantId) => $variants[$variantId]->size.' × '.$quantity
            )->join(', ').' · Bình quân '.number_format($weightedAverage, 3, '.', '').' kg.',
        ]);

        return back()->with('success', 'Đã lưu cơ cấu size thực đóng; size 2.5 chiếm '.number_format($mainSizeQuantity * 100 / $orderedQuantity, 1).'% và bình quân '.number_format($weightedAverage, 3).' kg.');
    }

    /** Warehouse/package updates actual packed item weight for an order. */
    public function updateLogistics(Request $request, Order $order)
    {
        $this->authorizePackingOrderAccess($order);

        $expectsJson = $request->expectsJson();

        if (! $this->canProcessOrderOnCurrentRun($order)) {
            $message = 'Chỉ được xử lý đơn có ngày hôm nay.';

            if ($expectsJson) {
                return response()->json([
                    'ok' => false,
                    'message' => $message,
                ], 422);
            }

            return back()->with('error', $message);
        }

        if (! in_array($order->status, self::EDITABLE_LOGISTICS_STATUSES, true)) {
            if ($expectsJson) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Không thể cập nhật kg thực tế ở trạng thái hiện tại của đơn hàng.',
                ], 422);
            }

            return back()->with('error', 'Không thể cập nhật kg thực tế ở trạng thái hiện tại của đơn hàng.');
        }

        $order->loadMissing('items');

        $itemIds = $order->items->pluck('id')->all();

        $rules = [
            'item_id' => ['nullable', 'integer'],
            'item_actual_weight' => ['nullable', 'numeric', 'min:0'],
            'clear_item_weight' => ['nullable', 'boolean'],
            'packing_details' => ['nullable', 'boolean'],
        ];

        if ($request->filled('item_id') && ! $request->boolean('clear_item_weight')) {
            $rules['item_actual_weight'] = ['required', 'numeric', 'min:0'];
        }

        if ($request->boolean('packing_details')) {
            $rules['package_count'] = ['nullable', 'integer', 'min:1', 'max:10000'];
            $rules['packing_specification'] = ['nullable', 'string', 'max:500', 'required_without:package_count'];
        }

        $validated = $request->validate($rules);

        $packingDetailsUpdated = $request->boolean('packing_details');

        if ($packingDetailsUpdated) {
            $order->update([
                'package_count' => filled($validated['package_count'] ?? null) ? (int) $validated['package_count'] : null,
                'packing_specification' => trim((string) ($validated['packing_specification'] ?? '')) ?: null,
            ]);

            OrderHistory::create([
                'order_id' => $order->id,
                'action' => 'warehouse_update_packing_details',
                'user_id' => Auth::id(),
                'role' => $this->packingActorRole(),
                'status_before' => $order->status,
                'status_after' => $order->status,
                'note' => 'Cập nhật đóng gói: '.($order->package_count ? $order->package_count.' bọc' : 'chưa xác định số bọc')
                    .($order->packing_specification ? ' · Quy cách: '.$order->packing_specification : ''),
            ]);

            $message = 'Đã lưu số bọc/quy cách bọc cho đơn #'.$order->code;
            if ($expectsJson) {
                return response()->json([
                    'ok' => true,
                    'message' => $message,
                    'order' => [
                        'id' => (int) $order->id,
                        'package_count' => $order->package_count,
                        'packing_specification' => $order->packing_specification,
                    ],
                ]);
            }

            return back()->with('success', $message);
        }

        $oldWeight = $order->actual_weight;

        if ($request->filled('item_id')) {
            $itemId = (int) $validated['item_id'];
            if (! in_array($itemId, $itemIds, true)) {
                if ($expectsJson) {
                    return response()->json([
                        'ok' => false,
                        'message' => 'Sản phẩm không thuộc đơn hàng này.',
                    ], 422);
                }

                return back()->with('error', 'Sản phẩm không thuộc đơn hàng này.');
            }

            $item = $order->items->firstWhere('id', $itemId);
            if ($item) {
                if ($request->boolean('clear_item_weight')) {
                    $clearedWeight = $item->actual_weight;
                    $item->forceFill([
                        'actual_weight' => null,
                        'packed_weight' => null,
                    ])->save();

                    $hasRemainingWeight = $order->items()->whereNotNull('actual_weight')->exists();
                    $remainingWeight = round((float) $order->items()->sum('actual_weight'), 3);
                    $order->update([
                        'actual_weight' => $hasRemainingWeight ? $remainingWeight : null,
                        'total_weight' => $hasRemainingWeight
                            ? $remainingWeight
                            : round((float) $order->items()->sum('total_weight'), 3),
                    ]);

                    OrderHistory::create([
                        'order_id' => $order->id,
                        'action' => 'warehouse_clear_item_weight',
                        'user_id' => Auth::id(),
                        'role' => $this->packingActorRole(),
                        'status_before' => $order->status,
                        'status_after' => $order->status,
                        'note' => 'Gỡ Kg thực tế đã lưu nhầm cho '.($item->variant?->name ?? $item->product?->name ?? ('dòng #'.$item->id))
                            .': '.number_format((float) $clearedWeight, 3, '.', '').' kg.',
                    ]);

                    $message = 'Đã gỡ kg đã lưu. Bạn có thể nhập lại và bấm Lưu màu xanh.';
                    if ($expectsJson) {
                        return response()->json([
                            'ok' => true,
                            'cleared' => true,
                            'message' => $message,
                            'order' => ['id' => $order->id, 'actual_weight' => $order->actual_weight],
                        ]);
                    }

                    return back()->with('success', $message);
                }

                $newWeight = round((float) $validated['item_actual_weight'], 3);
                if (abs((float) ($item->variant?->size ?? 0) - 2.5) < 0.0001 && (int) $item->quantity > 0) {
                    $averageWeight = $newWeight / (int) $item->quantity;
                    if ($averageWeight < 2.47 - 0.000001 || $averageWeight > 2.57 + 0.000001) {
                        $message = 'Khối lượng bình quân của size 2.5 phải nằm trong khoảng 2.47–2.57 kg/sản phẩm.';
                        if ($expectsJson) {
                            return response()->json(['ok' => false, 'message' => $message], 422);
                        }

                        return back()->withErrors(['item_actual_weight' => $message]);
                    }
                }
                $item->actual_weight = $newWeight;
                // Giữ lại KL kho cân lần đầu để đối chiếu hao hụt sau này
                if ($item->packed_weight === null) {
                    $item->packed_weight = $newWeight;
                }
                $item->save();
            }
        }

        $actualWeight = round((float) $order->items()->sum('actual_weight'), 3);

        $order->update([
            'actual_weight' => $actualWeight,
            // Keep total_weight aligned with real measured package weight in warehouse flow.
            'total_weight' => $actualWeight,
        ]);

        OrderHistory::create([
            'order_id' => $order->id,
            'action' => 'warehouse_update_logistics',
            'user_id' => Auth::id(),
            'role' => $this->packingActorRole(),
            'status_before' => $order->status,
            'status_after' => $order->status,
            'note' => sprintf(
                'Cập nhật Kg thực tế đóng hàng: %s → %s',
                number_format((float) $oldWeight, 3, '.', ''),
                number_format($actualWeight, 3, '.', '')
            ),
        ]);

        if ($request->filled('item_id')) {
            if ($expectsJson) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Đã lưu kg thực tế cho sản phẩm trong đơn #'.$order->code,
                    'order' => [
                        'id' => $order->id,
                        'actual_weight' => (float) $actualWeight,
                    ],
                ]);
            }

            return back()->with('success', 'Đã lưu kg thực tế cho sản phẩm trong đơn #'.$order->code);
        }

        if ($expectsJson) {
            return response()->json([
                'ok' => true,
                'message' => 'Đã cập nhật Kg thực tế cho đơn #'.$order->code,
                'order' => [
                    'id' => $order->id,
                    'actual_weight' => (float) $actualWeight,
                ],
            ]);
        }

        return back()->with('success', 'Đã cập nhật Kg thực tế cho đơn #'.$order->code);
    }

    public function requestAdjustment(Request $request, Order $order)
    {
        $this->authorizePackingOrderAccess($order);

        if (! $this->canProcessOrderOnCurrentRun($order)) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Chỉ được điều chỉnh đơn có ngày hôm nay.'], 422);
            }

            return back()->with('error', 'Chỉ được điều chỉnh đơn có ngày hôm nay.');
        }

        if ($order->status === Order::STATUS_PACKING) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Đơn đang đóng hàng. Hãy đưa đơn về Chờ đóng gói trước khi gửi điều chỉnh.'], 422);
            }

            return back()->with('error', 'Đơn đang đóng hàng. Hãy đưa đơn về Chờ đóng gói trước khi gửi điều chỉnh.');
        }

        if (in_array($order->status, self::PACKED_STATUSES, true)) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Đơn đã đóng gói xong, không thể điều chỉnh lại sản phẩm.'], 422);
            }

            return back()->with('error', 'Đơn đã đóng gói xong, không thể điều chỉnh lại sản phẩm.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:0'],
            'new_items' => ['nullable', 'array'],
            'new_items.*.product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'new_items.*.quantity' => ['nullable', 'integer', 'min:0'],
        ]);

        $order->loadMissing(['items.variant.product', 'user']);
        $orderItemsById = $order->items->keyBy('id');
        $newVariantIds = collect($validated['new_items'] ?? [])
            ->pluck('product_variant_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $variantsById = ProductVariant::query()
            ->with('product')
            ->whereIn('id', $newVariantIds->all())
            ->get()
            ->keyBy('id');

        $changes = [];
        $proposedQuantities = $order->items
            ->mapWithKeys(fn ($item) => [(int) $item->product_variant_id => (int) ($item->quantity ?? 0)])
            ->all();

        foreach ($validated['items'] as $itemData) {
            $orderItemId = (int) ($itemData['order_item_id'] ?? 0);
            $newQuantity = (int) ($itemData['quantity'] ?? 0);

            $orderItem = $orderItemsById->get($orderItemId);
            if (! $orderItem) {
                continue;
            }

            $variantId = (int) ($orderItem->product_variant_id ?? 0);
            $oldQuantity = (int) ($orderItem->quantity ?? 0);
            $proposedQuantities[$variantId] = $newQuantity;

            if ($oldQuantity === $newQuantity) {
                continue;
            }

            $changes[] = [
                'order_item_id' => $orderItem->id,
                'product_variant_id' => $variantId,
                'product_name' => $orderItem->variant?->name ?? $orderItem->product?->name ?? 'San pham',
                'sku' => $orderItem->variant?->sku,
                'size' => $orderItem->variant?->size,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'price' => (float) ($orderItem->price ?? 0),
                'unit_weight' => (float) ($orderItem->unit_weight ?? 1),
                'is_priced_by_kg' => (bool) ($orderItem->is_priced_by_kg ?? true),
            ];
        }

        foreach (($validated['new_items'] ?? []) as $itemData) {
            $variantId = (int) ($itemData['product_variant_id'] ?? 0);
            $addQuantity = (int) ($itemData['quantity'] ?? 0);

            if ($variantId <= 0 || $addQuantity <= 0) {
                continue;
            }

            $variant = $variantsById->get($variantId);
            if (! $variant) {
                continue;
            }

            $oldQuantity = (int) ($proposedQuantities[$variantId] ?? 0);
            $newQuantity = $oldQuantity + $addQuantity;
            $proposedQuantities[$variantId] = $newQuantity;

            $changes[] = [
                'order_item_id' => null,
                'product_variant_id' => $variant->id,
                'product_name' => $variant->name ?? $variant->product?->name ?? 'San pham',
                'sku' => $variant->sku,
                'size' => $variant->size,
                'old_quantity' => $oldQuantity,
                'new_quantity' => $newQuantity,
                'change_type' => $oldQuantity > 0 ? 'increase_existing' : 'added',
                'price' => (float) ($variant->final_price ?? 0),
                'unit_weight' => (float) ($variant->effective_kg ?? 1),
                'is_priced_by_kg' => (bool) ($variant->effective_priced_by_kg ?? true),
                'product_id' => (int) ($variant->product_id ?? 0),
            ];
        }

        $remainingItems = collect($proposedQuantities)->filter(fn ($qty) => (int) $qty > 0)->count();
        if ($remainingItems <= 0) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Đơn hàng phải còn ít nhất 1 sản phẩm sau khi điều chỉnh.'], 422);
            }

            return back()->with('error', 'Đơn hàng phải còn ít nhất 1 sản phẩm sau khi điều chỉnh.');
        }

        if (empty($changes)) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Không có thay đổi nào về số lượng sản phẩm.'], 422);
            }

            return back()->with('error', 'Không có thay đổi nào về số lượng sản phẩm.');
        }

        if ($order->warehouse_can_adjust) {
            DB::transaction(function () use ($order, $changes, $validated): void {
                app(WarehouseOrderAdjustmentService::class)->apply($order, collect($changes));
                $order->clearWarehouseAdjustmentState()->save();

                OrderHistory::create([
                    'order_id' => $order->id,
                    'action' => 'warehouse_direct_adjustment',
                    'user_id' => Auth::id(),
                    'role' => $this->packingActorRole(),
                    'status_before' => $order->status,
                    'status_after' => $order->status,
                    'note' => 'Kho đã trực tiếp điều chỉnh đơn: '.trim((string) $validated['reason']),
                ]);
            });

            app(OrderController::class)->syncDailySequenceAndStockSufficiency($order->created_at ?: now());

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Đã áp dụng điều chỉnh đơn hàng.',
                    'order_id' => (int) $order->id,
                    'warehouse_adjustment_status' => Order::WAREHOUSE_ADJUSTMENT_STATUS_NONE,
                ]);
            }

            return back()->with('success', 'Đã áp dụng điều chỉnh đơn hàng.');
        }

        $order->update([
            'warehouse_adjustment_status' => Order::WAREHOUSE_ADJUSTMENT_STATUS_PENDING_SALE_CONFIRMATION,
            'warehouse_adjustment_note' => trim((string) $validated['reason']),
            'warehouse_adjustment_changes' => $changes,
            'warehouse_adjustment_requested_by' => Auth::id(),
            'warehouse_adjustment_requested_at' => now(),
            'warehouse_adjustment_confirmed_by' => null,
            'warehouse_adjustment_confirmed_at' => null,
            'warehouse_adjustment_rejected_by' => null,
            'warehouse_adjustment_rejected_at' => null,
            'warehouse_adjustment_rejected_reason' => null,
        ]);

        OrderHistory::create([
            'order_id' => $order->id,
            'action' => 'warehouse_request_adjustment',
            'user_id' => Auth::id(),
            'role' => $this->packingActorRole(),
            'status_before' => $order->status,
            'status_after' => $order->status,
            'note' => 'Kho yeu cau sale xac nhan thay doi don (snapshot): '.trim((string) $validated['reason']),
        ]);

        $order->refresh();

        if ($order->user) {
            $order->user->notify(new WarehouseOrderAdjustmentRequested($order));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Đã lưu snapshot thay đổi và gửi yêu cầu xác nhận cho sale.',
                'order_id' => (int) $order->id,
                'warehouse_adjustment_status' => Order::WAREHOUSE_ADJUSTMENT_STATUS_PENDING_SALE_CONFIRMATION,
            ]);
        }

        return back()->with('success', 'Đã lưu snapshot thay đổi và gửi yêu cầu xác nhận cho sale.');
    }

    public function returnToReadyToPack(Request $request, Order $order)
    {
        $this->authorizePackingOrderAccess($order);

        if (! $this->canProcessOrderOnCurrentRun($order)) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Chỉ được xử lý đơn có ngày hôm nay.'], 422);
            }

            return back()->with('error', 'Chỉ được xử lý đơn có ngày hôm nay.');
        }

        if ($order->status !== Order::STATUS_PACKING) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Chỉ có thể hoàn tác đơn đang đóng hàng.'], 422);
            }

            return back()->with('error', 'Chỉ có thể đưa về Chờ đóng gói với đơn đang đóng hàng.');
        }

        $activePackingHistory = $order->histories()
            ->where('action', 'start_packing')
            ->latest('id')
            ->first();

        if (! $activePackingHistory || (int) $activePackingHistory->user_id !== (int) Auth::id()) {
            $message = 'Bạn chỉ có thể hoàn tác đơn do chính mình vừa nhận.';

            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $message], 403);
            }

            return back()->with('error', $message);
        }

        $updatePayload = ['status' => Order::STATUS_READY_TO_PACK];
        if (str_contains((string) $activePackingHistory->note, '[assigned_warehouse_on_start]')) {
            $updatePayload['warehouse_id'] = null;
        }
        $order->update($updatePayload);

        OrderHistory::create([
            'order_id' => $order->id,
            'action' => 'undo_start_packing',
            'user_id' => Auth::id(),
            'role' => $this->packingActorRole(),
            'status_before' => Order::STATUS_PACKING,
            'status_after' => Order::STATUS_READY_TO_PACK,
            'note' => 'Hoàn tác hoạt động nhận đơn đóng hàng của user hiện tại.',
        ]);

        $message = 'Đã hoàn tác nhận đơn #'.$order->code;

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'order' => [
                    'id' => (int) $order->id,
                    'status' => Order::STATUS_READY_TO_PACK,
                    'status_label' => 'Chờ đóng gói',
                    'status_class' => 'bg-secondary',
                ],
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Complete packing: packing → packed_waiting_pickup (ready to ship)
     */
    public function completePacking(Request $request, Order $order)
    {
        $this->authorizePackingOrderAccess($order);

        $validated = $request->validate([
            'packing_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);
        $hasPackingDate = $request->filled('packing_date');
        $packingDate = (string) ($validated['packing_date'] ?? now()->toDateString());

        $belongsToPackingDate = ! $hasPackingDate || Order::query()
            ->whereKey($order->id)
            ->forPackingDate($packingDate)
            ->exists();

        if (! $belongsToPackingDate) {
            $message = 'Đơn hàng không thuộc ngày đóng hàng đã chọn.';

            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        if ($order->warehouse_adjustment_status === Order::WAREHOUSE_ADJUSTMENT_STATUS_PENDING_SALE_CONFIRMATION) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Đơn đang chờ sale xác nhận thay đổi từ kho.'], 422);
            }

            return back()->with('error', 'Đơn đang chờ sale xác nhận thay đổi từ kho.');
        }

        if ($order->warehouse_adjustment_status === Order::WAREHOUSE_ADJUSTMENT_STATUS_SALE_REJECTED) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Sale đã từ chối yêu cầu điều chỉnh. Vui lòng xử lý lại thay đổi trước khi hoàn tất đóng gói.'], 422);
            }

            return back()->with('error', 'Sale đã từ chối yêu cầu điều chỉnh. Vui lòng xử lý lại thay đổi trước khi hoàn tất đóng gói.');
        }

        if (! $this->canProcessOrderOnCurrentRun($order)) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Chỉ được xử lý đơn có ngày hôm nay.'], 422);
            }

            return back()->with('error', 'Chỉ được xử lý đơn có ngày hôm nay.');
        }

        if ($order->status !== Order::STATUS_PACKING) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => 'Đơn hàng không đang ở trạng thái Đang đóng gói.'], 422);
            }

            return back()->with('error', 'Đơn hàng không đang ở trạng thái Đang đóng gói.');
        }

        $pendingForwardTransfer = WarehouseInventoryTransfer::query()
            ->where('order_id', $order->id)
            ->where('status', WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE)
            ->first();
        if ($pendingForwardTransfer) {
            $message = 'Đơn đang chờ kho mới tiếp nhận hàng chuyển tiếp theo phiếu '.$pendingForwardTransfer->transfer_code.'. Chưa thể hoàn thành đóng gói.';
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $order->loadMissing(['items.variant.product', 'items.product']);
        $missingWeightItems = $this->packingItemsMissingActualWeight($order);
        if ($missingWeightItems->isNotEmpty()) {
            $itemNames = $missingWeightItems
                ->map(fn ($item) => $item->variant?->name ?? $item->product?->name ?? ('Sản phẩm #'.$item->id))
                ->filter()
                ->take(3)
                ->join(', ');
            $message = 'Vui lòng cập nhật Kg thực tế cho mặt hàng tính theo kg trước khi hoàn thành đóng gói'
                .($itemNames ? ': '.$itemNames.'.' : '.');

            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $managedWarehouseId = Auth::user()?->warehouse_id
            ? (int) Auth::user()->warehouse_id
            : ($order->warehouse_id ? (int) $order->warehouse_id : null);
        $stockCheck = $this->evaluateSingleOrderStock($order, $managedWarehouseId, $packingDate);

        if (! ($stockCheck['can_start_packing'] ?? false)) {
            $message = 'Không đủ tồn kho ngày '.Carbon::parse($packingDate)->format('d/m/Y').' để hoàn thành đóng gói.';

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $message,
                    'stock_check' => $stockCheck,
                ], 422);
            }

            return back()->with('error', $message);
        }

        try {
            app(OrderController::class)->rebuildRestoredOrderStockReservation(
                $order,
                $managedWarehouseId
            );
        } catch (\RuntimeException $exception) {
            $message = 'Tồn kho vẫn chưa đủ để hoàn tất đóng gói đơn phục hồi. Vui lòng bổ sung kho trước.';

            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $packingWarehouseId = (int) ($order->warehouse_id ?: Auth::user()?->warehouse_id ?: 0);
        if ($packingWarehouseId <= 0) {
            $reservationWarehouseIds = InventoryReservation::query()
                ->join('inventories', 'inventories.id', '=', 'inventory_reservations.inventory_id')
                ->whereIn('inventory_reservations.order_item_id', $order->items()->pluck('id'))
                ->distinct()
                ->pluck('inventories.warehouse_id');

            if ($reservationWarehouseIds->count() === 1) {
                $packingWarehouseId = (int) $reservationWarehouseIds->first();
            }
        }

        if ($packingWarehouseId <= 0) {
            $message = 'Không xác định được kho đóng hàng. Vui lòng gán kho cho đơn trước khi hoàn tất đóng gói.';

            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $order->update([
            'status' => Order::STATUS_READY_TO_SHIP,
            'warehouse_id' => $packingWarehouseId,
        ]);

        OrderHistory::create([
            'order_id' => $order->id,
            'action' => 'complete_packing',
            'user_id' => Auth::id(),
            'role' => $this->packingActorRole(),
            'status_before' => Order::STATUS_PACKING,
            'status_after' => Order::STATUS_READY_TO_SHIP,
            'note' => 'Hoàn thành đóng gói ngày '.Carbon::parse($packingDate)->format('d/m/Y').' – Sẵn sàng giao hàng',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Đơn #'.$order->code.' đã đóng gói xong cho ngày '.Carbon::parse($packingDate)->format('d/m/Y').', sẵn sàng giao!',
                'order' => [
                    'id' => (int) $order->id,
                    'status' => Order::STATUS_READY_TO_SHIP,
                    'status_label' => 'Đã hoàn thành đóng hàng',
                    'status_class' => 'bg-success',
                ],
            ]);
        }

        return back()->with('success', 'Đơn #'.$order->code.' đã đóng gói xong cho ngày '.Carbon::parse($packingDate)->format('d/m/Y').', sẵn sàng giao!');
    }

    private function packingItemsMissingActualWeight(Order $order): Collection
    {
        return $order->items->filter(function ($item): bool {
            $unit = strtolower((string) ($item->variant?->product?->unit ?? $item->product?->unit ?? ''));

            // Bộ/Bánh are counted as discrete units. Old rows may still carry
            // the legacy is_priced_by_kg=true default, but must never force the
            // warehouse to enter a physical kg value to finish packing.
            if (in_array($unit, ['bo', 'banh'], true)) {
                return false;
            }

            return (bool) $item->effective_priced_by_kg && $item->actual_weight === null;
        })->values();
    }

    private function recalculateOrderTotalsAfterWarehouseAdjustment(Order $order): void
    {
        $order->loadMissing('items');

        $subtotalAmount = (float) $order->items->sum(function ($item) {
            $kg = max(0.01, (float) ($item->unit_weight ?? 1));
            $factor = (bool) ($item->is_priced_by_kg ?? true) ? $kg : 1;

            return (float) ($item->base_price ?? $item->price ?? 0) * (int) $item->quantity * $factor;
        });

        $itemDiscountTotal = (float) $order->items->sum(function ($item) {
            if ($item->discount_total !== null) {
                return (float) $item->discount_total;
            }

            $kg = max(0.01, (float) ($item->unit_weight ?? 1));
            $factor = (bool) ($item->is_priced_by_kg ?? true) ? $kg : 1;
            $amount = (float) ($item->unit_discount ?? 0) * (int) $item->quantity * $factor;
            $type = strtolower((string) ($item->discount_type ?? 'decrease'));

            return $type === 'increase' ? -1 * $amount : $amount;
        });

        $subtotalAfterItemDiscount = (float) $order->items->sum(function ($item) {
            if ($item->total !== null) {
                return (float) $item->total;
            }

            $kg = max(0.01, (float) ($item->unit_weight ?? 1));
            $factor = (bool) ($item->is_priced_by_kg ?? true) ? $kg : 1;

            return (float) $item->price * (int) $item->quantity * $factor;
        });

        $orderLevelDiscountAmount = (float) ($order->order_discount ?? 0);
        $orderLevelDiscountType = strtolower((string) ($order->order_discount_type ?? 'decrease'));
        if (! in_array($orderLevelDiscountType, ['decrease', 'increase'], true)) {
            $orderLevelDiscountType = 'decrease';
        }

        $orderLevelDiscount = $orderLevelDiscountType === 'increase'
            ? -1 * $orderLevelDiscountAmount
            : $orderLevelDiscountAmount;

        if ($orderLevelDiscountAmount <= 0 && $order->extra_discount_total !== null) {
            $orderLevelDiscount = (float) $order->extra_discount_total;
            $orderLevelDiscountType = $orderLevelDiscount < 0 ? 'increase' : 'decrease';
            $orderLevelDiscountAmount = abs($orderLevelDiscount);
        }

        $totalWeight = (float) $order->items->sum(function ($item) {
            return (float) ($item->total_weight ?? 0);
        });

        $order->update([
            'subtotal_amount' => $subtotalAmount,
            'item_discount_total' => $itemDiscountTotal,
            'extra_discount_total' => $orderLevelDiscount,
            'order_discount' => $orderLevelDiscountAmount,
            'order_discount_type' => $orderLevelDiscountType,
            'total_discount' => $itemDiscountTotal + $orderLevelDiscount,
            'total_weight' => round($totalWeight, 3),
            'total' => max($subtotalAfterItemDiscount - $orderLevelDiscount, 0),
        ]);
    }

    /**
     * Admin can reopen a packed warehouse order back to packing for edits.
     */
    public function reopenPacking(Request $request, Order $order)
    {
        $user = $request->user();

        abort_unless($user?->hasRole('admin'), 403);

        if (! $this->canProcessOrderOnCurrentRun($order)) {
            return back()->with('error', 'Chỉ được xử lý đơn có ngày hôm nay.');
        }

        if (! in_array($order->status, self::PACKED_STATUSES, true)) {
            return back()->with('error', 'Chỉ có thể bỏ khóa các đơn đang ở bước hoàn tất kho.');
        }

        $previousStatus = $order->status;

        $order->update([
            'status' => Order::STATUS_PACKING,
        ]);

        OrderHistory::create([
            'order_id' => $order->id,
            'action' => 'admin_reopen_packing',
            'user_id' => Auth::id(),
            'role' => 'admin',
            'status_before' => $previousStatus,
            'status_after' => Order::STATUS_PACKING,
            'note' => 'Admin bỏ khóa chỉnh sửa và đưa đơn quay lại bước đóng gói của kho',
        ]);

        return back()->with('success', 'Admin đã bỏ khóa chỉnh sửa cho đơn #'.$order->code.'.');
    }

    /**
     * List returning orders waiting for warehouse confirmation.
     * Fetches both:
     * 1. Orders with status=RETURNING (from shipper partial delivery)
     * 2. OrderReturn records with pending/requested status (from admin/customer return requests)
     */
    public function returns(Request $request)
    {
        $managedWarehouseId = Auth::user()->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        $warningDays = max(0, (int) Setting::get('warehouse_return_warning_days', 2));
        $period = (string) $request->input('period', 'all');
        $fromDate = $request->filled('from_date')
            ? Carbon::parse($request->input('from_date'))->toDateString()
            : null;
        $toDate = $request->filled('to_date')
            ? Carbon::parse($request->input('to_date'))->toDateString()
            : null;

        if ($fromDate && $toDate && $fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        if ($period === 'today') {
            $fromDate = Carbon::today()->toDateString();
            $toDate = Carbon::today()->toDateString();
        } elseif ($period === '7_days') {
            $fromDate = Carbon::today()->subDays(6)->toDateString();
            $toDate = Carbon::today()->toDateString();
        }

        // Fetch orders with status=RETURNING (shipper partial delivery)
        $ordersQuery = Order::with(['customer', 'shipper', 'items.variant', 'items.product', 'warehouse', 'returnWarehouse'])
            ->where('status', Order::STATUS_RETURNING);

        if ($managedWarehouseId) {
            $ordersQuery->whereExists(function ($query) use ($managedWarehouseId) {
                $query->select(DB::raw(1))
                    ->from('order_returns')
                    ->whereColumn('order_returns.order_id', 'orders.id')
                    ->where('order_returns.warehouse_id', $managedWarehouseId);
            });
        }

        if ($fromDate) {
            $ordersQuery->whereDate('updated_at', '>=', $fromDate);
        }

        if ($toDate) {
            $ordersQuery->whereDate('updated_at', '<=', $toDate);
        }

        $orders = $ordersQuery
            ->orderBy('updated_at', 'desc')
            ->get();

        $orderReturnCreatedMap = collect();
        if ($orders->isNotEmpty()) {
            $orderReturnCreatedMap = \App\Models\OrderReturn::query()
                ->whereIn('order_id', $orders->pluck('id')->all())
                ->selectRaw('order_id, MAX(created_at) as return_ticket_created_at')
                ->groupBy('order_id')
                ->pluck('return_ticket_created_at', 'order_id');
        }

        $orders->each(function (Order $order) use ($warningDays) {
            $resolvedWarehouse = $this->resolveReturnWarehouse($order);
            $order->setAttribute('resolved_return_warehouse_id', $resolvedWarehouse?->id);
            $order->setAttribute('resolved_return_warehouse_name', $resolvedWarehouse?->name);
            $order->setAttribute('is_from_order_return', false);
            $createdAt = $order->updated_at;
            $order->setAttribute('return_ticket_created_at', $createdAt);
            $ageDays = $createdAt ? Carbon::parse($createdAt)->startOfDay()->diffInDays(Carbon::today()) : 0;
            $order->setAttribute('return_ticket_age_days', $ageDays);
            $order->setAttribute('is_return_ticket_overdue', $warningDays > 0 && $ageDays >= $warningDays);
        });

        if ($orderReturnCreatedMap->isNotEmpty()) {
            $orders->each(function (Order $order) use ($orderReturnCreatedMap, $warningDays) {
                $returnCreatedAt = $orderReturnCreatedMap->get($order->id);
                if ($returnCreatedAt) {
                    $createdAt = Carbon::parse($returnCreatedAt);
                    $order->setAttribute('return_ticket_created_at', $createdAt);
                    $ageDays = $createdAt->startOfDay()->diffInDays(Carbon::today());
                    $order->setAttribute('return_ticket_age_days', $ageDays);
                    $order->setAttribute('is_return_ticket_overdue', $warningDays > 0 && $ageDays >= $warningDays);
                }
            });
        }

        // Fetch OrderReturn records for warehouse review and history
        // Include statuses: pending_warehouse (shipper), requested (admin/customer), ship_confirmed (shipper confirmed), warehouse_received (processed)
        $orderReturnsQuery = OrderReturn::with([
            'order.customer',
            'order.shipper',
            'order.items.variant',
            'order.items.product',
            'order.warehouse',
            'warehouse',
            'returnItems.productVariant.product',
        ])
            ->whereIn('status', ['pending_warehouse', 'requested', 'ship_confirmed', 'warehouse_received'])
            ->orderBy('updated_at', 'desc');

        // Filter by managed warehouse if user is warehouse staff
        if ($managedWarehouseId) {
            $orderReturnsQuery->where('warehouse_id', $managedWarehouseId);
        }

        if ($fromDate) {
            $orderReturnsQuery->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $orderReturnsQuery->whereDate('created_at', '<=', $toDate);
        }

        $orderReturns = $orderReturnsQuery->get();

        // Map OrderReturn records to Order-like structure for view
        $orderReturnsCollection = collect();
        foreach ($orderReturns as $orderReturn) {
            if (! $orderReturn->order) {
                continue;
            }

            $order = $orderReturn->order;

            // Set attributes from OrderReturn onto Order for view compatibility
            $order->setAttribute('order_return_id', $orderReturn->id);
            $order->setAttribute('order_return', $orderReturn);
            $order->setAttribute('resolved_return_warehouse_id', $orderReturn->warehouse_id);
            $order->setAttribute('resolved_return_warehouse_name', $orderReturn->warehouse?->name);
            $order->setAttribute('is_from_order_return', true);
            $order->setAttribute('return_reason', $orderReturn->reason);
            $order->setAttribute('shipper_note', $orderReturn->note);
            $order->setAttribute('return_status', $orderReturn->status);
            $order->setAttribute('return_ticket_created_at', $orderReturn->created_at);
            $order->setAttribute('is_return_processed', $orderReturn->status === 'warehouse_received');
            $ageDays = $orderReturn->created_at
                ? $orderReturn->created_at->startOfDay()->diffInDays(Carbon::today())
                : 0;
            $order->setAttribute('return_ticket_age_days', $ageDays);
            $order->setAttribute('is_return_ticket_overdue', $warningDays > 0 && $ageDays >= $warningDays);

            $orderReturnsCollection->push($order);
        }

        // Merge both collections and remove duplicates by order_id
        $allReturns = $orders->merge($orderReturnsCollection)->keyBy('id')->values();

        $filters = [
            'period' => $period,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'warning_days' => $warningDays,
        ];

        $warehouses = Warehouse::query()->orderBy('name')->get(['id', 'name']);

        return view('warehouse.returns.index', compact('allReturns', 'managedWarehouseId', 'filters', 'warehouses'))->with('orders', $allReturns);
    }

    /**
     * Confirm returned stock. A partial delivery completes the sale; a full
     * return completes the return workflow without recognizing a sale.
     */
    public function confirmReturn(Order $order)
    {
        $activeOrderReturn = $this->resolveActiveOrderReturn($order);
        $canHandleByOrderStatus = $order->status === Order::STATUS_RETURNING;
        if (! $activeOrderReturn && ! $canHandleByOrderStatus) {
            return back()->with('error', 'Đơn hàng không đang ở trạng thái Đang trả hàng.');
        }

        $managedWarehouseId = Auth::user()->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        $resolvedReturnWarehouse = $activeOrderReturn?->warehouse
            ?: $this->resolveReturnWarehouse($order);
        $returnWarehouseId = $resolvedReturnWarehouse?->id;

        if ($managedWarehouseId && (! $returnWarehouseId || $managedWarehouseId !== $returnWarehouseId)) {
            return back()->with('error', 'Bạn chỉ có thể xác nhận đơn trả về đúng kho mình quản lý.');
        }

        if (! $returnWarehouseId) {
            return back()->with('error', 'Đơn trả này chưa xác định kho nhận. Vui lòng yêu cầu shipper chọn kho trả về.');
        }

        DB::transaction(function () use ($order, $returnWarehouseId, $activeOrderReturn, $resolvedReturnWarehouse) {
            $statusBefore = (string) $order->status;
            $statusAfter = $activeOrderReturn?->completedOrderStatus() ?? Order::STATUS_RETURNED_COMPLETED;
            $order->update(['status' => $statusAfter]);

            if ($activeOrderReturn) {
                $activeOrderReturn->loadMissing('returnItems');
                $activeOrderReturn->update([
                    'status' => 'warehouse_received',
                    'warehouse_confirmed_by' => Auth::id(),
                    'warehouse_confirmed_at' => now(),
                ]);
            }

            // For a partial delivery, only ReturnItem quantities belong back
            // in stock; order_items now represent what the customer received.
            $inventoryItems = $activeOrderReturn?->returnItems?->isNotEmpty()
                ? $activeOrderReturn->returnItems
                : $order->items;
            foreach ($inventoryItems as $item) {
                Inventory::query()->firstOrCreate(
                    [
                        'product_variant_id' => $item->product_variant_id,
                        'warehouse_id' => $returnWarehouseId,
                    ],
                    ['quantity' => 0, 'reserved_quantity' => 0]
                )->increment('quantity', (int) $item->quantity);
            }

            if ($activeOrderReturn) {
                $this->syncReturnImportDocument($activeOrderReturn, (int) Auth::id());
            }

            OrderHistory::create([
                'order_id' => $order->id,
                'action' => 'confirm_return',
                'user_id' => Auth::id(),
                'role' => 'warehouse',
                'status_before' => $statusBefore,
                'status_after' => $statusAfter,
                'note' => 'Kho xác nhận đã nhận hàng trả vào kho '.($resolvedReturnWarehouse?->name ?? ('ID '.$returnWarehouseId)).' – Tồn kho đã cập nhật',
            ]);
        });

        return back()->with('success', 'Đã xác nhận nhập kho hàng trả – Đơn #'.$order->code);
    }

    /**
     * Show weight re-entry form for returned items
     */
    public function showWeightEntry(Order $order)
    {
        $activeOrderReturn = $this->resolveActiveOrderReturn($order);
        $canHandleByOrderStatus = $order->status === Order::STATUS_RETURNING;
        if (! $activeOrderReturn && ! $canHandleByOrderStatus) {
            return back()->with('error', 'Đơn hàng không đang ở trạng thái Đang trả hàng.');
        }

        $managedWarehouseId = Auth::user()->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        $resolvedReturnWarehouse = $activeOrderReturn?->warehouse
            ?: $this->resolveReturnWarehouse($order);
        $returnWarehouseId = $resolvedReturnWarehouse?->id;

        if ($managedWarehouseId && (! $returnWarehouseId || $managedWarehouseId !== $returnWarehouseId)) {
            return back()->with('error', 'Bạn chỉ có thể xác nhận đơn trả về đúng kho mình quản lý.');
        }

        // Load order with all needed relationships
        $order->load([
            'customer',
            'shipper',
            'items.variant.product',
            'items.product',
            'warehouse',
        ]);

        // Get or create OrderReturn for weight confirmation flow
        $orderReturn = $activeOrderReturn;

        if (! $orderReturn) {
            $orderReturn = OrderReturn::create([
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'warehouse_id' => $returnWarehouseId,
                'created_by' => Auth::id(),
                'status' => 'pending_warehouse',
                'reason' => $order->return_reason,
                'note' => $order->shipper_note,
            ]);

            foreach ($order->items as $orderItem) {
                ReturnItem::create([
                    'order_return_id' => $orderReturn->id,
                    'product_variant_id' => $orderItem->product_variant_id,
                    'quantity' => (int) $orderItem->quantity,
                    'condition' => null,
                ]);
            }

            $orderReturn->load(['warehouse', 'returnItems.productVariant.product']);
        } else {
            $orderReturn->loadMissing(['warehouse', 'returnItems.productVariant.product']);
        }

        // Populate original_weight if not yet set
        if ($orderReturn) {
            foreach ($orderReturn->returnItems as $returnItem) {
                if (! $returnItem->original_weight) {
                    // Find the corresponding order item
                    $orderItem = $order->items->where('product_variant_id', $returnItem->product_variant_id)->first();
                    if ($orderItem) {
                        $unitWeight = (float) ($orderItem->effective_unit_weight ?? $orderItem->unit_weight ?? 0);
                        if ($unitWeight <= 0) {
                            $unitWeight = (float) ($orderItem->variant->kg ?? 1);
                        }
                        $returnItem->update([
                            'original_weight' => $unitWeight * (int) $returnItem->quantity,
                        ]);
                    }
                }
            }
            // Reload after update
            $orderReturn->load('returnItems');
        }

        return view('warehouse.returns.weight-entry', compact(
            'order',
            'orderReturn',
            'resolvedReturnWarehouse'
        ));
    }

    /**
     * Save weight entries and confirm return
     */
    public function saveWeights(Request $request, Order $order)
    {
        $activeOrderReturn = $this->resolveActiveOrderReturn($order);
        $canHandleByOrderStatus = $order->status === Order::STATUS_RETURNING;
        if (! $activeOrderReturn && ! $canHandleByOrderStatus) {
            return back()->with('error', 'Đơn hàng không đang ở trạng thái Đang trả hàng.');
        }

        $managedWarehouseId = Auth::user()->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        $resolvedReturnWarehouse = $activeOrderReturn?->warehouse
            ?: $this->resolveReturnWarehouse($order);
        $returnWarehouseId = $resolvedReturnWarehouse?->id;

        if ($managedWarehouseId && (! $returnWarehouseId || $managedWarehouseId !== $returnWarehouseId)) {
            return back()->with('error', 'Bạn chỉ có thể xác nhận đơn trả về đúng kho mình quản lý.');
        }

        $orderReturn = $activeOrderReturn;
        if ($orderReturn) {
            $orderReturn->loadMissing('returnItems');
        }

        if (! $orderReturn || $orderReturn->returnItems->isEmpty()) {
            return back()->with('error', 'Không tìm thấy chi tiết sản phẩm trả để cân ký lại.');
        }

        // Validate weight inputs
        $validated = $request->validate([
            'item_weights' => 'required|array|min:1',
            'item_weights.*.item_id' => 'required|integer',
            'item_weights.*.received_weight' => 'required|numeric|min:0',
        ]);

        $requiredIds = $orderReturn->returnItems->pluck('id')->map(fn ($id) => (int) $id)->unique()->values();
        $submittedIds = collect($validated['item_weights'])
            ->pluck('item_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($requiredIds->diff($submittedIds)->isNotEmpty() || $submittedIds->diff($requiredIds)->isNotEmpty()) {
            return back()->with('error', 'Vui lòng nhập cân nặng đầy đủ cho tất cả sản phẩm trong phiếu trả.')->withInput();
        }

        $returnItemsById = $orderReturn->returnItems->keyBy('id');

        DB::transaction(function () use ($order, $returnWarehouseId, $validated, $resolvedReturnWarehouse, $orderReturn, $returnItemsById) {
            // Update weight data for return items
            foreach ($validated['item_weights'] as $weightData) {
                $returnItem = $returnItemsById->get((int) $weightData['item_id']);
                if (! $returnItem) {
                    continue;
                }

                $receivedWeight = (float) $weightData['received_weight'];
                $returnItem->update([
                    'received_weight' => $receivedWeight,
                    'weight_confirmed_at' => now(),
                ]);

                // Calculate weight loss
                $returnItem->calculateWeightLoss();
                $returnItem->save();
            }

            $orderReturn->update([
                'status' => 'warehouse_received',
                'warehouse_confirmed_by' => Auth::id(),
                'warehouse_confirmed_at' => now(),
            ]);

            // Partial delivery is a completed sale after returned stock is
            // received. Full return remains a completed return.
            $statusBefore = (string) $order->status;
            $statusAfter = $orderReturn->completedOrderStatus();
            $order->update(['status' => $statusAfter]);

            // Restore inventory for each item
            if ($orderReturn && $orderReturn->relationLoaded('returnItems')) {
                foreach ($orderReturn->returnItems as $returnItem) {
                    Inventory::where('product_variant_id', $returnItem->product_variant_id)
                        ->where('warehouse_id', $returnWarehouseId)
                        ->increment('quantity', (int) $returnItem->quantity);
                }
            } else {
                foreach ($order->items as $item) {
                    Inventory::where('product_variant_id', $item->product_variant_id)
                        ->where('warehouse_id', $returnWarehouseId)
                        ->increment('quantity', (int) $item->quantity);
                }
            }

            $this->syncReturnImportDocument($orderReturn, (int) Auth::id());

            // Calculate total weight loss for report
            $totalWeightLoss = $orderReturn
                ? (float) $orderReturn->returnItems()->whereNotNull('weight_loss')->sum('weight_loss')
                : 0;

            OrderHistory::create([
                'order_id' => $order->id,
                'action' => 'confirm_return',
                'user_id' => Auth::id(),
                'role' => 'warehouse',
                'status_before' => $statusBefore,
                'status_after' => $statusAfter,
                'note' => sprintf(
                    'Kho xác nhận đã nhận hàng trả vào kho %s – Tồn kho đã cập nhật – Hao hụt KL: %.3f kg',
                    $resolvedReturnWarehouse?->name ?? ('ID '.$returnWarehouseId),
                    $totalWeightLoss
                ),
            ]);
        });

        return redirect()
            ->route('warehouse.returns')
            ->with('success', 'Đã lưu cân nặng và xác nhận nhập kho hàng trả – Đơn #'.$order->code);
    }

    public function transferReturnWarehouse(Request $request, Order $order)
    {
        $activeOrderReturn = $this->resolveActiveOrderReturn($order);
        if (! $activeOrderReturn) {
            return back()->with('error', 'Không tìm thấy phiếu trả đang chờ kho xử lý để chuyển kho.');
        }

        $validated = $request->validate([
            'new_warehouse_id' => 'required|exists:warehouses,id',
            'transfer_note' => 'nullable|string|max:500',
        ]);

        $managedWarehouseId = Auth::user()->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        $currentWarehouseId = (int) ($activeOrderReturn->warehouse_id ?? 0);

        if ($managedWarehouseId && $currentWarehouseId !== $managedWarehouseId) {
            return back()->with('error', 'Bạn chỉ có thể chuyển tiếp các phiếu trả thuộc kho bạn đang quản lý.');
        }

        $newWarehouseId = (int) $validated['new_warehouse_id'];
        if ($newWarehouseId === $currentWarehouseId) {
            return back()->with('error', 'Kho chuyển tiếp phải khác kho hiện tại.');
        }

        $newWarehouse = Warehouse::query()->findOrFail($newWarehouseId);
        $oldWarehouseName = $activeOrderReturn->warehouse?->name ?? ('ID '.$currentWarehouseId);
        $extraNote = trim((string) ($validated['transfer_note'] ?? ''));
        $transferNote = 'Chuyển kho tiếp nhận trả hàng từ '.$oldWarehouseName.' sang '.$newWarehouse->name;
        if ($extraNote !== '') {
            $transferNote .= ' | Lý do: '.$extraNote;
        }

        DB::transaction(function () use ($activeOrderReturn, $order, $newWarehouseId, $transferNote) {
            $activeOrderReturn->update([
                'warehouse_id' => $newWarehouseId,
                'note' => trim(((string) ($activeOrderReturn->note ?? '')).' | '.$transferNote, ' |'),
            ]);

            if (Schema::hasColumn('orders', 'return_warehouse_id')) {
                $order->update(['return_warehouse_id' => $newWarehouseId]);
            }

            if (Schema::hasColumn('orders', 'warehouse_id') && $order->status === Order::STATUS_RETURNING) {
                $order->update(['warehouse_id' => $newWarehouseId]);
            }

            OrderHistory::create([
                'order_id' => $order->id,
                'action' => 'transfer_return_warehouse',
                'user_id' => Auth::id(),
                'role' => Auth::user()?->roles->pluck('name')->first() ?? 'warehouse',
                'status_before' => $order->status,
                'status_after' => $order->status,
                'note' => $transferNote,
            ]);
        });

        return back()->with('success', 'Đã chuyển kho tiếp nhận trả hàng sang: '.$newWarehouse->name);
    }

    protected function resolveActiveOrderReturn(Order $order): ?OrderReturn
    {
        return OrderReturn::query()
            ->where('order_id', $order->id)
            ->whereIn('status', ['pending_warehouse', 'requested', 'ship_confirmed'])
            ->with(['warehouse', 'returnItems.productVariant.product'])
            ->latest('id')
            ->first();
    }

    protected function resolveReturnWarehouse(Order $order): ?Warehouse
    {
        $latestOrderReturnWarehouseId = OrderReturn::query()
            ->where('order_id', $order->id)
            ->latest('id')
            ->value('warehouse_id');

        if (! empty($latestOrderReturnWarehouseId)) {
            return Warehouse::find((int) $latestOrderReturnWarehouseId);
        }

        if ($order->relationLoaded('returnWarehouse') && $order->returnWarehouse) {
            return $order->returnWarehouse;
        }

        if ($order->relationLoaded('warehouse') && $order->warehouse) {
            return $order->warehouse;
        }

        $warehouseId = $this->resolveReturnWarehouseId($order);
        if ($warehouseId) {
            return Warehouse::find($warehouseId);
        }

        $warehouseName = $this->extractReturnWarehouseName((string) ($order->shipper_note ?? ''));

        if (! $warehouseName) {
            $returnHistoryNote = OrderHistory::query()
                ->where('order_id', $order->id)
                ->where('action', 'return_request')
                ->latest('id')
                ->value('note');

            $warehouseName = $this->extractReturnWarehouseName((string) ($returnHistoryNote ?? ''));
        }

        if (! $warehouseName) {
            return null;
        }

        return Warehouse::query()->where('name', $warehouseName)->first();
    }

    protected function resolveReturnWarehouseId(Order $order): ?int
    {
        if (Schema::hasColumn('orders', 'return_warehouse_id') && ! empty($order->return_warehouse_id)) {
            return (int) $order->return_warehouse_id;
        }

        if (Schema::hasColumn('orders', 'warehouse_id') && ! empty($order->warehouse_id)) {
            return (int) $order->warehouse_id;
        }

        return null;
    }

    protected function extractReturnWarehouseName(string $text): ?string
    {
        if (trim($text) === '') {
            return null;
        }

        if (preg_match('/Kho trả về:\s*([^|]+)/u', $text, $matches) !== 1) {
            return null;
        }

        $warehouseName = trim((string) ($matches[1] ?? ''));

        return $warehouseName !== '' ? $warehouseName : null;
    }

    private function syncReturnImportDocument(OrderReturn $orderReturn, int $actorId): ?InventoryDocument
    {
        $orderReturn->loadMissing(['order.items', 'returnItems']);

        $expectedItems = $orderReturn->returnItems
            ->groupBy('product_variant_id')
            ->map(fn ($items) => (int) $items->sum('quantity'));

        $unitCostByVariant = $orderReturn->order?->items
            ? $orderReturn->order->items
                ->groupBy('product_variant_id')
                ->map(function ($items) {
                    $totalQty = max((int) $items->sum('quantity'), 1);
                    $totalAmount = (float) $items->sum(function ($item) {
                        return ((float) $item->price) * ((int) $item->quantity);
                    });

                    return round($totalAmount / $totalQty, 2);
                })
            : collect();

        $marker = $this->returnReceiptMarker((int) $orderReturn->id);

        $document = InventoryDocument::query()
            ->with('items')
            ->where('type', 'import')
            ->where('warehouse_id', $orderReturn->warehouse_id)
            ->where('notes', 'like', '%'.$marker.'%')
            ->latest('id')
            ->first();

        if (! $document) {
            $document = InventoryDocument::create([
                'type' => 'import',
                'warehouse_id' => $orderReturn->warehouse_id,
                'document_date' => optional($orderReturn->warehouse_confirmed_at)->toDateString() ?: now()->toDateString(),
                'notes' => 'Đơn nhập hàng từ trả hàng #'.$orderReturn->id.' '.$marker,
                'shipping_fee' => 0,
                'user_id' => $actorId,
            ]);
            $document->load('items');
        }

        $currentItems = $document->items->keyBy('product_variant_id');

        foreach ($expectedItems as $variantId => $expectedQty) {
            $variantId = (int) $variantId;
            $expectedQty = (int) $expectedQty;
            $expectedUnitCost = (float) ($unitCostByVariant[$variantId] ?? 0);

            $item = $currentItems->get($variantId);
            if (! $item) {
                InventoryDocumentItem::create([
                    'inventory_document_id' => $document->id,
                    'product_variant_id' => $variantId,
                    'quantity' => $expectedQty,
                    'unit_cost' => $expectedUnitCost,
                ]);

                continue;
            }

            if ((int) $item->quantity !== $expectedQty || abs((float) $item->unit_cost - $expectedUnitCost) > 0.0001) {
                $item->update([
                    'quantity' => $expectedQty,
                    'unit_cost' => $expectedUnitCost,
                ]);
            }
        }

        $expectedVariantIds = $expectedItems->keys()->map(fn ($id) => (int) $id)->all();
        $extraItems = $document->items->filter(fn ($item) => ! in_array((int) $item->product_variant_id, $expectedVariantIds, true));
        if ($extraItems->isNotEmpty()) {
            InventoryDocumentItem::whereIn('id', $extraItems->pluck('id')->all())->delete();
        }

        return $document->refresh();
    }

    private function returnReceiptMarker(int $returnId): string
    {
        return '[return_receipt:'.$returnId.']';
    }

    /**
     * Stock In (Nhập kho) - View list of stock in documents
     */
    public function stockIn(Request $request)
    {
        $query = InventoryDocument::where('type', 'import')
            ->with('warehouse', 'user', 'supplier', 'items.productVariant.product');

        $warehouseId = Auth::user()->warehouse_id;
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $supplierId = $request->input('supplier_id');
        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $from = $request->input('from_date', Carbon::now()->subDays(30)->toDateString());
        $to = $request->input('to_date', Carbon::now()->toDateString());
        $query->whereBetween('document_date', [$from, $to]);

        $stockInDocuments = $query->latest('document_date')->paginate(15);
        $warehouses = $warehouseId
            ? Warehouse::where('id', $warehouseId)->get()
            : Warehouse::all();
        $productVariants = ProductVariant::with('product')->orderBy('name')->get();
        $maxEdits = (int) (\App\Models\Setting::get('stock_in_max_edits', 3));
        $suppliers = \App\Models\Supplier::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('warehouse.stock-in.index', compact('stockInDocuments', 'from', 'to', 'warehouses', 'productVariants', 'maxEdits', 'suppliers', 'supplierId'));
    }

    /**
     * Store a new Phiếu Nhập Kho (import document).
     */
    public function storeStockIn(Request $request)
    {
        return $this->storeDocument($request, 'import');
    }

    /**
     * Stock Out (Xuất kho) - View list of stock out documents
     */
    public function stockOut(Request $request)
    {
        $query = InventoryDocument::where('type', 'export')
            ->with('warehouse', 'user', 'items.productVariant.product');

        $warehouseId = Auth::user()->warehouse_id;
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $from = $request->input('from_date', Carbon::now()->subDays(30)->toDateString());
        $to = $request->input('to_date', Carbon::now()->toDateString());
        $query->whereBetween('document_date', [$from, $to]);

        $stockOutDocuments = $query->latest('document_date')->paginate(15);
        $warehouses = $warehouseId
            ? Warehouse::where('id', $warehouseId)->get()
            : Warehouse::all();
        $productVariants = ProductVariant::with('product')->orderBy('name')->get();

        return view('warehouse.stock-out.index', compact('stockOutDocuments', 'from', 'to', 'warehouses', 'productVariants'));
    }

    /**
     * Exported orders list, grouped by export documents associated with order code.
     */
    public function exportedOrders(Request $request)
    {
        $query = InventoryDocument::query()
            ->where('type', 'export')
            ->with(['warehouse', 'user', 'items.productVariant.product'])
            ->orderByDesc('document_date')
            ->orderByDesc('id');

        $warehouseId = Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $from = $request->input('from_date', Carbon::now()->subDays(30)->toDateString());
        $to = $request->input('to_date', Carbon::now()->toDateString());
        $query->whereBetween('document_date', [$from, $to]);

        $documents = $query->get();

        $exportRows = $documents->map(function (InventoryDocument $document) {
            $note = (string) ($document->notes ?? '');
            $orderCode = null;

            if (preg_match('/(?:đơn|don)\s*#\s*([A-Za-z0-9\-]+)/iu', $note, $matches)) {
                $orderCode = strtoupper(trim((string) ($matches[1] ?? '')));
            }

            if (! $orderCode) {
                return null;
            }

            $order = Order::query()
                ->with(['customer', 'shipper', 'items.variant.product'])
                ->whereRaw('UPPER(code) = ?', [$orderCode])
                ->first();

            if (! $order) {
                return null;
            }

            return [
                'document' => $document,
                'order' => $order,
            ];
        })->filter()->values();

        return view('warehouse.stock-out.orders', compact('exportRows', 'from', 'to'));
    }

    /**
     * Store a new Phiếu Xuất Kho (export document).
     */
    public function storeStockOut(Request $request)
    {
        return $this->storeDocument($request, 'export');
    }

    /**
     * Transfer between warehouses (outgoing).
     */
    public function inventoryTransfers(Request $request)
    {
        $managedWarehouseId = Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        if (! $managedWarehouseId) {
            return redirect()->route('warehouse.dashboard')
                ->with('error', 'Bạn chưa được gán kho quản lý để tạo phiếu điều chuyển.');
        }

        return $this->renderInventoryTransferPage($managedWarehouseId);
    }

    /**
     * Edit an outgoing stock transfer that has not been received yet.
     */
    public function editInventoryTransfer(WarehouseInventoryTransfer $transfer)
    {
        $managedWarehouseId = Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        if (! $managedWarehouseId || (int) $transfer->source_warehouse_id !== $managedWarehouseId) {
            abort(403, 'Bạn không có quyền sửa phiếu điều chuyển này.');
        }

        if ($transfer->status !== WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE) {
            return redirect()->route('warehouse.inventory-transfers.index')
                ->with('error', 'Chỉ có thể sửa phiếu đang chờ kho nhận.');
        }

        if ($transfer->dispatchEntry()->exists()) {
            return redirect()->route('warehouse.inventory-transfers.index')
                ->with('error', 'Phiếu hàng đã thuộc một phiếu xuất kho tổng nên không thể sửa nội dung.');
        }
        if ($transfer->order_id) {
            return redirect()->route('warehouse.inventory-transfers.index')
                ->with('error', 'Phiếu này đang gắn với đơn thiếu hàng nên không thể sửa thủ công.');
        }

        $transfer->load('items');

        return $this->renderInventoryTransferPage($managedWarehouseId, $transfer);
    }

    private function renderInventoryTransferPage(
        int $managedWarehouseId,
        ?WarehouseInventoryTransfer $editingTransfer = null
    ) {
        $sourceWarehouse = Warehouse::query()->find($managedWarehouseId);
        $targetWarehouses = Warehouse::query()
            ->where('id', '!=', $managedWarehouseId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $editingQuantities = $editingTransfer
            ? $editingTransfer->items->pluck('quantity', 'product_variant_id')->map(fn ($quantity) => (int) $quantity)
            : collect();

        $availableVariants = Inventory::query()
            ->with(['productVariant.product', 'productVariant.values.attribute'])
            ->where('warehouse_id', $managedWarehouseId)
            ->when($editingQuantities->isNotEmpty(), function ($query) use ($editingQuantities) {
                $query->where(function ($scope) use ($editingQuantities) {
                    $scope->whereRaw('(quantity - reserved_quantity) > 0')
                        ->orWhereIn('product_variant_id', $editingQuantities->keys());
                });
            }, fn ($query) => $query->whereRaw('(quantity - reserved_quantity) > 0'))
            ->orderByRaw('(quantity - reserved_quantity) DESC')
            ->get()
            ->map(function (Inventory $inventory) use ($editingQuantities) {
                $variant = $inventory->productVariant;
                $product = $variant?->product;
                if (! $variant || ! $product) {
                    return null;
                }
                $attributes = $variant->values?->map(function ($val) {
                    return $val->attribute->name.': '.$val->value;
                })->implode(', ');

                $available = max(0, (int) $inventory->quantity - (int) $inventory->reserved_quantity)
                    + (int) $editingQuantities->get($variant->id, 0);

                return [
                    'variant_id' => (int) $variant->id,
                    'variant_name' => $variant->name ?? '',
                    'variant_sku' => $variant->sku ?? '',
                    'label' => $product->name.' - '.($variant->name ?? 'Biến thể'),
                    'unit_label' => $product->unit_label ?? 'Cái',
                    'weight_per_unit' => round((float) ($variant->effective_kg ?? 1), 3),
                    'available' => $available,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku ?? '',
                    'product_thumbnail' => $product->thumbnail?->media?->file_path ?? null,
                    'product_category' => $product->category?->name ?? null,
                    'attributes' => $attributes,
                ];
            })
            ->filter()
            ->values();

        $availableVariantsGrouped = $availableVariants->groupBy('product_id')->map(function ($variants, $productId) {
            $first = $variants->first();

            return [
                'product' => [
                    'id' => $first['product_id'],
                    'name' => $first['product_name'],
                    'sku' => $first['product_sku'],
                    'thumbnail' => $first['product_thumbnail'],
                    'category' => $first['product_category'],
                ],
                'variants' => $variants->map(function ($v) {
                    return [
                        'variant_id' => $v['variant_id'],
                        'name' => $v['variant_name'],
                        'sku' => $v['variant_sku'],
                        'unit_label' => $v['unit_label'],
                        'weight_per_unit' => $v['weight_per_unit'],
                        'available' => $v['available'],
                        'attributes' => $v['attributes'] ?? '',
                    ];
                })->values(),
            ];
        })->values();

        $outgoingTransfers = WarehouseInventoryTransfer::query()
            ->with([
                'order:id,code,customer_id',
                'order.customer:id,name',
                'targetWarehouse:id,name',
                'requester:id,name',
                'items.variant.product',
                'dispatchEntry.slip',
            ])
            ->where('source_warehouse_id', $managedWarehouseId)
            ->latest('id')
            ->paginate(10);

        $incomingPendingCount = WarehouseInventoryTransfer::query()
            ->where('target_warehouse_id', $managedWarehouseId)
            ->where('status', WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE)
            ->count();

        return view('warehouse.transfers.inventory', compact(
            'sourceWarehouse',
            'targetWarehouses',
            'availableVariantsGrouped',
            'availableVariants',
            'outgoingTransfers',
            'incomingPendingCount',
            'editingTransfer'
        ));
    }

    /**
     * Create a stock transfer request from managed warehouse to another warehouse.
     */
    public function storeInventoryTransfer(Request $request)
    {
        $managedWarehouseId = Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        if (! $managedWarehouseId) {
            return back()->with('error', 'Bạn chưa được gán kho quản lý để tạo phiếu điều chuyển.');
        }

        $validated = $request->validate([
            'target_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.weight_kg' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $targetWarehouseId = (int) $validated['target_warehouse_id'];
        if ($targetWarehouseId === $managedWarehouseId) {
            return back()->withErrors([
                'target_warehouse_id' => 'Kho nhận phải khác kho nguồn.',
            ])->withInput();
        }

        $normalizedItems = collect($validated['items'])
            ->map(function (array $row) {
                return [
                    'product_variant_id' => (int) $row['product_variant_id'],
                    'quantity' => (int) $row['quantity'],
                    'weight_kg' => round((float) $row['weight_kg'], 3),
                    'unit_cost' => (float) ($row['unit_cost'] ?? 0),
                ];
            })
            ->groupBy('product_variant_id')
            ->map(function (Collection $rows, int $variantId) {
                return [
                    'product_variant_id' => $variantId,
                    'quantity' => (int) $rows->sum('quantity'),
                    'weight_kg' => round((float) $rows->sum('weight_kg'), 3),
                    'unit_cost' => (float) $rows->last()['unit_cost'],
                ];
            })
            ->values();

        try {
            DB::transaction(function () use ($normalizedItems, $managedWarehouseId, $targetWarehouseId, $validated): void {
                $targetWarehouse = Warehouse::query()->find($targetWarehouseId);

                $transfer = WarehouseInventoryTransfer::create([
                    'source_warehouse_id' => $managedWarehouseId,
                    'target_warehouse_id' => $targetWarehouseId,
                    'requested_by' => Auth::id(),
                    'status' => WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE,
                    'note' => trim((string) ($validated['note'] ?? '')) ?: null,
                    'requested_at' => now(),
                ]);

                $exportDocument = InventoryDocument::create([
                    'type' => 'export',
                    'document_date' => now()->toDateString(),
                    'warehouse_id' => $managedWarehouseId,
                    'supplier_id' => null,
                    'shipping_fee' => 0,
                    'notes' => 'Điều chuyển kho #'.($transfer->transfer_code ?? $transfer->id)
                        .' sang '.($targetWarehouse?->name ?? ('Kho #'.$targetWarehouseId)),
                    'user_id' => Auth::id(),
                ]);

                foreach ($normalizedItems as $item) {
                    $variantId = (int) $item['product_variant_id'];
                    $qty = (int) $item['quantity'];
                    $unitCost = (float) $item['unit_cost'];

                    $inventory = Inventory::query()->where([
                        'warehouse_id' => $managedWarehouseId,
                        'product_variant_id' => $variantId,
                    ])->lockForUpdate()->first();

                    $available = $inventory
                        ? max(0, (int) $inventory->quantity - (int) $inventory->reserved_quantity)
                        : 0;

                    if ($available < $qty) {
                        $variant = ProductVariant::query()->find($variantId);
                        throw new \RuntimeException(
                            'Không đủ tồn để điều chuyển cho '.($variant?->name ?? ('biến thể #'.$variantId))
                            .'. Tồn khả dụng: '.$available.', yêu cầu: '.$qty.'.'
                        );
                    }

                    WarehouseInventoryTransferItem::create([
                        'transfer_id' => $transfer->id,
                        'product_variant_id' => $variantId,
                        'quantity' => $qty,
                        'weight_kg' => (float) $item['weight_kg'],
                        'unit_cost' => $unitCost,
                    ]);

                    $exportDocument->items()->create([
                        'product_variant_id' => $variantId,
                        'quantity' => $qty,
                        'unit_cost' => $unitCost,
                    ]);

                    InventoryMovement::create([
                        'inventory_id' => $inventory->id,
                        'quantity' => -$qty,
                        'type' => 'transfer_out',
                        'reference_id' => $transfer->id,
                        'reference_type' => WarehouseInventoryTransfer::class,
                        'user_id' => Auth::id(),
                    ]);

                    $inventory->decrement('quantity', $qty);

                    $totalStock = (int) Inventory::query()
                        ->where('product_variant_id', $variantId)
                        ->sum('quantity');
                    ProductVariant::query()->where('id', $variantId)->update(['stock' => $totalStock]);
                }

                $transfer->update([
                    'export_document_id' => $exportDocument->id,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors([
                'items' => $e->getMessage(),
            ])->withInput();
        }

        return redirect()->route('warehouse.inventory-transfers.index')
            ->with('success', 'Đã tạo phiếu điều chuyển kho thành công. Kho đích có thể vào phần tiếp nhận để nhập kho.');
    }

    /**
     * Update a pending stock transfer and apply its quantity differences to source stock.
     */
    public function updateInventoryTransfer(Request $request, WarehouseInventoryTransfer $transfer)
    {
        $managedWarehouseId = Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        if (! $managedWarehouseId || (int) $transfer->source_warehouse_id !== $managedWarehouseId) {
            abort(403, 'Bạn không có quyền sửa phiếu điều chuyển này.');
        }

        if ($transfer->status !== WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE) {
            return redirect()->route('warehouse.inventory-transfers.index')
                ->with('error', 'Chỉ có thể sửa phiếu đang chờ kho nhận.');
        }

        if ($transfer->dispatchEntry()->exists()) {
            return redirect()->route('warehouse.inventory-transfers.index')
                ->with('error', 'Phiếu hàng đã thuộc một phiếu xuất kho tổng. Hãy xóa phiếu tổng đang mở trước khi sửa hàng điều chuyển.');
        }
        if ($transfer->order_id) {
            return redirect()->route('warehouse.inventory-transfers.index')
                ->with('error', 'Phiếu này đang gắn với đơn thiếu hàng nên không thể sửa thủ công.');
        }

        $validated = $request->validate([
            'target_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.weight_kg' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $targetWarehouseId = (int) $validated['target_warehouse_id'];
        if ($targetWarehouseId === $managedWarehouseId) {
            return back()->withErrors([
                'target_warehouse_id' => 'Kho nhận phải khác kho nguồn.',
            ])->withInput();
        }

        $normalizedItems = collect($validated['items'])
            ->map(fn (array $row) => [
                'product_variant_id' => (int) $row['product_variant_id'],
                'quantity' => (int) $row['quantity'],
                'weight_kg' => round((float) $row['weight_kg'], 3),
                'unit_cost' => (float) ($row['unit_cost'] ?? 0),
            ])
            ->groupBy('product_variant_id')
            ->map(fn (Collection $rows, int $variantId) => [
                'product_variant_id' => $variantId,
                'quantity' => (int) $rows->sum('quantity'),
                'weight_kg' => round((float) $rows->sum('weight_kg'), 3),
                'unit_cost' => (float) $rows->last()['unit_cost'],
            ])
            ->keyBy('product_variant_id');

        try {
            DB::transaction(function () use (
                $transfer,
                $normalizedItems,
                $managedWarehouseId,
                $targetWarehouseId,
                $validated
            ): void {
                $lockedTransfer = WarehouseInventoryTransfer::query()
                    ->lockForUpdate()
                    ->findOrFail($transfer->id);

                if ((int) $lockedTransfer->source_warehouse_id !== $managedWarehouseId) {
                    abort(403, 'Bạn không có quyền sửa phiếu điều chuyển này.');
                }
                if ($lockedTransfer->status !== WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE) {
                    throw new \RuntimeException('Phiếu điều chuyển không còn ở trạng thái có thể sửa.');
                }

                $lockedTransfer->load('items');
                $oldItems = $lockedTransfer->items->keyBy('product_variant_id');
                $variantIds = $oldItems->keys()->merge($normalizedItems->keys())->unique()->values();
                $changes = [];

                foreach ($variantIds as $variantIdValue) {
                    $variantId = (int) $variantIdValue;
                    $oldItem = $oldItems->get($variantId);
                    $newItem = $normalizedItems->get($variantId);
                    $oldQty = (int) ($oldItem?->quantity ?? 0);
                    $newQty = (int) ($newItem['quantity'] ?? 0);
                    $oldCost = (float) ($oldItem?->unit_cost ?? 0);
                    $newCost = (float) ($newItem['unit_cost'] ?? 0);
                    $oldWeight = (float) ($oldItem?->weight_kg ?? 0);
                    $newWeight = (float) ($newItem['weight_kg'] ?? 0);
                    $inventoryDelta = $oldQty - $newQty;

                    if ($inventoryDelta !== 0) {
                        $inventory = Inventory::query()
                            ->where('warehouse_id', $managedWarehouseId)
                            ->where('product_variant_id', $variantId)
                            ->lockForUpdate()
                            ->first();
                        $available = $inventory
                            ? max(0, (int) $inventory->quantity - (int) $inventory->reserved_quantity)
                            : 0;

                        if ($inventoryDelta < 0 && $available < abs($inventoryDelta)) {
                            $variant = ProductVariant::query()->find($variantId);
                            throw new \RuntimeException(
                                'Không đủ tồn để tăng số lượng điều chuyển cho '
                                .($variant?->name ?? ('biến thể #'.$variantId))
                                .'. Tồn khả dụng thêm: '.$available.', cần thêm: '.abs($inventoryDelta).'.'
                            );
                        }
                        if (! $inventory) {
                            throw new \RuntimeException('Không tìm thấy tồn kho nguồn cho biến thể #'.$variantId.'.');
                        }

                        InventoryMovement::create([
                            'inventory_id' => $inventory->id,
                            'quantity' => $inventoryDelta,
                            'type' => 'adjustment',
                            'reference_id' => $lockedTransfer->id,
                            'reference_type' => WarehouseInventoryTransfer::class,
                            'user_id' => Auth::id(),
                        ]);

                        $inventory->increment('quantity', $inventoryDelta);
                        $totalStock = (int) Inventory::query()
                            ->where('product_variant_id', $variantId)
                            ->sum('quantity');
                        ProductVariant::query()->where('id', $variantId)->update(['stock' => $totalStock]);
                    }

                    if ($oldQty !== $newQty || $oldCost !== $newCost || abs($oldWeight - $newWeight) >= 0.0005) {
                        $changes[] = [
                            'variant_id' => $variantId,
                            'old_qty' => $oldQty,
                            'new_qty' => $newQty,
                            'old_cost' => $oldCost,
                            'new_cost' => $newCost,
                            'old_weight_kg' => $oldWeight,
                            'new_weight_kg' => $newWeight,
                        ];
                    }
                }

                $lockedTransfer->items()->delete();
                foreach ($normalizedItems as $item) {
                    $lockedTransfer->items()->create($item);
                }

                $targetWarehouse = Warehouse::query()->find($targetWarehouseId);
                $exportDocument = $lockedTransfer->exportDocument;
                if (! $exportDocument) {
                    $exportDocument = InventoryDocument::create([
                        'type' => 'export',
                        'document_date' => now()->toDateString(),
                        'warehouse_id' => $managedWarehouseId,
                        'supplier_id' => null,
                        'shipping_fee' => 0,
                        'user_id' => Auth::id(),
                    ]);
                    $lockedTransfer->export_document_id = $exportDocument->id;
                }

                $exportDocument->items()->delete();
                foreach ($normalizedItems as $item) {
                    $exportDocument->items()->create([
                        'product_variant_id' => $item['product_variant_id'],
                        'quantity' => $item['quantity'],
                        'unit_cost' => $item['unit_cost'],
                    ]);
                }
                $exportDocument->update([
                    'notes' => 'Điều chuyển kho #'.($lockedTransfer->transfer_code ?? $lockedTransfer->id)
                        .' sang '.($targetWarehouse?->name ?? ('Kho #'.$targetWarehouseId)),
                    'edit_count' => (int) $exportDocument->edit_count + 1,
                ]);

                \App\Models\InventoryDocumentEdit::create([
                    'inventory_document_id' => $exportDocument->id,
                    'user_id' => Auth::id(),
                    'edit_number' => (int) $exportDocument->edit_count,
                    'notes' => 'Cập nhật từ phiếu điều chuyển '.($lockedTransfer->transfer_code ?? '#'.$lockedTransfer->id),
                    'changes' => $changes ?: null,
                ]);

                $lockedTransfer->update([
                    'target_warehouse_id' => $targetWarehouseId,
                    'note' => trim((string) ($validated['note'] ?? '')) ?: null,
                    'export_document_id' => $exportDocument->id,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        return redirect()->route('warehouse.inventory-transfers.index')
            ->with('success', 'Đã cập nhật phiếu điều chuyển kho thành công.');
    }

    /**
     * Incoming stock transfers waiting to be received.
     */
    public function incomingInventoryTransfers()
    {
        $managedWarehouseId = Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        if (! $managedWarehouseId) {
            return redirect()->route('warehouse.dashboard')
                ->with('error', 'Bạn chưa được gán kho quản lý để tiếp nhận điều chuyển.');
        }

        $transfers = WarehouseInventoryTransfer::query()
            ->with([
                'sourceWarehouse:id,name',
                'targetWarehouse:id,name',
                'requester:id,name',
                'receiver:id,name',
                'order:id,code,customer_id,warehouse_id,created_at,delivery_date,accounting_sales_import_batch_id',
                'order.customer:id,name',
                'items.variant.product',
                'dispatchEntry.slip',
            ])
            ->where('target_warehouse_id', $managedWarehouseId)
            ->whereIn('status', [
                WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE,
                WarehouseInventoryTransfer::STATUS_RECEIVED_COMPLETED,
            ])
            ->orderByRaw("CASE WHEN status = 'pending_receive' THEN 0 ELSE 1 END")
            ->latest('id')
            ->paginate(12);

        $pendingCount = $transfers->getCollection()
            ->where('status', WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE)
            ->count();

        return view('warehouse.transfers.inventory-incoming', compact('transfers', 'pendingCount'));
    }

    /**
     * Confirm receipt of a stock transfer at destination warehouse.
     */
    public function confirmIncomingInventoryTransfer(WarehouseInventoryTransfer $transfer)
    {
        $managedWarehouseId = Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null;
        if (! $managedWarehouseId || (int) $transfer->target_warehouse_id !== $managedWarehouseId) {
            abort(403, 'Bạn không có quyền tiếp nhận phiếu điều chuyển này.');
        }

        if ($transfer->status !== WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE) {
            return back()->with('error', 'Phiếu điều chuyển này không còn ở trạng thái chờ tiếp nhận.');
        }

        $transfer->loadMissing('dispatchEntry.slip');
        if ($transfer->dispatchEntry?->slip?->status === \App\Models\WarehouseDispatchSlip::STATUS_DRAFT) {
            return back()->with('error', 'Phiếu xuất kho tổng '.$transfer->dispatchEntry->slip->code.' chưa được kho xuất chốt.');
        }

        $transfer->loadMissing(['items.variant.product', 'sourceWarehouse', 'targetWarehouse', 'order.items.variant']);

        try {
            DB::transaction(function () use ($transfer): void {
                $lockedTransfer = WarehouseInventoryTransfer::query()
                    ->with(['items', 'sourceWarehouse', 'order.items.variant'])
                    ->lockForUpdate()
                    ->findOrFail($transfer->id);
                if ($lockedTransfer->status !== WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE) {
                    throw new \RuntimeException('Phiếu điều chuyển này đã được người khác tiếp nhận.');
                }

                $importDocument = InventoryDocument::create([
                    'type' => 'import',
                    'document_date' => now()->toDateString(),
                    'warehouse_id' => (int) $lockedTransfer->target_warehouse_id,
                    'supplier_id' => null,
                    'shipping_fee' => 0,
                    'notes' => 'Tiếp nhận điều chuyển kho #'.($lockedTransfer->transfer_code ?? $lockedTransfer->id)
                        .' từ '.($lockedTransfer->sourceWarehouse?->name ?? ('Kho #'.$lockedTransfer->source_warehouse_id))
                        .($lockedTransfer->order ? ' cho đơn '.($lockedTransfer->order->code ?: '#'.$lockedTransfer->order->id) : ''),
                    'user_id' => Auth::id(),
                ]);

                foreach ($lockedTransfer->items as $item) {
                    $variantId = (int) $item->product_variant_id;
                    $qty = (int) $item->quantity;
                    $unitCost = (float) $item->unit_cost;

                    $importDocument->items()->create([
                        'product_variant_id' => $variantId,
                        'quantity' => $qty,
                        'unit_cost' => $unitCost,
                    ]);

                    $inventory = Inventory::query()->firstOrCreate(
                        [
                            'warehouse_id' => (int) $lockedTransfer->target_warehouse_id,
                            'product_variant_id' => $variantId,
                        ],
                        [
                            'quantity' => 0,
                            'reserved_quantity' => 0,
                            'low_stock_threshold' => 5,
                        ]
                    );
                    $inventory = Inventory::query()->whereKey($inventory->id)->lockForUpdate()->firstOrFail();

                    InventoryMovement::create([
                        'inventory_id' => $inventory->id,
                        'quantity' => $qty,
                        'type' => 'transfer_in',
                        'reference_id' => $lockedTransfer->id,
                        'reference_type' => WarehouseInventoryTransfer::class,
                        'user_id' => Auth::id(),
                    ]);

                    $inventory->increment('quantity', $qty);

                    $totalStock = (int) Inventory::query()
                        ->where('product_variant_id', $variantId)
                        ->sum('quantity');
                    ProductVariant::query()->where('id', $variantId)->update(['stock' => $totalStock]);
                }

                $lockedTransfer->update([
                    'status' => WarehouseInventoryTransfer::STATUS_RECEIVED_COMPLETED,
                    'received_by' => Auth::id(),
                    'received_at' => now(),
                    'import_document_id' => $importDocument->id,
                ]);

                if ($lockedTransfer->order
                && (int) $lockedTransfer->order->warehouse_id === (int) $lockedTransfer->target_warehouse_id
                && in_array((string) $lockedTransfer->order->status, array_merge(self::READY_TO_PACK_STATUSES, [Order::STATUS_PACKING]), true)
                ) {
                    $order = Order::query()->with('items.variant')->lockForUpdate()->findOrFail($lockedTransfer->order->id);
                    $this->reserveOrderStockAtWarehouse($order, (int) $lockedTransfer->target_warehouse_id);
                    $order->forceFill([
                        'stock_sufficient' => null,
                        'stock_shortage_detail' => null,
                        'stock_alert_status' => null,
                    ])->save();
                    OrderHistory::create([
                        'order_id' => $order->id,
                        'action' => 'warehouse_order_goods_received',
                        'user_id' => Auth::id(),
                        'role' => 'warehouse',
                        'status_before' => $order->status,
                        'status_after' => $order->status,
                        'note' => 'Kho nhận đã tiếp nhận phiếu '.$lockedTransfer->transfer_code.' và bổ sung hàng để tiếp tục đóng đơn.',
                    ]);
                }
            });
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $this->refreshQueuedOrdersAfterInventoryChange((int) $transfer->target_warehouse_id);

        return back()->with('success', $transfer->order_id
            ? 'Đã tiếp nhận hàng, nhập kho và bổ sung giữ hàng cho đơn '.($transfer->order?->code ?: '#'.$transfer->order_id).'. Kho có thể tiếp tục hoàn thiện đóng hàng.'
            : 'Đã tiếp nhận phiếu điều chuyển và cập nhật nhập kho thành công.');
    }

    /**
     * Show a single inventory document (for warehouse users).
     */
    public function showDocument(InventoryDocument $document)
    {
        $warehouseId = Auth::user()->warehouse_id;
        if ($warehouseId && (int) $document->warehouse_id !== (int) $warehouseId) {
            abort(403, 'Bạn không có quyền xem phiếu kho này.');
        }
        $document->load('items.productVariant.product', 'warehouse', 'user', 'edits.user');

        return view('warehouse.document-show', compact('document'));
    }

    /**
     * Return JSON data for populating the edit-stock-in modal.
     */
    public function editStockIn(Request $request, InventoryDocument $document)
    {
        $warehouseId = Auth::user()->warehouse_id;
        if ($warehouseId && (int) $document->warehouse_id !== (int) $warehouseId) {
            abort(403, 'Bạn không có quyền sửa phiếu này.');
        }
        if ($document->type !== 'import') {
            abort(400, 'Chỉ hỗ trợ điều chỉnh phiếu nhập kho.');
        }

        if (! $document->document_date->isToday()) {
            return response()->json([
                'ok' => false,
                'message' => 'Chỉ được điều chỉnh phiếu nhập kho trong ngày hôm nay.',
            ], 422);
        }

        $maxEdits = (int) (\App\Models\Setting::get('stock_in_max_edits', 3));
        if ($document->edit_count >= $maxEdits) {
            return response()->json([
                'ok' => false,
                'message' => "Phiếu này đã đạt giới hạn {$maxEdits} lần điều chỉnh.",
            ], 422);
        }

        $document->load('items.productVariant.product', 'supplier', 'warehouse');

        return response()->json([
            'ok' => true,
            'document' => [
                'id' => $document->id,
                'document_number' => $document->document_number ?? '#'.$document->id,
                'document_date' => $document->document_date->format('Y-m-d'),
                'supplier_name' => $document->supplier?->name ?? 'Không có nhà cung cấp',
                'warehouse_name' => $document->warehouse?->name ?? 'Kho #'.$document->warehouse_id,
                'notes' => $document->notes,
                'shipping_fee' => (float) $document->shipping_fee,
                'edit_count' => $document->edit_count,
                'max_edits' => $maxEdits,
            ],
            'items' => $document->items->map(function ($item) {
                $variant = $item->productVariant;
                $product = $variant?->product;

                return [
                    'id' => $item->id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_name' => $product?->name ?? 'Sản phẩm',
                    'variant_name' => $variant?->name ?? $product?->name ?? 'SP #'.$item->product_variant_id,
                    'sku' => $variant?->sku ?? '',
                    'unit_label' => $product?->unit_label ?? 'Cái',
                    'weight_per_unit' => (float) ($variant?->effective_kg ?? 1),
                    'quantity' => (int) $item->quantity,
                    'unit_cost' => (float) $item->unit_cost,
                    'note' => $item->note,
                ];
            })->values(),
        ]);
    }

    /**
     * Apply an edit to a stock-in document (adjust quantities/costs).
     */
    public function updateStockIn(Request $request, InventoryDocument $document)
    {
        $warehouseId = Auth::user()->warehouse_id;
        if ($warehouseId && (int) $document->warehouse_id !== (int) $warehouseId) {
            abort(403, 'Bạn không có quyền sửa phiếu này.');
        }
        if ($document->type !== 'import') {
            return back()->with('error', 'Chỉ hỗ trợ điều chỉnh phiếu nhập kho.');
        }

        if (! $document->document_date->isToday()) {
            return back()->with('error', 'Chỉ được điều chỉnh phiếu nhập kho trong ngày hôm nay.');
        }

        $maxEdits = (int) (\App\Models\Setting::get('stock_in_max_edits', 3));
        if ($document->edit_count >= $maxEdits) {
            return back()->with('error', "Phiếu này đã đạt giới hạn {$maxEdits} lần điều chỉnh.");
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
            'edit_notes' => 'nullable|string|max:500',
            'shipping_fee' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:inventory_document_items,id',
            'items.*.quantity' => 'required|integer|min:0',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.note' => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($validated, $document) {
                $document->load('items');

                $itemMap = $document->items->keyBy('id');

                // Ensure all submitted item IDs belong to this document.
                foreach ($validated['items'] as $row) {
                    if (! $itemMap->has((int) $row['id'])) {
                        throw new \RuntimeException('Dòng sản phẩm không thuộc phiếu này.');
                    }
                }

                $changes = [];

                foreach ($validated['items'] as $row) {
                    $item = $itemMap[(int) $row['id']];
                    $oldQty = (int) $item->quantity;
                    $newQty = (int) $row['quantity'];
                    $oldCost = (float) $item->unit_cost;
                    $newCost = (float) $row['unit_cost'];
                    $oldNote = (string) ($item->note ?? '');
                    $newNote = trim((string) ($row['note'] ?? ''));
                    $delta = $newQty - $oldQty;

                    if ($delta === 0 && $newCost === $oldCost && $newNote === $oldNote) {
                        continue; // Nothing changed for this item
                    }

                    // Adjust inventory quantity by delta.
                    if ($delta !== 0) {
                        $inventory = Inventory::lockForUpdate()
                            ->where('product_variant_id', $item->product_variant_id)
                            ->where('warehouse_id', $document->warehouse_id)
                            ->first();

                        if (! $inventory) {
                            throw new \RuntimeException(
                                'Không tìm thấy bản ghi tồn kho cho sản phẩm #'.$item->product_variant_id
                            );
                        }

                        if ($delta < 0) {
                            // Reducing import quantity — ensure we don't go below quantity
                            // already consumed by "Hoàn tất đóng hàng" orders (packed_waiting_pickup
                            // and downstream statuses). These are "hard" deductions that cannot
                            // be undone by re-running Ráp đơn hàng.
                            $completedPackingStatuses = [
                                Order::STATUS_READY_TO_SHIP, // packed_waiting_pickup
                                Order::STATUS_DELIVERING,
                                Order::STATUS_RETURNED_COMPLETED,
                                'delivering',
                                'delivered',
                                'completed',
                                'shipping',
                            ];

                            $completedQty = (int) \App\Models\OrderItem::query()
                                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                                ->join('inventory_reservations', 'inventory_reservations.order_item_id', '=', 'order_items.id')
                                ->join('inventories', 'inventories.id', '=', 'inventory_reservations.inventory_id')
                                ->where('order_items.product_variant_id', $item->product_variant_id)
                                ->where('inventories.warehouse_id', $document->warehouse_id)
                                ->whereIn('orders.status', $completedPackingStatuses)
                                ->sum('inventory_reservations.quantity');

                            $newTotal = (int) $inventory->quantity + $delta;
                            if ($newTotal < $completedQty) {
                                throw new \RuntimeException(
                                    'Không thể giảm tồn kho thấp hơn số lượng đã hoàn tất đóng hàng.'
                                );
                            }
                        }

                        // Record adjustment movement.
                        InventoryMovement::create([
                            'inventory_id' => $inventory->id,
                            'quantity' => $delta,
                            'type' => 'adjustment',
                            'reference_id' => $document->id,
                            'reference_type' => InventoryDocument::class,
                            'user_id' => Auth::id(),
                        ]);

                        $inventory->increment('quantity', $delta);

                        // Sync variant stock.
                        $totalStock = (int) Inventory::where('product_variant_id', $item->product_variant_id)->sum('quantity');
                        ProductVariant::where('id', $item->product_variant_id)->update(['stock' => $totalStock]);
                    }

                    $changes[] = [
                        'item_id' => $item->id,
                        'variant_id' => $item->product_variant_id,
                        'old_qty' => $oldQty,
                        'new_qty' => $newQty,
                        'old_cost' => $oldCost,
                        'new_cost' => $newCost,
                        'old_note' => $oldNote,
                        'new_note' => $newNote,
                    ];

                    $item->update([
                        'quantity' => $newQty,
                        'unit_cost' => $newCost,
                        'note' => $newNote !== '' ? $newNote : null,
                    ]);
                }

                // Update document header.
                $document->update([
                    'notes' => $validated['notes'] ?? $document->notes,
                    'shipping_fee' => $validated['shipping_fee'] ?? $document->shipping_fee,
                    'edit_count' => $document->edit_count + 1,
                ]);

                // Record edit history.
                \App\Models\InventoryDocumentEdit::create([
                    'inventory_document_id' => $document->id,
                    'user_id' => Auth::id(),
                    'edit_number' => $document->edit_count, // already incremented above
                    'notes' => $validated['edit_notes'] ?? null,
                    'changes' => $changes ?: null,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        // After stock adjustment, re-run Ráp đơn hàng to refresh stock_sufficient on all queued orders.
        $this->syncAllQueuedOrdersStockSufficiency(
            Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null
        );

        return redirect()->route('warehouse.stock-in')->with('success',
            'Đã điều chỉnh phiếu '.($document->document_number ?? '#'.$document->id).' thành công.'
        );
    }

    /**
     * Shared logic for creating import/export documents.
     */
    private function storeDocument(Request $request, string $type)
    {

        $isImport = $type === 'import';

        $userWarehouseId = Auth::user()->warehouse_id;
        if (! $userWarehouseId) {
            return back()->withErrors(['warehouse_id' => 'Bạn chưa được gán kho quản lý, không thể tạo phiếu nhập kho.'])->withInput();
        }

        $validated = $request->validate([
            'document_date' => 'required|date',
            // 'warehouse_id'  => 'required|exists:warehouses,id', // bỏ không nhận từ request
            'supplier_id' => $isImport ? 'required|exists:suppliers,id' : 'nullable|exists:suppliers,id',
            'shipping_fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.note' => 'nullable|string|max:500',
            'items.*.source_price_id' => 'nullable|exists:supplier_product_prices,id',
        ]);

        try {
            DB::transaction(function () use ($validated, $type, $userWarehouseId, $isImport) {
                $document = InventoryDocument::create([
                    'type' => $type,
                    'document_date' => $validated['document_date'],
                    'warehouse_id' => $userWarehouseId,
                    'supplier_id' => $validated['supplier_id'] ?? null,
                    'shipping_fee' => $validated['shipping_fee'] ?? 0,
                    'notes' => $validated['notes'] ?? null,
                    'user_id' => Auth::id(),
                ]);

                foreach ($validated['items'] as $itemData) {
                    $sourcePriceId = $itemData['source_price_id'] ?? null;
                    if ($isImport && $sourcePriceId) {
                        $variant = ProductVariant::query()->find($itemData['product_variant_id']);
                        $price = \App\Models\SupplierProductPrice::query()
                            ->where('id', $sourcePriceId)
                            ->where('supplier_id', $validated['supplier_id'])
                            ->where('product_id', $variant?->product_id)
                            ->first();

                        if (! $price) {
                            throw new \RuntimeException('Bảng giá đã chọn không khớp với nhà cung cấp hoặc sản phẩm.');
                        }

                        $itemData['unit_cost'] = (float) $price->stock_in_unit_cost;
                    }

                    $document->items()->create([
                        'product_variant_id' => $itemData['product_variant_id'],
                        'quantity' => $itemData['quantity'],
                        'unit_cost' => $itemData['unit_cost'],
                        'note' => $itemData['note'] ?? null,
                        'source_price_id' => $sourcePriceId,
                    ]);

                    $inventory = Inventory::firstOrCreate(
                        [
                            'product_variant_id' => $itemData['product_variant_id'],
                            'warehouse_id' => $userWarehouseId,
                        ],
                        ['quantity' => 0]
                    );

                    $qty = (int) $itemData['quantity'];
                    if ($type === 'export') {
                        $available = max(0, (int) $inventory->quantity - (int) $inventory->reserved_quantity);
                        if ($available < $qty) {
                            throw new \RuntimeException('Số lượng xuất vượt quá tồn kho khả dụng cho sản phẩm.');
                        }
                        $qty = -$qty;
                    }

                    InventoryMovement::create([
                        'inventory_id' => $inventory->id,
                        'quantity' => $qty,
                        'type' => $type,
                        'reference_id' => $document->id,
                        'reference_type' => InventoryDocument::class,
                        'user_id' => Auth::id(),
                    ]);

                    $inventory->increment('quantity', $qty);

                    $totalStock = (int) Inventory::where('product_variant_id', $itemData['product_variant_id'])->sum('quantity');
                    ProductVariant::where('id', $itemData['product_variant_id'])->update(['stock' => $totalStock]);
                }
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        $label = $type === 'import' ? 'nhập' : 'xuất';
        $route = $type === 'import' ? 'warehouse.stock-in' : 'warehouse.stock-out';

        // After a new stock-in, re-run Ráp đơn hàng to refresh stock_sufficient on all queued orders.
        if ($type === 'import') {
            $this->syncAllQueuedOrdersStockSufficiency(
                Auth::user()?->warehouse_id ? (int) Auth::user()->warehouse_id : null
            );
        }

        return redirect()->route($route)->with('success', 'Đã tạo phiếu '.$label.' kho thành công.');
    }

    /**
     * Inventory (Tồn kho) - View current stock levels
     */
    public function inventory(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status');
        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : Carbon::today()->toDateString();
        $summary = app(WarehouseInventorySummaryService::class)
            ->buildConsolidated($selectedDate, $search, $status);
        $page = max(1, (int) $request->input('page', 1));
        $perPage = (int) $request->input('per_page', 50);
        if (! in_array($perPage, [25, 50, 100, 200], true)) {
            $perPage = 50;
        }
        $products = new LengthAwarePaginator(
            $summary['rows']->forPage($page, $perPage)->values(),
            $summary['rows']->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('warehouse.inventory.index', [
            'products' => $products,
            'warehouses' => $summary['warehouses'],
            'summaryTotals' => $summary['totals'],
            'selectedDate' => $summary['selectedDate'],
        ]);
    }

    /**
     * Daily inventory ledger grouped by product variant and date.
     */
    public function inventoryDaily(Request $request)
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $search = trim((string) $request->input('search', ''));
        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : Carbon::today()->subDays(6)->startOfDay();
        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : Carbon::today()->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        if ($from->diffInDays($to) > 30) {
            $from = $to->copy()->subDays(30)->startOfDay();
        }

        $activeWarehouseIds = Warehouse::query()
            ->where('status', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $inventoryScope = fn ($query) => $query->whereIn('warehouse_id', $activeWarehouseIds->all());

        $variants = ProductVariant::query()
            ->with('product:id,name,unit')
            ->whereHas('inventories', $inventoryScope)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('product_id')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $variantIds = $variants->getCollection()->pluck('id')->map(fn ($id) => (int) $id)->values();
        $dates = collect();
        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($to)) {
            $dates->push([
                'date' => $cursor->toDateString(),
                'label' => $cursor->format('d/m/Y'),
            ]);
            $cursor->addDay();
        }

        $currentByVariant = Inventory::query()
            ->selectRaw('product_variant_id, COALESCE(SUM(quantity), 0) as quantity')
            ->whereIn('product_variant_id', $variantIds->all())
            ->whereIn('warehouse_id', $activeWarehouseIds->all())
            ->groupBy('product_variant_id')
            ->pluck('quantity', 'product_variant_id');

        $movementBase = InventoryMovement::query()
            ->join('inventories', 'inventories.id', '=', 'inventory_movements.inventory_id')
            ->whereIn('inventories.product_variant_id', $variantIds->all())
            ->whereIn('inventories.warehouse_id', $activeWarehouseIds->all());

        $movementAfterTo = (clone $movementBase)
            ->where('inventory_movements.created_at', '>', $to)
            ->selectRaw('inventories.product_variant_id, COALESCE(SUM(inventory_movements.quantity), 0) as quantity')
            ->groupBy('inventories.product_variant_id')
            ->pluck('quantity', 'inventories.product_variant_id');

        $movementsByVariantAndDate = (clone $movementBase)
            ->whereBetween('inventory_movements.created_at', [$from, $to])
            ->selectRaw('inventories.product_variant_id, DATE(inventory_movements.created_at) as movement_date')
            ->selectRaw('COALESCE(SUM(CASE WHEN inventory_movements.quantity > 0 THEN inventory_movements.quantity ELSE 0 END), 0) as import_qty')
            ->selectRaw('COALESCE(ABS(SUM(CASE WHEN inventory_movements.quantity < 0 THEN inventory_movements.quantity ELSE 0 END)), 0) as export_qty')
            ->groupBy('inventories.product_variant_id', 'movement_date')
            ->get()
            ->groupBy('product_variant_id')
            ->map(fn ($rows) => $rows->keyBy('movement_date'));

        $dailyRows = $variants->getCollection()->map(function (ProductVariant $variant) use ($dates, $currentByVariant, $movementAfterTo, $movementsByVariantAndDate) {
            $variantId = (int) $variant->id;
            $closing = (int) ($currentByVariant[$variantId] ?? 0) - (int) ($movementAfterTo[$variantId] ?? 0);
            $variantMovements = $movementsByVariantAndDate->get($variantId, collect());
            $days = collect();

            foreach ($dates->reverse() as $date) {
                $movement = $variantMovements->get($date['date']);
                $import = (int) ($movement?->import_qty ?? 0);
                $export = (int) ($movement?->export_qty ?? 0);
                $opening = $closing - $import + $export;

                $days->prepend([
                    'opening' => $opening,
                    'import' => $import,
                    'total' => $opening + $import,
                    'export' => $export,
                    'closing' => $closing,
                ], $date['date']);
                $closing = $opening;
            }

            return [
                'product' => (string) ($variant->product?->name ?? 'Sản phẩm'),
                'variant' => (string) ($variant->name ?: 'Mặc định'),
                'sku' => (string) ($variant->sku ?: '—'),
                'unit' => (string) ($variant->product?->unit_label ?? '—'),
                'days' => $days,
            ];
        });

        $pageTotals = $dates->mapWithKeys(function ($date) use ($dailyRows) {
            $dateKey = $date['date'];

            return [$dateKey => [
                'opening' => (int) $dailyRows->sum(fn ($row) => $row['days'][$dateKey]['opening']),
                'import' => (int) $dailyRows->sum(fn ($row) => $row['days'][$dateKey]['import']),
                'total' => (int) $dailyRows->sum(fn ($row) => $row['days'][$dateKey]['total']),
                'export' => (int) $dailyRows->sum(fn ($row) => $row['days'][$dateKey]['export']),
                'closing' => (int) $dailyRows->sum(fn ($row) => $row['days'][$dateKey]['closing']),
            ]];
        });

        return view('warehouse.inventory.daily', compact(
            'variants',
            'dailyRows',
            'dates',
            'pageTotals',
            'from',
            'to',
            'search'
        ));
    }

    /**
     * Manually trigger auto-cancel of overdue orders to restore accurate stock.
     */
    public function cancelOverdueOrders(Request $request)
    {
        \Artisan::call('orders:auto-cancel-overdue');
        $output = trim(\Artisan::output());

        // Parse how many orders were cancelled from command output
        preg_match('/đã hủy (\d+) đơn/u', $output, $matches);
        $count = (int) ($matches[1] ?? 0);

        if ($count > 0) {
            return back()->with('success', "Đã hủy {$count} đơn quá hạn và trả lại tồn kho.");
        }

        return back()->with('info', 'Không có đơn nào quá hạn cần xử lý.');
    }

    /**
     * Product Management (Quản lý theo sản phẩm) - View product inventory across warehouses
     */
    public function products(Request $request)
    {
        $query = Product::with(['variants' => function ($q) {
            $q->with('inventory');
        }]);

        // Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhereHas('variants', function ($variantQuery) use ($search) {
                        $variantQuery->where('sku', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        $products = $query->latest()->paginate(15);

        return view('warehouse.products.index', compact('products'));
    }

    /**
     * Reports & Statistics - Comprehensive warehouse metrics
     */
    public function reports(Request $request)
    {
        // Default to current day if not specified
        $rangeType = $request->input('range_type', 'day');
        $selectedDate = $request->input('selected_date', Carbon::now()->toDateString());

        $warehouseId = Auth::user()->warehouse_id;

        // Calculate date range based on selection
        $dates = $this->getDateRange($rangeType, $selectedDate);
        $from = Carbon::parse($dates['from']);
        $to = Carbon::parse($dates['to']);

        // Stock In Statistics
        $stockInData = InventoryDocument::where('type', 'import')
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->whereBetween('document_date', [$from, $to])
            ->with('items')
            ->get()
            ->groupBy(function ($doc) use ($rangeType) {
                if ($rangeType === 'day' || $rangeType === 'custom') {
                    return $doc->document_date->format('d/m');
                } elseif ($rangeType === 'week') {
                    return 'W'.$doc->document_date->weekOfYear;
                } elseif ($rangeType === 'month') {
                    return $doc->document_date->format('m/Y');
                }

                return $doc->document_date->format('Y');
            })
            ->map(fn ($docs) => [
                'count' => $docs->count(),
                'quantity' => $docs->flatMap(fn ($d) => $d->items)->sum('quantity'),
            ]);

        // Stock Out Statistics
        $stockOutData = InventoryDocument::where('type', 'export')
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->whereBetween('document_date', [$from, $to])
            ->with('items')
            ->get()
            ->groupBy(function ($doc) use ($rangeType) {
                if ($rangeType === 'day' || $rangeType === 'custom') {
                    return $doc->document_date->format('d/m');
                } elseif ($rangeType === 'week') {
                    return 'W'.$doc->document_date->weekOfYear;
                } elseif ($rangeType === 'month') {
                    return $doc->document_date->format('m/Y');
                }

                return $doc->document_date->format('Y');
            })
            ->map(fn ($docs) => [
                'count' => $docs->count(),
                'quantity' => $docs->flatMap(fn ($d) => $d->items)->sum('quantity'),
            ]);

        // Inventory Movement Statistics
        $movementData = InventoryMovement::whereHas('inventory', fn ($q) => $q->when($warehouseId, fn ($q2) => $q2->where('warehouse_id', $warehouseId)))
            ->whereBetween('created_at', [$from, $to])
            ->get()
            ->groupBy('type')
            ->map(fn ($movements) => [
                'count' => $movements->count(),
                'quantity' => $movements->sum('quantity'),
            ]);

        // Top products by movement
        $topProducts = InventoryMovement::whereHas('inventory', fn ($q) => $q->when($warehouseId, fn ($q2) => $q2->where('warehouse_id', $warehouseId)))
            ->whereBetween('created_at', [$from, $to])
            ->with('inventory.productVariant.product')
            ->get()
            ->groupBy('inventory.product_variant_id')
            ->map(fn ($movements) => [
                'product' => $movements->first()->inventory->productVariant->product,
                'variant' => $movements->first()->inventory->productVariant,
                'quantity' => $movements->sum('quantity'),
                'count' => $movements->count(),
            ])
            ->sortByDesc('quantity')
            ->take(10);

        // Statistics by product and variant (based on import/export documents)
        $reportItems = InventoryDocumentItem::query()
            ->whereHas('document', function ($q) use ($warehouseId, $from, $to) {
                $q->whereBetween('document_date', [$from, $to])
                    ->whereIn('type', ['import', 'export'])
                    ->when($warehouseId, fn ($q2) => $q2->where('warehouse_id', $warehouseId));
            })
            ->with([
                'document:id,type,warehouse_id,document_date',
                'productVariant:id,product_id,name,sku',
                'productVariant.product:id,name,slug,unit',
            ])
            ->get();

        $variantStats = $reportItems
            ->groupBy('product_variant_id')
            ->map(function ($items) {
                $first = $items->first();
                $variant = $first?->productVariant;
                $product = $variant?->product;

                $inQty = (int) $items
                    ->filter(fn ($item) => $item->document?->type === 'import')
                    ->sum('quantity');

                $outQty = (int) $items
                    ->filter(fn ($item) => $item->document?->type === 'export')
                    ->sum('quantity');

                return [
                    'product_id' => $product?->id,
                    'product_name' => $product?->name ?? 'N/A',
                    'product_sku' => $product?->sku,
                    'unit_label' => $product?->unit_label ?? 'Cái',
                    'variant_id' => $variant?->id,
                    'variant_name' => $variant?->name ?? 'N/A',
                    'variant_sku' => $variant?->sku,
                    'in_qty' => $inQty,
                    'out_qty' => $outQty,
                    'net_qty' => $inQty - $outQty,
                ];
            })
            ->sortByDesc('net_qty')
            ->values();

        $productStats = $variantStats
            ->groupBy('product_id')
            ->map(function ($items) {
                $first = $items->first();
                $inQty = (int) $items->sum('in_qty');
                $outQty = (int) $items->sum('out_qty');

                return [
                    'product_id' => $first['product_id'],
                    'product_name' => $first['product_name'],
                    'product_sku' => $first['product_sku'],
                    'unit_label' => $first['unit_label'] ?? 'Cái',
                    'variant_count' => $items->count(),
                    'in_qty' => $inQty,
                    'out_qty' => $outQty,
                    'net_qty' => $inQty - $outQty,
                ];
            })
            ->sortByDesc('net_qty')
            ->values();

        // Overall statistics
        $totals = [
            'total_stock_in' => InventoryDocument::where('type', 'import')
                ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
                ->whereBetween('document_date', [$from, $to])
                ->withSum('items', 'quantity')
                ->get()
                ->sum('items_sum_quantity') ?? 0,
            'total_stock_out' => InventoryDocument::where('type', 'export')
                ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
                ->whereBetween('document_date', [$from, $to])
                ->withSum('items', 'quantity')
                ->get()
                ->sum('items_sum_quantity') ?? 0,
            'total_movements' => InventoryMovement::whereHas('inventory', fn ($q) => $q->when($warehouseId, fn ($q2) => $q2->where('warehouse_id', $warehouseId)))
                ->whereBetween('created_at', [$from, $to])
                ->count(),
        ];

        return view('warehouse.reports.index', compact(
            'rangeType',
            'selectedDate',
            'from',
            'to',
            'stockInData',
            'stockOutData',
            'movementData',
            'topProducts',
            'totals',
            'productStats',
            'variantStats'
        ));
    }

    /**
     * Helper method to calculate date range
     */
    private function getDateRange($rangeType, $selectedDate)
    {
        $date = Carbon::parse($selectedDate);

        return match ($rangeType) {
            'day' => [
                'from' => $date->toDateString(),
                'to' => $date->toDateString(),
            ],
            'week' => [
                'from' => $date->startOfWeek()->toDateString(),
                'to' => $date->endOfWeek()->toDateString(),
            ],
            'month' => [
                'from' => $date->startOfMonth()->toDateString(),
                'to' => $date->endOfMonth()->toDateString(),
            ],
            'year' => [
                'from' => $date->startOfYear()->toDateString(),
                'to' => $date->endOfYear()->toDateString(),
            ],
            'custom' => [
                'from' => $selectedDate,
                'to' => $selectedDate,
            ],
            default => [
                'from' => $date->toDateString(),
                'to' => $date->toDateString(),
            ],
        };
    }

    private function authorizePackingOrderAccess(Order $order): void
    {
        $user = Auth::user();
        $managedWarehouseId = $user?->warehouse_id ? (int) $user->warehouse_id : null;
        $orderWarehouseId = $order->warehouse_id ? (int) $order->warehouse_id : null;
        $isPackingOperator = $user?->hasRole('warehouse') || $user?->hasRole('package');

        if (! $user?->hasRole('admin') && $isPackingOperator && $managedWarehouseId && $orderWarehouseId && $managedWarehouseId !== $orderWarehouseId) {
            abort(403, 'Đơn hàng thuộc kho khác.');
        }
    }

    private function canProcessOrderOnCurrentRun(Order $order): bool
    {
        return $order->accounting_sales_import_batch_id !== null
            || (bool) $order->skip_auto_cancel
            || $order->hasCompletedAdjustment()
            || ($order->created_at && $order->created_at->isToday());
    }

    private function packingActorRole(): string
    {
        return request()->routeIs('package.*') ? 'package' : 'warehouse';
    }
}
