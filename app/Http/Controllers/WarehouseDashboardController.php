<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\InventoryDocument;
use App\Models\InventoryDocumentItem;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\Product;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WarehouseDashboardController extends Controller
{
    private const READY_TO_PACK_STATUSES = [
        'approved',
        Order::STATUS_READY_TO_PACK,
    ];

    private const PACKED_STATUSES = [
        'packed',
        Order::STATUS_READY_TO_SHIP,
    ];

    public function __construct()
    {
        $this->middleware(['auth', 'role:warehouse,admin']);
    }

    public function index()
    {
        $today = Carbon::today();

        $stats = [
            'ready_to_pack' => Order::whereIn('status', self::READY_TO_PACK_STATUSES)->count(),
            'packing'       => Order::where('status', Order::STATUS_PACKING)->count(),
            'packed_today'  => Order::whereIn('status', self::PACKED_STATUSES)
                ->whereDate('updated_at', $today)->count(),
            'returning'     => Order::where('status', Order::STATUS_RETURNING)->count(),
            'done_today'    => Order::whereIn('status', self::PACKED_STATUSES)
                ->whereDate('updated_at', $today)->count(),
        ];

        $recentPacked = Order::with('customer')
            ->whereIn('status', self::PACKED_STATUSES)
            ->whereDate('updated_at', $today)
            ->orderByDesc('updated_at')
            ->take(5)
            ->get();

        return view('warehouse.dashboard', compact('stats', 'recentPacked'));
    }

    /**
     * List orders awaiting packing or currently being packed.
     */
    public function orders()
    {
        $today = Carbon::today();

        $orders = Order::with(['customer', 'user', 'items'])
            ->whereDate('created_at', $today)
            ->orderByDesc('created_at')
            ->get();

        return view('warehouse.orders.index', compact('orders'));
    }

    /**
     * Start packing: ready_to_pack → packing
     */
    public function startPacking(Order $order)
    {
        if (!in_array($order->status, self::READY_TO_PACK_STATUSES, true)) {
            return back()->with('error', 'Đơn hàng không ở trạng thái Chờ đóng gói.');
        }

        $statusBefore = $order->status;

        $order->update(['status' => Order::STATUS_PACKING]);

        OrderHistory::create([
            'order_id'      => $order->id,
            'action'        => 'start_packing',
            'user_id'       => Auth::id(),
            'role'          => 'warehouse',
            'status_before' => $statusBefore,
            'status_after'  => Order::STATUS_PACKING,
            'note'          => 'Bắt đầu đóng gói đơn hàng',
        ]);

        return back()->with('success', 'Đã bắt đầu đóng gói đơn #' . $order->code);
    }

    /**
     * Complete packing: packing → packed_waiting_pickup (ready to ship)
     */
    public function completePacking(Order $order)
    {
        if ($order->status !== Order::STATUS_PACKING) {
            return back()->with('error', 'Đơn hàng không đang ở trạng thái Đang đóng gói.');
        }

        $order->update(['status' => Order::STATUS_READY_TO_SHIP]);

        OrderHistory::create([
            'order_id'      => $order->id,
            'action'        => 'complete_packing',
            'user_id'       => Auth::id(),
            'role'          => 'warehouse',
            'status_before' => Order::STATUS_PACKING,
            'status_after'  => Order::STATUS_READY_TO_SHIP,
            'note'          => 'Hoàn thành đóng gói – Sẵn sàng giao hàng',
        ]);

        return back()->with('success', 'Đơn #' . $order->code . ' đã đóng gói xong, sẵn sàng giao!');
    }

    /**
     * List returning orders waiting for warehouse confirmation.
     */
    public function returns()
    {
        $orders = Order::with(['customer', 'shipper', 'items'])
            ->where('status', Order::STATUS_RETURNING)
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('warehouse.returns.index', compact('orders'));
    }

    /**
     * Confirm return receipt: returning → returned_completed + restore inventory
     */
    public function confirmReturn(Order $order)
    {
        if ($order->status !== Order::STATUS_RETURNING) {
            return back()->with('error', 'Đơn hàng không đang ở trạng thái Đang trả hàng.');
        }

        DB::transaction(function () use ($order) {
            $order->update(['status' => Order::STATUS_RETURNED_COMPLETED]);

            // Restore inventory for each item
            foreach ($order->items as $item) {
                Inventory::where('product_variant_id', $item->product_variant_id)
                    ->where('warehouse_id', Auth::user()->warehouse_id)
                    ->increment('quantity', $item->quantity);
            }

            OrderHistory::create([
                'order_id'      => $order->id,
                'action'        => 'confirm_return',
                'user_id'       => Auth::id(),
                'role'          => 'warehouse',
                'status_before' => Order::STATUS_RETURNING,
                'status_after'  => Order::STATUS_RETURNED_COMPLETED,
                'note'          => 'Kho xác nhận đã nhận hàng trả – Tồn kho đã cập nhật',
            ]);
        });

        return back()->with('success', 'Đã xác nhận nhập kho hàng trả – Đơn #' . $order->code);
    }

    /**
     * Stock In (Nhập kho) - View list of stock in documents
     */
    public function stockIn(Request $request)
    {
        $query = InventoryDocument::where('type', 'import')
            ->with('warehouse', 'user', 'items.productVariant.product');

        // Filter by warehouse if user has warehouse assignment
        $warehouseId = Auth::user()->warehouse_id;
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        // Date range filter
        $from = $request->input('from_date', Carbon::now()->subDays(30)->toDateString());
        $to = $request->input('to_date', Carbon::now()->toDateString());
        $query->whereBetween('document_date', [$from, $to]);

        $stockInDocuments = $query->latest('document_date')->paginate(15);

        return view('warehouse.stock-in.index', compact('stockInDocuments', 'from', 'to'));
    }

    /**
     * Stock Out (Xuất kho) - View list of stock out documents
     */
    public function stockOut(Request $request)
    {
        $query = InventoryDocument::where('type', 'export')
            ->with('warehouse', 'user', 'items.productVariant.product');

        // Filter by warehouse if user has warehouse assignment
        $warehouseId = Auth::user()->warehouse_id;
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        // Date range filter
        $from = $request->input('from_date', Carbon::now()->subDays(30)->toDateString());
        $to = $request->input('to_date', Carbon::now()->toDateString());
        $query->whereBetween('document_date', [$from, $to]);

        $stockOutDocuments = $query->latest('document_date')->paginate(15);

        return view('warehouse.stock-out.index', compact('stockOutDocuments', 'from', 'to'));
    }

    /**
     * Inventory (Tồn kho) - View current stock levels
     */
    public function inventory(Request $request)
    {
        $query = Inventory::with('productVariant.product', 'warehouse');

        // Filter by warehouse if user has warehouse assignment
        $warehouseId = Auth::user()->warehouse_id;
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        // Search by product name or SKU
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('productVariant.product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            })->orWhereHas('productVariant', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filter by status
        $status = $request->input('status');
        if ($status === 'low_stock') {
            $query->whereColumn('quantity', '<=', 'low_stock_threshold');
        } elseif ($status === 'out_of_stock') {
            $query->where('quantity', 0);
        }

        $inventories = $query->latest('updated_at')->paginate(20);
        $stats = [
            'total_items' => Inventory::when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))->count(),
            'low_stock' => Inventory::when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
                ->whereColumn('quantity', '<=', 'low_stock_threshold')->count(),
            'out_of_stock' => Inventory::when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
                ->where('quantity', 0)->count(),
        ];

        return view('warehouse.inventory.index', compact('inventories', 'stats'));
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
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->whereBetween('document_date', [$from, $to])
            ->with('items')
            ->get()
            ->groupBy(function ($doc) use ($rangeType) {
                if ($rangeType === 'day' || $rangeType === 'custom') {
                    return $doc->document_date->format('d/m');
                } elseif ($rangeType === 'week') {
                    return 'W' . $doc->document_date->weekOfYear;
                } elseif ($rangeType === 'month') {
                    return $doc->document_date->format('m/Y');
                }
                return $doc->document_date->format('Y');
            })
            ->map(fn($docs) => [
                'count' => $docs->count(),
                'quantity' => $docs->flatMap(fn($d) => $d->items)->sum('quantity'),
            ]);

        // Stock Out Statistics
        $stockOutData = InventoryDocument::where('type', 'export')
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->whereBetween('document_date', [$from, $to])
            ->with('items')
            ->get()
            ->groupBy(function ($doc) use ($rangeType) {
                if ($rangeType === 'day' || $rangeType === 'custom') {
                    return $doc->document_date->format('d/m');
                } elseif ($rangeType === 'week') {
                    return 'W' . $doc->document_date->weekOfYear;
                } elseif ($rangeType === 'month') {
                    return $doc->document_date->format('m/Y');
                }
                return $doc->document_date->format('Y');
            })
            ->map(fn($docs) => [
                'count' => $docs->count(),
                'quantity' => $docs->flatMap(fn($d) => $d->items)->sum('quantity'),
            ]);

        // Inventory Movement Statistics
        $movementData = InventoryMovement::whereHas('inventory', fn($q) => $q->when($warehouseId, fn($q2) => $q2->where('warehouse_id', $warehouseId)))
            ->whereBetween('created_at', [$from, $to])
            ->get()
            ->groupBy('type')
            ->map(fn($movements) => [
                'count' => $movements->count(),
                'quantity' => $movements->sum('quantity'),
            ]);

        // Top products by movement
        $topProducts = InventoryMovement::whereHas('inventory', fn($q) => $q->when($warehouseId, fn($q2) => $q2->where('warehouse_id', $warehouseId)))
            ->whereBetween('created_at', [$from, $to])
            ->with('inventory.productVariant.product')
            ->get()
            ->groupBy('inventory.product_variant_id')
            ->map(fn($movements) => [
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
                    ->when($warehouseId, fn($q2) => $q2->where('warehouse_id', $warehouseId));
            })
            ->with([
                'document:id,type,warehouse_id,document_date',
                'productVariant:id,product_id,name,sku',
                'productVariant.product:id,name,sku',
            ])
            ->get();

        $variantStats = $reportItems
            ->groupBy('product_variant_id')
            ->map(function ($items) {
                $first = $items->first();
                $variant = $first?->productVariant;
                $product = $variant?->product;

                $inQty = (int) $items
                    ->filter(fn($item) => $item->document?->type === 'import')
                    ->sum('quantity');

                $outQty = (int) $items
                    ->filter(fn($item) => $item->document?->type === 'export')
                    ->sum('quantity');

                return [
                    'product_id' => $product?->id,
                    'product_name' => $product?->name ?? 'N/A',
                    'product_sku' => $product?->sku,
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
                ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
                ->whereBetween('document_date', [$from, $to])
                ->withSum('items', 'quantity')
                ->get()
                ->sum('items_sum_quantity') ?? 0,
            'total_stock_out' => InventoryDocument::where('type', 'export')
                ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
                ->whereBetween('document_date', [$from, $to])
                ->withSum('items', 'quantity')
                ->get()
                ->sum('items_sum_quantity') ?? 0,
            'total_movements' => InventoryMovement::whereHas('inventory', fn($q) => $q->when($warehouseId, fn($q2) => $q2->where('warehouse_id', $warehouseId)))
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
}
