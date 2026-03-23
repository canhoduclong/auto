<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\InventoryDocument;
use App\Models\InventoryDocumentItem;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
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

    private const EDITABLE_LOGISTICS_STATUSES = [
        Order::STATUS_PACKING,
    ];

    public function __construct()
    {
        $this->middleware(['auth', 'role:warehouse,admin']);
    }

    public function index(Request $request)
    {
        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))->startOfDay()
            : Carbon::today();

        $dateString = $selectedDate->toDateString();

        $dailyOrdersQuery = Order::with('customer')
            ->whereDate('created_at', $dateString);

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
            'ready_to_pack' => Order::whereIn('status', self::READY_TO_PACK_STATUSES)->count(),
            'packing'       => Order::where('status', Order::STATUS_PACKING)->count(),
            'packed_today'  => Order::whereIn('status', self::PACKED_STATUSES)
                ->whereDate('updated_at', $dateString)->count(),
            'returning'     => Order::where('status', Order::STATUS_RETURNING)->count(),
            'done_today'    => Order::whereIn('status', self::PACKED_STATUSES)
                ->whereDate('updated_at', $dateString)->count(),
            'orders_in_day' => (clone $dailyOrdersQuery)->count(),
        ];

        $recentPacked = Order::with('customer')
            ->whereIn('status', self::PACKED_STATUSES)
            ->whereDate('updated_at', $dateString)
            ->orderByDesc('updated_at')
            ->take(5)
            ->get();

        return view('warehouse.dashboard', compact(
            'stats',
            'recentPacked',
            'selectedDate',
            'dailyOrders',
            'approvalStats'
        ));
    }

    /**
     * List orders awaiting packing or currently being packed.
     */
    public function orders(Request $request)
    {
        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : Carbon::today()->toDateString();

        $status = $request->input('status');
        $today = Carbon::today();
        $startDate = $today->copy()->subDays(6)->toDateString();

        $dailyCountsQuery = Order::query()
            ->selectRaw('DATE(created_at) as day_key, COUNT(*) as total')
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $today->toDateString());

        if (!empty($status)) {
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
            'items.product.avatar.media',
            'items.variant' => function ($query) {
                $query->withAvailableStock()->with('avatar.media');
            },
        ])
            ->whereDate('created_at', $selectedDate);

        if (!empty($status)) {
            $ordersQuery->where('status', $status);
        }

        $orders = $ordersQuery
            ->orderByDesc('created_at')
            ->get();

        return view('warehouse.orders.index', compact('orders', 'selectedDate', 'status', 'quickDates'));
    }

    /**
     * Start packing: ready_to_pack → packing
     */
    public function startPacking(Request $request, Order $order)
    {
        if (!$order->created_at || !$order->created_at->isToday()) {
            $message = 'Chỉ được xử lý đơn có ngày hôm nay.';

            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $message,
                ], 422);
            }

            return back()->with('error', $message);
        }

        if (!in_array($order->status, self::READY_TO_PACK_STATUSES, true)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Đơn hàng không ở trạng thái Chờ đóng gói.',
                ], 422);
            }

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

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Đã bắt đầu đóng gói đơn #' . $order->code,
                'order' => [
                    'id' => $order->id,
                    'status' => Order::STATUS_PACKING,
                    'status_label' => 'Đang đóng gói',
                    'status_class' => 'bg-warning text-dark',
                ],
            ]);
        }

        return back()->with('success', 'Đã bắt đầu đóng gói đơn #' . $order->code);
    }

    /**
     * Warehouse updates actual package weight and shipping fee for an order.
     */
    public function updateLogistics(Request $request, Order $order)
    {
        $expectsJson = $request->expectsJson();

        if (!$order->created_at || !$order->created_at->isToday()) {
            $message = 'Chỉ được xử lý đơn có ngày hôm nay.';

            if ($expectsJson) {
                return response()->json([
                    'ok' => false,
                    'message' => $message,
                ], 422);
            }

            return back()->with('error', $message);
        }

        if (!in_array($order->status, self::EDITABLE_LOGISTICS_STATUSES, true)) {
            if ($expectsJson) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Không thể cập nhật kg/phí ship ở trạng thái hiện tại của đơn hàng.',
                ], 422);
            }

            return back()->with('error', 'Không thể cập nhật kg/phí ship ở trạng thái hiện tại của đơn hàng.');
        }

        $order->loadMissing('items');

        $itemIds = $order->items->pluck('id')->all();

        $rules = [
            'item_id' => ['nullable', 'integer'],
            'item_actual_weight' => ['nullable', 'numeric', 'min:0'],
            'charge_shipping_fee' => ['nullable', 'boolean'],
            'shipping_fee'  => ['nullable', 'numeric', 'min:0', 'required_if:charge_shipping_fee,1'],
            'charge_foam_box_fee' => ['nullable', 'boolean'],
            'foam_box_price' => ['nullable', 'numeric', 'min:0', 'required_if:charge_foam_box_fee,1'],
        ];

        if ($request->filled('item_id')) {
            $rules['item_actual_weight'] = ['required', 'numeric', 'min:0'];
        }

        $validated = $request->validate($rules);

        $oldWeight = $order->actual_weight;
        $oldShippingFee = $order->shipping_fee;
        $oldChargeShippingFee = $order->charge_shipping_fee;
        $oldFoamBoxPrice = $order->foam_box_price;
        $oldChargeFoamBoxFee = $order->charge_foam_box_fee;

        if ($request->filled('item_id')) {
            $itemId = (int) $validated['item_id'];
            if (!in_array($itemId, $itemIds, true)) {
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
                $item->actual_weight = round((float) $validated['item_actual_weight'], 3);
                $item->save();
            }
        }

        $chargeShippingFee = $oldChargeShippingFee;
        $shippingFee = (float) ($oldShippingFee ?? 0);
        if ($request->has('charge_shipping_fee')) {
            $chargeShippingFee = $request->boolean('charge_shipping_fee');
            $shippingFee = $chargeShippingFee
                ? round((float) ($validated['shipping_fee'] ?? 0), 2)
                : 0.0;
        }

        $chargeFoamBoxFee = $oldChargeFoamBoxFee;
        $foamBoxPrice = (float) ($oldFoamBoxPrice ?? 0);
        if ($request->has('charge_foam_box_fee')) {
            $chargeFoamBoxFee = $request->boolean('charge_foam_box_fee');
            $foamBoxPrice = $chargeFoamBoxFee
                ? round((float) ($validated['foam_box_price'] ?? 0), 2)
                : 0.0;
        }

        $actualWeight = round((float) $order->items()->sum('actual_weight'), 3);

        $order->update([
            'actual_weight' => $actualWeight,
            'charge_shipping_fee' => $chargeShippingFee,
            'shipping_fee' => $shippingFee,
            'charge_foam_box_fee' => $chargeFoamBoxFee,
            'foam_box_price' => $foamBoxPrice,
            // Keep total_weight aligned with real measured package weight in warehouse flow.
            'total_weight' => $actualWeight,
        ]);

        OrderHistory::create([
            'order_id'      => $order->id,
            'action'        => 'warehouse_update_logistics',
            'user_id'       => Auth::id(),
            'role'          => 'warehouse',
            'status_before' => $order->status,
            'status_after'  => $order->status,
            'note'          => sprintf(
                'Cập nhật logistics: Kg thực tế %s → %s | Tính phí ship %s → %s | Phí ship %s → %s | Thùng xốp %s → %s | Giá thùng xốp %s → %s',
                number_format((float) $oldWeight, 3, '.', ''),
                number_format($actualWeight, 3, '.', ''),
                ((bool) $oldChargeShippingFee) ? 'Có' : 'Không',
                $chargeShippingFee ? 'Có' : 'Không',
                number_format((float) $oldShippingFee, 2, '.', ''),
                number_format($shippingFee, 2, '.', ''),
                ((bool) $oldChargeFoamBoxFee) ? 'Có' : 'Không',
                $chargeFoamBoxFee ? 'Có' : 'Không',
                number_format((float) $oldFoamBoxPrice, 2, '.', ''),
                number_format($foamBoxPrice, 2, '.', '')
            ),
        ]);

        if ($request->filled('item_id')) {
            if ($expectsJson) {
                return response()->json([
                    'ok' => true,
                    'message' => 'Đã lưu kg thực tế cho sản phẩm trong đơn #' . $order->code,
                    'order' => [
                        'id' => $order->id,
                        'actual_weight' => (float) $actualWeight,
                    ],
                ]);
            }

            return back()->with('success', 'Đã lưu kg thực tế cho sản phẩm trong đơn #' . $order->code);
        }

        if ($expectsJson) {
            return response()->json([
                'ok' => true,
                'message' => 'Đã cập nhật phí ship/thùng xốp cho đơn #' . $order->code,
                'order' => [
                    'id' => $order->id,
                    'actual_weight' => (float) $actualWeight,
                    'shipping_fee' => (float) $shippingFee,
                    'foam_box_price' => (float) $foamBoxPrice,
                    'charge_shipping_fee' => (bool) $chargeShippingFee,
                    'charge_foam_box_fee' => (bool) $chargeFoamBoxFee,
                ],
            ]);
        }

        return back()->with('success', 'Đã cập nhật phí ship/thùng xốp cho đơn #' . $order->code);
    }

    /**
     * Complete packing: packing → packed_waiting_pickup (ready to ship)
     */
    public function completePacking(Request $request, Order $order)
    {
        if (!$order->created_at || !$order->created_at->isToday()) {
            return back()->with('error', 'Chỉ được xử lý đơn có ngày hôm nay.');
        }

        if ($order->status !== Order::STATUS_PACKING) {
            return back()->with('error', 'Đơn hàng không đang ở trạng thái Đang đóng gói.');
        }

        if ($order->actual_weight === null || $order->shipping_fee === null) {
            return back()->with('error', 'Vui lòng cập nhật Kg thực tế và phí ship trước khi hoàn thành đóng gói.');
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
     * Admin can reopen a packed warehouse order back to packing for edits.
     */
    public function reopenPacking(Request $request, Order $order)
    {
        $user = $request->user();

        abort_unless($user?->hasRole('admin'), 403);

        if (!$order->created_at || !$order->created_at->isToday()) {
            return back()->with('error', 'Chỉ được xử lý đơn có ngày hôm nay.');
        }

        if (!in_array($order->status, self::PACKED_STATUSES, true)) {
            return back()->with('error', 'Chỉ có thể bỏ khóa các đơn đang ở bước hoàn tất kho.');
        }

        $previousStatus = $order->status;

        $order->update([
            'status' => Order::STATUS_PACKING,
        ]);

        OrderHistory::create([
            'order_id'      => $order->id,
            'action'        => 'admin_reopen_packing',
            'user_id'       => Auth::id(),
            'role'          => 'admin',
            'status_before' => $previousStatus,
            'status_after'  => Order::STATUS_PACKING,
            'note'          => 'Admin bỏ khóa chỉnh sửa và đưa đơn quay lại bước đóng gói của kho',
        ]);

        return back()->with('success', 'Admin đã bỏ khóa chỉnh sửa cho đơn #' . $order->code . '.');
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

        $warehouseId = Auth::user()->warehouse_id;
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $from = $request->input('from_date', Carbon::now()->subDays(30)->toDateString());
        $to   = $request->input('to_date',   Carbon::now()->toDateString());
        $query->whereBetween('document_date', [$from, $to]);

        $stockInDocuments = $query->latest('document_date')->paginate(15);
        $warehouses       = $warehouseId
            ? Warehouse::where('id', $warehouseId)->get()
            : Warehouse::all();
        $productVariants  = ProductVariant::with('product')->orderBy('name')->get();

        return view('warehouse.stock-in.index', compact('stockInDocuments', 'from', 'to', 'warehouses', 'productVariants'));
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
        $to   = $request->input('to_date',   Carbon::now()->toDateString());
        $query->whereBetween('document_date', [$from, $to]);

        $stockOutDocuments = $query->latest('document_date')->paginate(15);
        $warehouses        = $warehouseId
            ? Warehouse::where('id', $warehouseId)->get()
            : Warehouse::all();
        $productVariants   = ProductVariant::with('product')->orderBy('name')->get();

        return view('warehouse.stock-out.index', compact('stockOutDocuments', 'from', 'to', 'warehouses', 'productVariants'));
    }

    /**
     * Store a new Phiếu Xuất Kho (export document).
     */
    public function storeStockOut(Request $request)
    {
        return $this->storeDocument($request, 'export');
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
        $document->load('items.productVariant.product', 'warehouse', 'user');
        return view('warehouse.document-show', compact('document'));
    }

    /**
     * Shared logic for creating import/export documents.
     */
    private function storeDocument(Request $request, string $type)
    {
        $validated = $request->validate([
            'document_date' => 'required|date',
            'warehouse_id'  => 'required|exists:warehouses,id',
            'shipping_fee'  => 'nullable|numeric|min:0',
            'notes'         => 'nullable|string|max:1000',
            'items'         => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity'           => 'required|integer|min:1',
            'items.*.unit_cost'          => 'required|numeric|min:0',
        ]);

        // Warehouse users can only create documents for their own warehouse
        $userWarehouseId = Auth::user()->warehouse_id;
        if ($userWarehouseId && (int) $validated['warehouse_id'] !== (int) $userWarehouseId) {
            return back()->withErrors(['warehouse_id' => 'Bạn chỉ được tạo phiếu cho kho của mình.'])->withInput();
        }

        try {
            DB::transaction(function () use ($validated, $type) {
                $document = InventoryDocument::create([
                    'type'          => $type,
                    'document_date' => $validated['document_date'],
                    'warehouse_id'  => $validated['warehouse_id'],
                    'shipping_fee'  => $validated['shipping_fee'] ?? 0,
                    'notes'         => $validated['notes'] ?? null,
                    'user_id'       => Auth::id(),
                ]);

                foreach ($validated['items'] as $itemData) {
                    $document->items()->create([
                        'product_variant_id' => $itemData['product_variant_id'],
                        'quantity'           => $itemData['quantity'],
                        'unit_cost'          => $itemData['unit_cost'],
                    ]);

                    $inventory = Inventory::firstOrCreate(
                        [
                            'product_variant_id' => $itemData['product_variant_id'],
                            'warehouse_id'       => $validated['warehouse_id'],
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
                        'inventory_id'   => $inventory->id,
                        'quantity'       => $qty,
                        'type'           => $type,
                        'reference_id'   => $document->id,
                        'reference_type' => InventoryDocument::class,
                        'user_id'        => Auth::id(),
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

        return redirect()->route($route)->with('success', 'Đã tạo phiếu ' . $label . ' kho thành công.');
    }

    /**
     * Inventory (Tồn kho) - View current stock levels
     */
    public function inventory(Request $request)
    {
        $warehouseId = Auth::user()->warehouse_id;
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status');
        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : Carbon::today()->toDateString();
        $dayStart = Carbon::parse($selectedDate)->startOfDay();
        $dayEnd = Carbon::parse($selectedDate)->endOfDay();

        $inventoryScope = function ($query) use ($warehouseId, $status) {
            if ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }

            if ($status === 'low_stock') {
                $query->whereColumn('quantity', '<=', 'low_stock_threshold');
            } elseif ($status === 'out_of_stock') {
                $query->where('quantity', 0);
            }
        };

        $products = Product::query()
            ->with([
                'avatar.media',
                'variants' => function ($variantQuery) use ($inventoryScope, $dayStart, $dayEnd) {
                    $variantQuery->with([
                        'avatar.media',
                        'inventories' => function ($inventoryQuery) use ($inventoryScope, $dayStart, $dayEnd) {
                            $inventoryScope($inventoryQuery);

                            $inventoryQuery->with([
                                'warehouse',
                                'movements' => function ($movementQuery) use ($dayStart, $dayEnd) {
                                    $movementQuery->whereBetween('created_at', [$dayStart, $dayEnd]);
                                },
                                'reservations' => function ($reservationQuery) use ($dayStart, $dayEnd) {
                                    $reservationQuery->whereBetween('reserved_at', [$dayStart, $dayEnd]);
                                },
                            ])
                                ->orderBy('warehouse_id');
                        },
                    ])
                        ->whereHas('inventories', $inventoryScope)
                        ->orderBy('name')
                        ->orderBy('sku');
                },
            ])
            ->whereHas('variants', function ($variantQuery) use ($inventoryScope) {
                $variantQuery->whereHas('inventories', $inventoryScope);
            })
            ->when($search !== '', function ($productQuery) use ($search, $inventoryScope) {
                $productQuery->where(function ($searchQuery) use ($search, $inventoryScope) {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhereHas('variants', function ($variantQuery) use ($search, $inventoryScope) {
                            $variantQuery->whereHas('inventories', $inventoryScope)
                                ->where(function ($variantSearchQuery) use ($search) {
                                    $variantSearchQuery->where('name', 'like', "%{$search}%")
                                        ->orWhere('sku', 'like', "%{$search}%");
                                });
                        });
                });
            })
            ->orderBy('name')
            ->paginate(12);

        $movementQuery = InventoryMovement::query()
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->when($warehouseId, function ($query) use ($warehouseId) {
                $query->whereHas('inventory', function ($inventoryQuery) use ($warehouseId) {
                    $inventoryQuery->where('warehouse_id', $warehouseId);
                });
            });

        $reservationQuery = InventoryReservation::query()
            ->whereBetween('reserved_at', [$dayStart, $dayEnd])
            ->when($warehouseId, function ($query) use ($warehouseId) {
                $query->whereHas('inventory', function ($inventoryQuery) use ($warehouseId) {
                    $inventoryQuery->where('warehouse_id', $warehouseId);
                });
            });

        $stats = [
            'total_items' => Inventory::when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))->count(),
            'total_products' => Product::whereHas('variants', function ($variantQuery) use ($warehouseId) {
                $variantQuery->whereHas('inventories', function ($inventoryQuery) use ($warehouseId) {
                    if ($warehouseId) {
                        $inventoryQuery->where('warehouse_id', $warehouseId);
                    }
                });
            })->count(),
            'low_stock' => Inventory::when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
                ->whereColumn('quantity', '<=', 'low_stock_threshold')->count(),
            'out_of_stock' => Inventory::when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
                ->where('quantity', 0)->count(),
            'daily_import' => (clone $movementQuery)->where('quantity', '>', 0)->sum('quantity'),
            'daily_export' => abs((int) ((clone $movementQuery)->where('quantity', '<', 0)->sum('quantity'))),
            'daily_reserved' => (clone $reservationQuery)->sum('quantity'),
            'daily_reservation_rows' => (clone $reservationQuery)->count(),
        ];

        return view('warehouse.inventory.index', compact('products', 'stats', 'selectedDate'));
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
                'productVariant.product:id,name,slug',
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
