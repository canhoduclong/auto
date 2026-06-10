<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Models\AccountingReconciliation;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\Order;
use App\Models\OrderAdjustment;
use App\Models\OrderReturn;
use App\Models\SupplierProductPrice;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WarehouseInventoryTransfer;
use App\Models\WarehouseTransfer;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RoleScreenApiController extends BaseApiController
{
    public function show(Request $request, string $layout, string $key): JsonResponse
    {
        $this->authorizeLayout($request, $layout);

        return match ($layout) {
            'warehouse' => $this->warehouse($request, $key),
            'manager_shipper' => $this->managerShipper($request, $key),
            'sale' => $this->sale($request, $key),
            'accounting' => $this->accounting($request, $key),
            'ceo' => $this->ceo($request, $key),
            default => $this->fail('Layout khong duoc ho tro.', 404),
        };
    }

    private function warehouse(Request $request, string $key): JsonResponse
    {
        $warehouseId = $request->user()->warehouse_id ? (int) $request->user()->warehouse_id : null;
        $date = (string) $request->query('date', now()->toDateString());

        return match ($key) {
            'orders' => $this->orders([
                'approved',
                Order::STATUS_READY_TO_PACK,
                Order::STATUS_PACKING,
                Order::STATUS_READY_TO_SHIP,
            ], $warehouseId, $date),
            'supplier_prices' => $this->supplierPrices(),
            'incoming_transfers' => $this->incomingOrderTransfers($warehouseId),
            'incoming_inventory_transfers' => $this->incomingInventoryTransfers($warehouseId),
            'stock_in_create' => $this->documents('import', $warehouseId),
            'order_transfers' => $this->orderTransfers($warehouseId),
            'inventory_transfers' => $this->inventoryTransfers($warehouseId),
            'stock_out' => $this->documents('export', $warehouseId),
            'stock_out_orders' => $this->orders([Order::STATUS_READY_TO_SHIP, Order::STATUS_DELIVERING, Order::STATUS_DELIVERED, Order::STATUS_COMPLETED], $warehouseId, null),
            'returns' => $this->warehouseReturns($warehouseId),
            default => $this->fail('Man hinh kho khong duoc ho tro.', 404),
        };
    }

    private function managerShipper(Request $request, string $key): JsonResponse
    {
        $today = now()->toDateString();

        return match ($key) {
            'manage_assignments' => $this->managerAssignments($today),
            'shipper_team', 'team_report' => $this->ok([
                'cards' => [
                    ['label' => 'Tổng shipper', 'value' => User::query()->whereHas('roles', fn ($q) => $q->whereIn('name', ['shipper', 'manager_shipper']))->count()],
                    ['label' => 'Đang có đơn', 'value' => Order::query()->whereNotNull('shipper_id')->whereIn('status', [Order::STATUS_DELIVERING, 'shipping', Order::STATUS_READY_TO_SHIP])->distinct('shipper_id')->count('shipper_id')],
                    ['label' => 'Đã giao hôm nay', 'value' => Order::query()->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])->whereDate('updated_at', $today)->count()],
                    ['label' => 'Lịch trình hôm nay', 'value' => Order::query()->whereNotNull('shipper_id')->whereDate('created_at', $today)->count()],
                ],
                'items' => User::query()
                    ->whereHas('roles', fn ($q) => $q->whereIn('name', ['shipper', 'manager_shipper']))
                    ->orderBy('name')
                    ->limit(30)
                    ->get()
                    ->map(fn (User $shipper) => [
                        'id' => (int) $shipper->id,
                        'title' => (string) $shipper->name,
                        'subtitle' => (string) ($shipper->phone ?? $shipper->email ?? ''),
                        'status' => 'shipper',
                        'updated_at' => optional($shipper->updated_at)->toIso8601String(),
                    ])
                    ->values(),
            ]),
            'manage_fees', 'shipping_fee_report' => $this->ok([
                'cards' => [
                    ['label' => 'Tổng phí ship', 'value' => (float) Order::query()->sum('shipping_fee')],
                    ['label' => 'Phí ship hôm nay', 'value' => (float) Order::query()->whereDate('created_at', $today)->sum('shipping_fee')],
                    ['label' => 'Đơn có phí ship', 'value' => Order::query()->where('shipping_fee', '>', 0)->count()],
                    ['label' => 'Đơn chưa có phí', 'value' => Order::query()->where(function ($q) {
                        $q->whereNull('shipping_fee')->orWhere('shipping_fee', 0);
                    })->count()],
                ],
                'items' => $this->latestOrders()->take(20)->values(),
            ]),
            'route_planning' => $this->ok([
                'cards' => [
                    ['label' => 'Đơn cần lập lịch', 'value' => Order::query()->whereIn('status', [Order::STATUS_READY_TO_SHIP, 'packed'])->whereDate('updated_at', $today)->count()],
                    ['label' => 'Shipper có lịch', 'value' => Order::query()->whereNotNull('shipper_id')->whereDate('updated_at', $today)->distinct('shipper_id')->count('shipper_id')],
                    ['label' => 'Đang giao', 'value' => Order::query()->whereIn('status', [Order::STATUS_DELIVERING, 'shipping'])->count()],
                    ['label' => 'Hoàn thành hôm nay', 'value' => Order::query()->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])->whereDate('updated_at', $today)->count()],
                ],
                'items' => $this->latestOrders()->take(20)->values(),
            ]),
            default => $this->fail('Man hinh Shipper Manager khong duoc ho tro.', 404),
        };
    }

    private function sale(Request $request, string $key): JsonResponse
    {
        if ($key !== 'my_orders') {
            return $this->fail('Man hinh sale khong duoc ho tro.', 404);
        }

        return $this->paginated(
            Order::query()
                ->with(['customer:id,name,phone,address', 'items.product:id,name,unit', 'items.variant:id,name,sku,size,product_id'])
                ->where('user_id', (int) $request->user()->id)
                ->latest('updated_at')
                ->paginate(20)
        );
    }

    private function accounting(Request $request, string $key): JsonResponse
    {
        if ($key !== 'dashboard') {
            return $this->fail('Man hinh accounting khong duoc ho tro.', 404);
        }

        $today = now()->toDateString();
        $transactions = Transaction::query();

        return $this->ok([
            'cards' => [
                ['label' => 'Don hang', 'value' => Order::query()->count()],
                ['label' => 'Don chua doi soat', 'value' => Schema::hasTable('accounting_reconciliations') ? AccountingReconciliation::query()->where('status', AccountingReconciliation::STATUS_PENDING)->count() : 0],
                ['label' => 'Thu chi hom nay', 'value' => (float) (clone $transactions)->whereDate('created_at', $today)->sum('amount')],
                ['label' => 'Cong no khach hang', 'value' => (float) Order::query()->sum('amount_due')],
            ],
            'items' => $this->latestOrders()->take(20)->values(),
        ]);
    }

    private function ceo(Request $request, string $key): JsonResponse
    {
        if ($key === 'daily_sales') {
            return $this->ceoDailySales($request);
        }
        if ($key === 'cashflow') {
            return $this->ceoCashflow($request);
        }
        if ($key === 'financial_reports') {
            return $this->ceoFinancialReports($request);
        }
        if ($key === 'shipper_costs') {
            return $this->ceoShipperCosts($request);
        }
        if ($key === 'customers_list') {
            return $this->ceoCustomersList($request);
        }
        if ($key === 'debts') {
            return $this->ceoDebts($request);
        }
        if ($key === 'inventory') {
            return $this->ceoInventory();
        }
        if ($key !== 'dashboard') {
            return $this->fail('Man hinh CEO khong duoc ho tro.', 404);
        }

        $today = now()->toDateString();
        $todayOrders = Order::query()
            ->with([
                'customer:id,name,phone,address',
                'user:id,name,team_id',
                'user.team:id,name',
                'items.product:id,name,unit',
                'items.variant:id,name,sku,size,product_id',
            ])
            ->whereDate('created_at', $today)
            ->whereNotIn('status', [Order::STATUS_CANCELLED, Order::STATUS_REJECTED])
            ->orderByRaw('CASE WHEN daily_sequence IS NULL THEN 1 ELSE 0 END')
            ->orderBy('daily_sequence')
            ->orderBy('created_at')
            ->get();
        $revenue = Schema::hasTable('accounting_reconciliations')
            ? (float) AccountingReconciliation::query()->where('status', AccountingReconciliation::STATUS_CONFIRMED)->sum('recognized_revenue')
            : (float) Order::query()->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])->sum('total');

        return $this->ok([
            'cards' => [
                ['label' => 'Doanh thu', 'value' => $revenue],
                ['label' => 'Don hom nay', 'value' => Order::query()->whereDate('created_at', $today)->count()],
                ['label' => 'Dang dong goi', 'value' => Order::query()->where('status', Order::STATUS_PACKING)->count()],
                ['label' => 'Dang giao', 'value' => Order::query()->where('status', Order::STATUS_DELIVERING)->count()],
            ],
            'items' => $todayOrders->values(),
        ]);
    }

    private function ceoDailySales(Request $request): JsonResponse
    {
        [$from, $to] = $this->mobileDateRange($request, true);
        $approvedAdjustmentItems = DB::table('order_adjustment_items as adjustment_item_source')
            ->join('order_adjustments as adjustment_source', function ($join) {
                $join->on('adjustment_source.id', '=', 'adjustment_item_source.order_adjustment_id')
                    ->where('adjustment_source.status', OrderAdjustment::STATUS_APPROVED);
            })
            ->selectRaw('adjustment_item_source.order_item_id, MAX(adjustment_item_source.id) as adjustment_item_id')
            ->groupBy('adjustment_item_source.order_item_id');

        $base = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->leftJoin('customers', 'customers.id', '=', 'orders.customer_id')
            ->leftJoin('users', 'users.id', '=', 'orders.user_id')
            ->leftJoinSub($approvedAdjustmentItems, 'latest_adjustment', 'latest_adjustment.order_item_id', '=', 'order_items.id')
            ->leftJoin('order_adjustment_items as adjustment', 'adjustment.id', '=', 'latest_adjustment.adjustment_item_id')
            ->whereNotIn('orders.status', [Order::STATUS_REJECTED, Order::STATUS_CANCELLED])
            ->whereBetween('orders.created_at', [$from, $to]);

        $effectiveTotal = "CASE WHEN adjustment.id IS NOT NULL THEN
            CASE WHEN order_items.is_priced_by_kg = 1
                THEN COALESCE(adjustment.adjusted_weight, order_items.total_weight) * COALESCE(adjustment.adjusted_price, order_items.price)
                ELSE COALESCE(adjustment.adjusted_quantity, order_items.quantity) * COALESCE(adjustment.adjusted_price, order_items.price)
            END
            ELSE order_items.total
        END";

        $summary = (clone $base)->selectRaw('COUNT(DISTINCT orders.id) as order_count')
            ->selectRaw('COUNT(order_items.id) as item_count')
            ->selectRaw('COALESCE(SUM(COALESCE(adjustment.adjusted_quantity, order_items.quantity)), 0) as total_qty')
            ->selectRaw('COALESCE(SUM(COALESCE(adjustment.adjusted_weight, order_items.total_weight)), 0) as total_weight')
            ->selectRaw("COALESCE(SUM({$effectiveTotal}), 0) as total_amount")
            ->first();

        $items = (clone $base)
            ->select([
                'order_items.id',
                'orders.code as order_code',
                'orders.created_at',
                'orders.status',
                'products.name as product_name',
                'product_variants.name as variant_name',
                'product_variants.size',
                'customers.name as customer_name',
                'users.name as sale_name',
                DB::raw('COALESCE(adjustment.adjusted_quantity, order_items.quantity) as effective_quantity'),
                DB::raw('COALESCE(adjustment.adjusted_weight, order_items.total_weight) as effective_weight'),
                DB::raw('COALESCE(adjustment.adjusted_price, order_items.price) as effective_price'),
                DB::raw("{$effectiveTotal} as effective_total"),
            ])
            ->orderByDesc('orders.created_at')
            ->limit(100)
            ->get()
            ->map(fn ($item) => [
                'id' => (int) $item->id,
                'title' => (string) $item->product_name,
                'subtitle' => trim(($item->customer_name ?: 'Khách hàng') . ' • ' . ($item->sale_name ?: 'Sale')),
                'status' => (string) $item->order_code,
                'amount' => (float) $item->effective_total,
                'created_at' => $item->created_at,
                'quantity' => (float) $item->effective_quantity,
                'weight' => (float) $item->effective_weight,
                'unit_price' => (float) $item->effective_price,
                'variant' => (string) ($item->variant_name ?: ''),
                'size' => (string) ($item->size ?: ''),
            ])
            ->values();

        return $this->ok([
            'cards' => [
                ['label' => 'Tổng doanh số', 'value' => (float) ($summary->total_amount ?? 0)],
                ['label' => 'Đơn hàng', 'value' => (int) ($summary->order_count ?? 0)],
                ['label' => 'Mặt hàng', 'value' => (int) ($summary->item_count ?? 0)],
                ['label' => 'Tổng số lượng', 'value' => (float) ($summary->total_qty ?? 0)],
            ],
            'items' => $items,
        ]);
    }

    private function ceoCashflow(Request $request): JsonResponse
    {
        [$from, $to] = $this->mobileDateRange($request);
        $base = Transaction::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('status', Transaction::STATUS_APPROVED);
        $incomeTypes = ['payment', 'extra_income'];
        $expenseTypes = ['refund', 'fee', 'expense', 'extra_expense'];
        $income = (float) (clone $base)->whereIn('type', $incomeTypes)->sum('amount');
        $expense = (float) (clone $base)->whereIn('type', $expenseTypes)->sum('amount');

        $items = (clone $base)
            ->with(['transactionCategory:id,name,flow_direction', 'account:id,name,type', 'customer:id,name', 'order:id,code'])
            ->latest('created_at')
            ->limit(100)
            ->get()
            ->map(function (Transaction $transaction) use ($incomeTypes) {
                $isIncome = in_array($transaction->type, $incomeTypes, true);
                return [
                    'id' => (int) $transaction->id,
                    'title' => (string) ($transaction->transactionCategory?->name ?? ($isIncome ? 'Khoản thu' : 'Khoản chi')),
                    'subtitle' => (string) ($transaction->account?->name ?? 'Chưa xác định tài khoản'),
                    'status' => $isIncome ? 'Thu' : 'Chi',
                    'amount' => (float) $transaction->amount,
                    'created_at' => optional($transaction->created_at)->toIso8601String(),
                    'customer' => $transaction->customer?->name,
                    'order_code' => $transaction->order?->code,
                    'method' => $transaction->method,
                    'note' => $transaction->note,
                ];
            })
            ->values();

        return $this->ok([
            'cards' => [
                ['label' => 'Tổng thu', 'value' => $income],
                ['label' => 'Tổng chi', 'value' => $expense],
                ['label' => 'Dòng tiền ròng', 'value' => $income - $expense],
                ['label' => 'Giao dịch', 'value' => (clone $base)->count()],
            ],
            'items' => $items,
        ]);
    }

    private function ceoFinancialReports(Request $request): JsonResponse
    {
        [$from, $to] = $this->mobileDateRange($request);
        $revenue = (float) Order::query()->whereBetween('created_at', [$from, $to])->sum('total');
        $approved = Transaction::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('status', Transaction::STATUS_APPROVED);
        $received = (float) (clone $approved)->where('type', 'payment')->sum('amount');
        $cost = (float) (clone $approved)->whereIn('type', ['refund', 'expense'])->sum('amount');

        $items = (clone $approved)
            ->with(['transactionCategory:id,name,flow_direction'])
            ->whereNotNull('transaction_category_id')
            ->get()
            ->groupBy('transaction_category_id')
            ->map(function ($transactions) {
                $category = $transactions->first()?->transactionCategory;
                return [
                    'id' => (int) ($category?->id ?? 0),
                    'title' => (string) ($category?->name ?? 'Chưa phân loại'),
                    'subtitle' => $category?->flow_direction === 'in' ? 'Nhóm thu' : 'Nhóm chi',
                    'status' => (string) ($category?->flow_direction ?? ''),
                    'amount' => (float) $transactions->sum('amount'),
                    'transaction_count' => $transactions->count(),
                ];
            })
            ->sortByDesc('amount')
            ->values();

        return $this->ok([
            'cards' => [
                ['label' => 'Doanh thu đơn hàng', 'value' => $revenue],
                ['label' => 'Thực thu', 'value' => $received],
                ['label' => 'Chi phí', 'value' => $cost],
                ['label' => 'Lợi nhuận', 'value' => $received - $cost],
            ],
            'items' => $items,
        ]);
    }

    private function ceoShipperCosts(Request $request): JsonResponse
    {
        [$from, $to] = $this->mobileDateRange($request);
        $orders = Order::query()
            ->with('shipper:id,name')
            ->whereNotNull('shipper_id')
            ->whereBetween('updated_at', [$from, $to])
            ->whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
            ->get();
        $returns = OrderReturn::query()
            ->with('order.shipper:id,name')
            ->whereBetween('updated_at', [$from, $to])
            ->get();

        $deliveryFee = (float) $orders->sum(fn (Order $order) => ($order->charge_shipping_fee ?? true) ? (float) ($order->shipping_fee ?? 0) : 0);
        $returnFee = (float) $returns->sum('return_shipping_fee');
        $shipperIds = $orders->pluck('shipper_id')->merge($returns->pluck('order.shipper_id'))->filter()->unique();

        $items = $shipperIds->map(function ($shipperId) use ($orders, $returns) {
            $shipperOrders = $orders->where('shipper_id', $shipperId);
            $shipperReturns = $returns->filter(fn (OrderReturn $return) => (int) $return->order?->shipper_id === (int) $shipperId);
            $delivery = (float) $shipperOrders->sum(fn (Order $order) => ($order->charge_shipping_fee ?? true) ? (float) ($order->shipping_fee ?? 0) : 0);
            $returned = (float) $shipperReturns->sum('return_shipping_fee');

            return [
                'id' => (int) $shipperId,
                'title' => (string) ($shipperOrders->first()?->shipper?->name ?? $shipperReturns->first()?->order?->shipper?->name ?? 'Shipper'),
                'subtitle' => $shipperOrders->count() . ' đơn giao • ' . $shipperReturns->count() . ' đơn hoàn',
                'status' => 'Tổng chi phí',
                'amount' => $delivery + $returned,
                'delivery_fee' => $delivery,
                'return_fee' => $returned,
            ];
        })->sortByDesc('amount')->values();

        return $this->ok([
            'cards' => [
                ['label' => 'Phí giao hàng', 'value' => $deliveryFee],
                ['label' => 'Phí hoàn trả', 'value' => $returnFee],
                ['label' => 'Tổng chi phí Shipper', 'value' => $deliveryFee + $returnFee],
                ['label' => 'Shipper phát sinh', 'value' => $shipperIds->count()],
            ],
            'items' => $items,
        ]);
    }

    private function ceoCustomersList(Request $request): JsonResponse
    {
        $sort = (string) $request->query('sort', 'newest');
        $query = Customer::query()
            ->with(['assignedTo:id,name', 'lastOrder'])
            ->withCount('orders')
            ->withSum('orders as total_sales', 'total')
            ->withSum('orders as total_debt', 'amount_due')
            ->where('is_employee', false);

        match ($sort) {
            'debt_desc' => $query->orderByDesc('total_debt'),
            'sales_desc' => $query->orderByDesc('total_sales'),
            'name_asc' => $query->orderBy('name'),
            default => $query->latest('created_at'),
        };

        $customers = $query->limit(100)->get();

        return $this->ok([
            'cards' => [
                ['label' => 'Khách hàng', 'value' => Customer::query()->where('is_employee', false)->count()],
                ['label' => 'Khách mới tháng này', 'value' => Customer::query()->where('is_employee', false)->where('created_at', '>=', now()->startOfMonth())->count()],
                ['label' => 'Tổng công nợ', 'value' => (float) Order::query()->sum('amount_due')],
                ['label' => 'Khách có công nợ', 'value' => Order::query()->where('amount_due', '>', 0)->distinct('customer_id')->count('customer_id')],
            ],
            'items' => $customers->map(fn (Customer $customer) => [
                'id' => (int) $customer->id,
                'title' => (string) $customer->name,
                'subtitle' => trim(($customer->phone ?: 'Chưa có SĐT') . ' • ' . ($customer->assignedTo?->name ?: 'Chưa gán Sale')),
                'status' => 'Công nợ: ' . number_format((float) ($customer->total_debt ?? 0)) . 'đ',
                'amount' => (float) ($customer->total_sales ?? 0),
                'created_at' => optional($customer->created_at)->toIso8601String(),
                'orders_count' => (int) ($customer->orders_count ?? 0),
                'total_debt' => (float) ($customer->total_debt ?? 0),
                'last_order_at' => optional($customer->lastOrder?->created_at)->toIso8601String(),
            ])->values(),
        ]);
    }

    private function ceoDebts(Request $request): JsonResponse
    {
        [$from, $to] = $this->mobileDateRange($request);
        $rows = Order::query()
            ->select('customer_id')
            ->selectRaw('COUNT(*) as order_count, SUM(amount_due) as debt_total')
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('customer_id')
            ->where('amount_due', '>', 0)
            ->groupBy('customer_id')
            ->with('customer:id,name,phone')
            ->orderByDesc('debt_total')
            ->limit(100)
            ->get();

        return $this->ok([
            'cards' => [
                ['label' => 'Tổng công nợ', 'value' => (float) $rows->sum('debt_total')],
                ['label' => 'Khách có nợ', 'value' => $rows->count()],
                ['label' => 'Đơn còn nợ', 'value' => (int) $rows->sum('order_count')],
            ],
            'items' => $rows->map(fn ($row) => [
                'id' => (int) $row->customer_id,
                'title' => (string) ($row->customer?->name ?? 'Khách hàng'),
                'subtitle' => (string) ($row->customer?->phone ?? ''),
                'status' => $row->order_count . ' đơn còn nợ',
                'amount' => (float) $row->debt_total,
            ])->values(),
        ]);
    }

    private function ceoInventory(): JsonResponse
    {
        $rows = Inventory::query()
            ->with(['warehouse:id,name', 'productVariant:id,name,sku,product_id', 'productVariant.product:id,name'])
            ->orderByRaw('(quantity - reserved_quantity) ASC')
            ->limit(100)
            ->get();
        $lowStock = $rows->filter(fn (Inventory $inventory) => $inventory->quantity > 0 && $inventory->quantity <= ($inventory->low_stock_threshold ?: 5))->count();

        return $this->ok([
            'cards' => [
                ['label' => 'Tổng tồn kho', 'value' => (int) Inventory::query()->sum('quantity')],
                ['label' => 'Tổng giữ chỗ', 'value' => (int) Inventory::query()->sum('reserved_quantity')],
                ['label' => 'SKU sắp hết', 'value' => $lowStock],
                ['label' => 'SKU hết hàng', 'value' => Inventory::query()->where('quantity', '<=', 0)->count()],
            ],
            'items' => $rows->map(fn (Inventory $inventory) => [
                'id' => (int) $inventory->id,
                'title' => (string) ($inventory->productVariant?->name ?? $inventory->productVariant?->product?->name ?? 'Sản phẩm'),
                'subtitle' => trim(($inventory->warehouse?->name ?: 'Kho') . ' • ' . ($inventory->productVariant?->sku ?: 'Không SKU')),
                'status' => 'Khả dụng: ' . $inventory->available,
                'amount' => 0,
                'quantity' => (int) $inventory->quantity,
                'reserved' => (int) $inventory->reserved_quantity,
                'available' => $inventory->available,
            ])->values(),
        ]);
    }

    private function mobileDateRange(Request $request, bool $defaultToday = false): array
    {
        $defaultFrom = $defaultToday ? now()->toDateString() : now()->startOfMonth()->toDateString();
        $from = Carbon::parse((string) $request->query('from_date', $defaultFrom))->startOfDay();
        $to = Carbon::parse((string) $request->query('to_date', now()->toDateString()))->endOfDay();

        return $from->lte($to) ? [$from, $to] : [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
    }

    private function orders(array $statuses, ?int $warehouseId, ?string $date): JsonResponse
    {
        $query = Order::query()
            ->with(['customer:id,name,phone,address', 'items.product:id,name,unit', 'items.variant:id,name,sku,size,product_id'])
            ->whereIn('status', $statuses)
            ->when($warehouseId, fn ($q) => $q->where(function ($scope) use ($warehouseId) {
                $scope->where('warehouse_id', $warehouseId)->orWhereNull('warehouse_id');
            }))
            ->when($date, fn ($q) => $q->whereDate('created_at', $date))
            ->latest('updated_at');

        return $this->paginated($query->paginate(20));
    }

    private function supplierPrices(): JsonResponse
    {
        $items = SupplierProductPrice::query()
            ->with(['supplier:id,name', 'product:id,name'])
            ->latest('created_at')
            ->paginate(20)
            ->through(fn (SupplierProductPrice $price) => [
                'id' => (int) $price->id,
                'title' => (string) ($price->product?->name ?? 'San pham'),
                'subtitle' => (string) ($price->supplier?->name ?? 'Nha cung cap'),
                'status' => optional($price->created_at)->format('d/m/Y H:i'),
                'amount' => (float) ($price->purchase_price ?? $price->min_price ?? 0),
            ]);

        return $this->paginated($items);
    }

    private function incomingOrderTransfers(?int $warehouseId): JsonResponse
    {
        $query = WarehouseTransfer::query()
            ->with(['order.customer', 'sourceWarehouse', 'targetWarehouse', 'shipper'])
            ->when($warehouseId, fn ($q) => $q->where('target_warehouse_id', $warehouseId))
            ->where('status', WarehouseTransfer::STATUS_DELIVERED_WAITING_RECEIVE)
            ->latest('delivered_at');

        return $this->paginated($query->paginate(20));
    }

    private function orderTransfers(?int $warehouseId): JsonResponse
    {
        $query = WarehouseTransfer::query()
            ->with(['order.customer', 'sourceWarehouse', 'targetWarehouse', 'shipper'])
            ->when($warehouseId, fn ($q) => $q->where(function ($scope) use ($warehouseId) {
                $scope->where('source_warehouse_id', $warehouseId)->orWhere('target_warehouse_id', $warehouseId);
            }))
            ->latest('updated_at');

        return $this->paginated($query->paginate(20));
    }

    private function incomingInventoryTransfers(?int $warehouseId): JsonResponse
    {
        $query = WarehouseInventoryTransfer::query()
            ->with(['sourceWarehouse', 'targetWarehouse', 'requester', 'items.productVariant.product'])
            ->when($warehouseId, fn ($q) => $q->where('target_warehouse_id', $warehouseId))
            ->where('status', WarehouseInventoryTransfer::STATUS_PENDING_RECEIVE)
            ->latest('requested_at');

        return $this->paginated($query->paginate(20));
    }

    private function inventoryTransfers(?int $warehouseId): JsonResponse
    {
        $query = WarehouseInventoryTransfer::query()
            ->with(['sourceWarehouse', 'targetWarehouse', 'requester', 'items.productVariant.product'])
            ->when($warehouseId, fn ($q) => $q->where(function ($scope) use ($warehouseId) {
                $scope->where('source_warehouse_id', $warehouseId)->orWhere('target_warehouse_id', $warehouseId);
            }))
            ->latest('updated_at');

        return $this->paginated($query->paginate(20));
    }

    private function documents(string $type, ?int $warehouseId): JsonResponse
    {
        $query = InventoryDocument::query()
            ->with(['warehouse:id,name', 'supplier:id,name', 'user:id,name', 'items'])
            ->where('type', $type)
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->latest('document_date');

        return $this->paginated($query->paginate(20));
    }

    private function managerAssignments(string $date): JsonResponse
    {
        $statuses = [Order::STATUS_APPROVED, Order::STATUS_READY_TO_PACK, Order::STATUS_PACKING, Order::STATUS_READY_TO_SHIP];
        $shippers = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['shipper', 'manager_shipper']))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $shipper) => ['id' => (int) $shipper->id, 'name' => (string) $shipper->name])
            ->values();
        $orders = Order::query()
            ->with(['customer:id,name,phone,address', 'shipper:id,name'])
            ->whereIn('status', $statuses)
            ->where(function ($query) use ($date) {
                $query->whereDate('created_at', $date)->orWhereDate('updated_at', $date);
            })
            ->orderByRaw('CASE WHEN shipper_id IS NULL THEN 0 ELSE 1 END')
            ->orderByRaw('CASE WHEN daily_sequence IS NULL THEN 1 ELSE 0 END')
            ->orderBy('daily_sequence')
            ->orderBy('created_at')
            ->get();

        return $this->ok([
            'cards' => [
                ['label' => 'Tổng đơn điều phối', 'value' => $orders->count()],
                ['label' => 'Chưa phân công', 'value' => $orders->whereNull('shipper_id')->count()],
                ['label' => 'Đã phân công', 'value' => $orders->whereNotNull('shipper_id')->count()],
                ['label' => 'Shipper sẵn sàng', 'value' => $shippers->count()],
            ],
            'items' => $orders->map(fn (Order $order) => [
                'id' => (int) $order->id,
                'title' => 'Đơn #' . ($order->daily_sequence ?: $order->code ?: $order->id),
                'subtitle' => (string) ($order->customer?->name ?? 'Khách hàng'),
                'status' => (string) $order->status,
                'total' => (float) ($order->total ?? 0),
                'shipper_id' => $order->shipper_id ? (int) $order->shipper_id : null,
                'shipper_name' => (string) ($order->shipper?->name ?? ''),
                'delivery_time' => (string) ($order->delivery_time ?? ''),
                'available_shippers' => $shippers,
                'updated_at' => optional($order->updated_at)->toIso8601String(),
            ])->values(),
        ]);
    }

    private function warehouseReturns(?int $warehouseId): JsonResponse
    {
        $query = OrderReturn::query()
            ->with(['order:id,code,status,total', 'customer:id,name,phone', 'warehouse:id,name', 'returnItems.productVariant:id,name'])
            ->latest('updated_at');
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $this->ok([
            'cards' => [
                ['label' => 'Chờ tiếp nhận', 'value' => (clone $query)->whereIn('status', ['pending_warehouse', 'requested', 'ship_confirmed'])->count()],
                ['label' => 'Đã nhập kho', 'value' => (clone $query)->where('status', 'warehouse_received')->count()],
            ],
            'items' => $query->limit(50)->get()->map(fn (OrderReturn $return) => [
                'id' => (int) $return->id,
                'title' => 'Phiếu trả #' . $return->id . ' - Đơn ' . ($return->order?->code ?? $return->order_id),
                'subtitle' => (string) ($return->customer?->name ?? 'Khách hàng'),
                'status' => (string) $return->status,
                'reason' => (string) ($return->reason ?? ''),
                'note' => (string) ($return->note ?? ''),
                'warehouse' => $return->warehouse ? ['id' => (int) $return->warehouse->id, 'name' => (string) $return->warehouse->name] : null,
                'items' => $return->returnItems->map(fn ($item) => [
                    'name' => (string) ($item->productVariant?->name ?? 'Sản phẩm'),
                    'quantity' => (int) $item->quantity,
                ])->values(),
                'updated_at' => optional($return->updated_at)->toIso8601String(),
            ])->values(),
        ]);
    }

    private function latestOrders()
    {
        return Order::query()
            ->with('customer:id,name,phone,address')
            ->latest('updated_at')
            ->limit(20)
            ->get()
            ->map(fn (Order $order) => [
                'id' => (int) $order->id,
                'title' => (string) ($order->code ?: '#' . $order->id),
                'subtitle' => (string) ($order->customer?->name ?? 'Khach hang'),
                'status' => (string) $order->status,
                'amount' => (float) ($order->total ?? 0),
                'updated_at' => optional($order->updated_at)->toIso8601String(),
            ]);
    }

    private function authorizeLayout(Request $request, string $layout): void
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        $allowed = match ($layout) {
            'warehouse' => $user->hasRole('warehouse') || $user->hasRole('admin'),
            'sale' => $user->hasRole('sale') || $user->hasRole('leader') || $user->hasRole('leader_sale') || $user->hasRole('sale_manager') || $user->hasRole('manager') || $user->hasRole('manager_sale') || $user->hasRole('admin'),
            'accounting' => $user->hasRole('accounting') || $user->hasRole('accountant') || $user->hasRole('admin'),
            'ceo' => $user->hasRole('ceo') || $user->hasRole('admin'),
            'manager_shipper' => $user->hasRole('manager_shipper') || $user->hasRole('admin'),
            default => false,
        };

        if (!$allowed) {
            abort(403, 'Role khong duoc phep truy cap man hinh nay');
        }
    }
}
