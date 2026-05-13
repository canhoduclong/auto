<?php

namespace App\Http\Controllers;

use App\Models\AdminEvent;
use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryDocumentItem;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Models\OrderReturn;
use App\Models\ReturnItem;
use App\Models\Transaction;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderReturnController extends Controller
{
    private const RETURN_RECEIPT_NOTE_PREFIX = '[order_return:#';

    private function logOrderHistory(Order $order, string $action, ?string $statusBefore, ?string $statusAfter, ?string $note = null): void
    {
        $user = Auth::user();

        OrderHistory::create([
            'order_id' => $order->id,
            'action' => $action,
            'user_id' => $user?->id,
            'role' => $user?->roles->pluck('name')->first(),
            'status_before' => $statusBefore,
            'status_after' => $statusAfter,
            'note' => $note,
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = OrderReturn::with([
            'order',
            'customer',
            'warehouse',
            'creator',
            'shipConfirmer',
            'warehouseConfirmer',
            'refundTransaction',
            'returnItems.productVariant.product',
        ])->latest();

        $user = Auth::user();
        if ($user && $user->hasRole('warehouse')) {
            if (!$user->warehouse_id) {
                $query->whereRaw('1=0');
            } else {
                $query->where('warehouse_id', $user->warehouse_id);
            }
        }

        $returns = $query->paginate(10)->appends($request->query());

        $receiptMap = $this->receiptMapForReturnIds(
            $returns->getCollection()->pluck('id')->map(fn ($id) => (int) $id)->all()
        );

        $syncHistory = AdminEvent::with('actor')
            ->where('event_type', 'order_return_sync')
            ->latest()
            ->limit(10)
            ->get();

        $mismatchCount = $returns->getCollection()
            ->filter(fn (OrderReturn $return) => $return->status === 'warehouse_received' && !isset($receiptMap[$return->id]))
            ->count();

        return view('order-returns.index', compact('returns', 'receiptMap', 'syncHistory', 'mismatchCount'));
    }

    public function syncWarehouseReceipts()
    {
        $user = Auth::user();
        abort_unless($user && ($user->hasRole('warehouse') || $user->hasRole('admin')), 403);

        $returnsQuery = OrderReturn::with(['order.items', 'returnItems'])
            ->where('status', 'warehouse_received')
            ->orderByDesc('warehouse_confirmed_at');

        if ($user->hasRole('warehouse')) {
            if (!$user->warehouse_id) {
                return back()->with('error', 'Tai khoan kho chua duoc gan kho de dong bo.');
            }

            $returnsQuery->where('warehouse_id', $user->warehouse_id);
        }

        $returns = $returnsQuery->get();

        $summary = [
            'checked' => 0,
            'created_count' => 0,
            'updated_count' => 0,
            'unchanged_count' => 0,
            'items_adjusted_total' => 0,
            'receipts' => [],
        ];

        DB::transaction(function () use ($returns, $user, &$summary) {
            foreach ($returns as $return) {
                $summary['checked']++;

                [$document, $result, $adjustedItems] = $this->syncReceiptForReturn($return, (int) $user->id);

                if ($result === 'created') {
                    $summary['created_count']++;
                } elseif ($result === 'updated') {
                    $summary['updated_count']++;
                } else {
                    $summary['unchanged_count']++;
                }

                if ($result !== 'unchanged') {
                    $summary['items_adjusted_total'] += $adjustedItems;
                    $summary['receipts'][] = [
                        'order_return_id' => (int) $return->id,
                        'document_id' => (int) $document->id,
                        'document_number' => (string) ($document->document_number ?? ('#' . $document->id)),
                        'action' => $result,
                        'items_adjusted' => (int) $adjustedItems,
                    ];
                }
            }
        });

        AdminEvent::create([
            'actor_id' => $user->id,
            'event_type' => 'order_return_sync',
            'action' => 'sync',
            'subject_type' => OrderReturn::class,
            'subject_id' => null,
            'title' => 'Dong bo don tra hang voi phieu nhap kho',
            'message' => 'Da kiem tra ' . $summary['checked'] . ' don tra. Tao moi ' . $summary['created_count'] . ', cap nhat ' . $summary['updated_count'] . '.',
            'metadata' => $summary,
            'url' => route('order-returns.index'),
        ]);

        return redirect()
            ->route('order-returns.index')
            ->with('success', 'Da refresh dong bo phieu nhap kho tu don tra hang.')
            ->with('sync_result', $summary);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorizeReturnCreation();

        $selectedOrderId = request()->integer('order_id');

        $orders = Order::with(['items.variant.product', 'customer'])
            ->whereIn('status', ['picked_up', 'shipping', 'completed'])
            ->when($selectedOrderId, fn ($q) => $q->where('id', $selectedOrderId))
            ->latest()
            ->take(50)
            ->get();

        $orderPayload = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'items' => $order->items->map(function ($item) {
                    $variant = $item->variant;
                    $productName = optional(optional($variant)->product)->name ?: 'San pham';
                    $variantName = optional($variant)->name ?: ('Variant #' . $item->product_variant_id);

                    return [
                        'variant_id' => $item->product_variant_id,
                        'name' => $productName . ' - ' . $variantName,
                        'max_qty' => (int) $item->quantity,
                    ];
                })->values(),
            ];
        })->values();

        $warehouses = Warehouse::orderBy('name')->get();

        return view('order-returns.create', [
            'orders' => $orders,
            'warehouses' => $warehouses,
            'orderPayload' => $orderPayload,
            'submitRoute' => route('order-returns.store'),
            'selectedOrderId' => $selectedOrderId,
        ]);
    }

    public function createForMyOrder(Order $order)
    {
        $this->authorizeReturnCreation($order);

        $order->load(['items.variant.product', 'customer']);

        $orders = collect([$order]);
        $orderPayload = $orders->map(function ($orderItem) {
            return [
                'id' => $orderItem->id,
                'items' => $orderItem->items->map(function ($item) {
                    $variant = $item->variant;
                    $productName = optional(optional($variant)->product)->name ?: 'San pham';
                    $variantName = optional($variant)->name ?: ('Variant #' . $item->product_variant_id);

                    return [
                        'variant_id' => $item->product_variant_id,
                        'name' => $productName . ' - ' . $variantName,
                        'max_qty' => (int) $item->quantity,
                    ];
                })->values(),
            ];
        })->values();

        $warehouses = Warehouse::orderBy('name')->get();

        return view('order-returns.create', [
            'orders' => $orders,
            'warehouses' => $warehouses,
            'orderPayload' => $orderPayload,
            'submitRoute' => route('site.order-returns.store', $order),
            'selectedOrderId' => $order->id,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'reason' => 'nullable|string|max:2000',
            'evidence_image' => 'required|image|max:5120',
            'note' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.condition' => 'nullable|string|max:255',
        ]);

        $order = Order::with(['customer', 'items'])->findOrFail($data['order_id']);
        $this->authorizeReturnCreation($order);

        if (!$order->customer_id) {
            return back()->with('error', __('order_returns.messages.no_customer'))->withInput();
        }

        $orderedByVariant = $order->items
            ->groupBy('product_variant_id')
            ->map(fn ($items) => (int) $items->sum('quantity'));

        $alreadyReturnedByVariant = ReturnItem::query()
            ->whereHas('orderReturn', function ($q) use ($order) {
                $q->where('order_id', $order->id)
                    ->whereIn('status', ['requested', 'ship_confirmed', 'warehouse_received']);
            })
            ->selectRaw('product_variant_id, SUM(quantity) as qty')
            ->groupBy('product_variant_id')
            ->pluck('qty', 'product_variant_id')
            ->map(fn ($qty) => (int) $qty);

        $requestedByVariant = collect($data['items'])
            ->groupBy('product_variant_id')
            ->map(fn ($items) => (int) collect($items)->sum('quantity'));

        foreach ($requestedByVariant as $variantId => $requestedQty) {
            $orderedQty = (int) ($orderedByVariant[$variantId] ?? 0);
            if ($orderedQty <= 0) {
                return back()->with('error', __('order_returns.messages.item_not_in_order'))->withInput();
            }

            $alreadyReturned = (int) ($alreadyReturnedByVariant[$variantId] ?? 0);
            if (($alreadyReturned + $requestedQty) > $orderedQty) {
                return back()->with('error', __('order_returns.messages.qty_exceeds'))->withInput();
            }
        }

        $evidenceImagePath = $request->file('evidence_image')->store('orders/returns', 'public');

        $createdReturn = null;
        DB::transaction(function () use ($data, $order, $evidenceImagePath, &$createdReturn) {
            $orderReturn = OrderReturn::create([
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'warehouse_id' => $data['warehouse_id'],
                'created_by' => Auth::id(),
                'status' => 'requested',
                'reason' => $data['reason'] ?? null,
                'evidence_image_path' => $evidenceImagePath,
                'note' => $data['note'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                ReturnItem::create([
                    'order_return_id' => $orderReturn->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'quantity' => $item['quantity'],
                    'condition' => $item['condition'] ?? null,
                ]);
            }

            $createdReturn = $orderReturn;
        });

        if ($createdReturn && $order->exists) {
            $status = (string) $order->status;
            $this->logOrderHistory($order, 'create_return_request', $status, $status, 'Tao yeu cau tra hang #' . $createdReturn->id);
        }

        return redirect()->route('order-returns.index')->with('success', __('order_returns.messages.created'));
    }

    public function storeForMyOrder(Request $request, Order $order)
    {
        $this->authorizeReturnCreation($order);

        $request->merge(['order_id' => $order->id]);

        return $this->store($request);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $return = OrderReturn::with([
            'order',
            'customer',
            'warehouse',
            'creator',
            'shipConfirmer',
            'warehouseConfirmer',
            'refundTransaction',
            'returnItems.productVariant.product',
        ])->findOrFail($id);

        return view('order-returns.show', compact('return'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function shipConfirm(OrderReturn $orderReturn)
    {
        $user = Auth::user();
        abort_unless($user && ($user->hasRole('shipper') || $user->hasRole('ship') || $user->hasRole('admin')), 403);

        $order = $orderReturn->order;
        $statusBefore = $order ? (string) $order->status : null;

        if (!in_array($orderReturn->status, ['requested'], true)) {
            return back()->with('error', __('order_returns.messages.invalid_status_ship_confirm'));
        }

        $orderReturn->update([
            'status' => 'ship_confirmed',
            'ship_confirmed_by' => $user->id,
            'ship_confirmed_at' => now(),
        ]);

        if ($order && $statusBefore !== null) {
            $this->logOrderHistory($order, 'return_ship_confirmed', $statusBefore, (string) $order->status, 'Ship xac nhan don tra hang #' . $orderReturn->id);
        }

        return back()->with('success', __('order_returns.messages.ship_confirmed'));
    }

    public function warehouseConfirm(OrderReturn $orderReturn)
    {
        $user = Auth::user();
        abort_unless($user && ($user->hasRole('warehouse') || $user->hasRole('admin')), 403);

        if ($user->hasRole('warehouse') && (!$user->warehouse_id || (int) $user->warehouse_id !== (int) $orderReturn->warehouse_id)) {
            return back()->with('error', __('order_returns.messages.not_allowed_warehouse_confirm'));
        }

        if (!in_array($orderReturn->status, ['ship_confirmed'], true)) {
            return back()->with('error', __('order_returns.messages.invalid_status_warehouse_confirm'));
        }

        $statusBefore = $orderReturn->order ? (string) $orderReturn->order->status : null;

        DB::transaction(function () use ($orderReturn, $user, $statusBefore) {
            $orderReturn->loadMissing(['order.items', 'returnItems.productVariant']);

            $refundAmount = 0;

            $orderItemPrices = $orderReturn->order->items
                ->groupBy('product_variant_id')
                ->map(function ($items) {
                    $totalQty = (int) $items->sum('quantity');
                    $totalAmount = (float) $items->sum(function ($item) {
                        return ((float) $item->price) * ((int) $item->quantity);
                    });

                    if ($totalQty <= 0) {
                        return 0;
                    }

                    return $totalAmount / $totalQty;
                });

            foreach ($orderReturn->returnItems as $returnItem) {
                $inventory = Inventory::firstOrCreate(
                    [
                        'product_variant_id' => $returnItem->product_variant_id,
                        'warehouse_id' => $orderReturn->warehouse_id,
                    ],
                    [
                        'quantity' => 0,
                        'reserved_quantity' => 0,
                        'low_stock_threshold' => 10,
                    ]
                );

                $inventory->quantity += (int) $returnItem->quantity;
                $inventory->save();

                InventoryMovement::create([
                    'inventory_id' => $inventory->id,
                    'quantity' => (int) $returnItem->quantity,
                    'type' => 'import',
                    'reference_id' => $orderReturn->id,
                    'reference_type' => OrderReturn::class,
                    'user_id' => $user->id,
                ]);

                $this->syncVariantStockFromInventories((int) $returnItem->product_variant_id);

                $unitPrice = (float) ($orderItemPrices[$returnItem->product_variant_id] ?? 0);
                $refundAmount += $unitPrice * (int) $returnItem->quantity;
            }

            $refundAmount = round($refundAmount, 2);

            Transaction::create([
                'order_id' => $orderReturn->order_id,
                'order_return_id' => $orderReturn->id,
                'customer_id' => $orderReturn->customer_id,
                'amount' => $refundAmount,
                'type' => 'refund',
                'method' => 'return_refund',
                'note' => 'Refund tu don tra hang #' . $orderReturn->id,
            ]);

            $totalOrderedQty = (int) $orderReturn->order->items->sum('quantity');
            $alreadyReceivedQty = (int) ReturnItem::query()
                ->whereHas('orderReturn', function ($q) use ($orderReturn) {
                    $q->where('order_id', $orderReturn->order_id)
                        ->whereIn('status', ['warehouse_received']);
                })
                ->sum('quantity');
            $currentReturnQty = (int) $orderReturn->returnItems->sum('quantity');
            $totalReturnedQtyAfterConfirm = $alreadyReceivedQty + $currentReturnQty;
            $isFullReturn = $totalOrderedQty > 0 && ($totalReturnedQtyAfterConfirm >= $totalOrderedQty);

            $orderReturn->update([
                'status' => 'warehouse_received',
                'warehouse_confirmed_by' => $user->id,
                'warehouse_confirmed_at' => now(),
                'refund_amount' => $refundAmount,
                'return_scope' => $isFullReturn ? 'full' : 'partial',
            ]);

            $order = $orderReturn->order;
            if ($order) {
                $netPaid = (float) $order->transactions()->where('type', 'payment')->sum('amount')
                    - (float) $order->transactions()->where('type', 'refund')->sum('amount');
                $order->amount_paid = $netPaid;
                $order->payment_status = $netPaid >= (float) $order->total ? 'paid' : ($netPaid > 0 ? 'partially_paid' : 'unpaid');
                if ($isFullReturn) {
                    $order->status = Order::STATUS_RETURNED;
                }
                $order->save();

                $this->logOrderHistory(
                    $order,
                    'return_warehouse_confirmed',
                    $statusBefore,
                    (string) $order->status,
                    'Kho xac nhan tra hang #' . $orderReturn->id . ', refund ' . number_format($refundAmount, 2, '.', '')
                );
            }
        });

        return back()->with('success', __('order_returns.messages.warehouse_confirmed'));
    }

    private function authorizeReturnCreation(?Order $order = null): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $isInternal = $user->hasRole('shipper') || $user->hasRole('ship') || $user->hasRole('sale') || $user->hasRole('admin');
        $isOwner = $order && ((int) $order->user_id === (int) $user->id);

        abort_unless($isInternal || $isOwner, 403);
    }

    private function syncVariantStockFromInventories(int $variantId): void
    {
        $totalStock = (int) Inventory::where('product_variant_id', $variantId)->sum('quantity');
        \App\Models\ProductVariant::where('id', $variantId)->update(['stock' => $totalStock]);
    }

    private function syncReceiptForReturn(OrderReturn $orderReturn, int $actorId): array
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
            ->where('notes', 'like', '%' . $marker . '%')
            ->latest('id')
            ->first();

        $result = 'unchanged';
        $adjustedItems = 0;

        if (!$document) {
            $document = InventoryDocument::create([
                'type' => 'import',
                'warehouse_id' => $orderReturn->warehouse_id,
                'document_date' => optional($orderReturn->warehouse_confirmed_at)->toDateString() ?: now()->toDateString(),
                'notes' => 'Dong bo tu don tra hang #' . $orderReturn->id . ' ' . $marker,
                'shipping_fee' => 0,
                'user_id' => $actorId,
            ]);
            $document->load('items');
            $result = 'created';
        }

        $currentItems = $document->items->keyBy('product_variant_id');

        foreach ($expectedItems as $variantId => $expectedQty) {
            $variantId = (int) $variantId;
            $expectedQty = (int) $expectedQty;
            $expectedUnitCost = (float) ($unitCostByVariant[$variantId] ?? 0);

            $item = $currentItems->get($variantId);
            if (!$item) {
                InventoryDocumentItem::create([
                    'inventory_document_id' => $document->id,
                    'product_variant_id' => $variantId,
                    'quantity' => $expectedQty,
                    'unit_cost' => $expectedUnitCost,
                ]);
                $adjustedItems++;
                if ($result === 'unchanged') {
                    $result = 'updated';
                }
                continue;
            }

            $currentQty = (int) $item->quantity;
            $currentUnitCost = (float) $item->unit_cost;
            if ($currentQty !== $expectedQty || abs($currentUnitCost - $expectedUnitCost) > 0.0001) {
                $item->update([
                    'quantity' => $expectedQty,
                    'unit_cost' => $expectedUnitCost,
                ]);
                $adjustedItems++;
                if ($result === 'unchanged') {
                    $result = 'updated';
                }
            }
        }

        $expectedVariantIds = $expectedItems->keys()->map(fn ($id) => (int) $id)->all();
        $extraItems = $document->items->filter(fn ($item) => !in_array((int) $item->product_variant_id, $expectedVariantIds, true));
        if ($extraItems->isNotEmpty()) {
            $adjustedItems += $extraItems->count();
            InventoryDocumentItem::whereIn('id', $extraItems->pluck('id')->all())->delete();
            if ($result === 'unchanged') {
                $result = 'updated';
            }
        }

        $desiredNotes = trim((string) $document->notes);
        if (strpos($desiredNotes, $marker) === false) {
            $desiredNotes = trim($desiredNotes . ' ' . $marker);
        }

        $targetDate = optional($orderReturn->warehouse_confirmed_at)->toDateString() ?: optional($document->document_date)->toDateString() ?: now()->toDateString();
        $docUpdates = [];
        if ((string) optional($document->document_date)->toDateString() !== (string) $targetDate) {
            $docUpdates['document_date'] = $targetDate;
        }
        if ((string) $document->notes !== (string) $desiredNotes) {
            $docUpdates['notes'] = $desiredNotes;
        }

        if (!empty($docUpdates)) {
            $document->update($docUpdates);
            if ($result === 'unchanged') {
                $result = 'updated';
            }
        }

        $document->refresh();

        return [$document, $result, $adjustedItems];
    }

    private function receiptMapForReturnIds(array $returnIds): array
    {
        $returnIds = array_values(array_unique(array_map('intval', $returnIds)));
        if (empty($returnIds)) {
            return [];
        }

        $documents = InventoryDocument::query()
            ->where('type', 'import')
            ->where(function ($query) use ($returnIds) {
                foreach ($returnIds as $returnId) {
                    $query->orWhere('notes', 'like', '%' . $this->returnReceiptMarker($returnId) . '%');
                }
            })
            ->latest('id')
            ->get();

        $mapped = [];
        foreach ($documents as $document) {
            if (!preg_match('/\[order_return:#(\d+)\]/', (string) $document->notes, $matches)) {
                continue;
            }

            $returnId = (int) ($matches[1] ?? 0);
            if ($returnId <= 0 || isset($mapped[$returnId])) {
                continue;
            }

            $mapped[$returnId] = $document;
        }

        return $mapped;
    }

    private function returnReceiptMarker(int $returnId): string
    {
        return self::RETURN_RECEIPT_NOTE_PREFIX . $returnId . ']';
    }
}
