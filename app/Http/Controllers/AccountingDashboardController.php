<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryDocumentItem;
use App\Models\Order;
use App\Models\OrderAdjustment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountingDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:accountant,accounting,admin']);
    }

    public function index(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveDateRange($request);

        $receivableTotal = Schema::hasColumn('orders', 'amount_due')
            ? (float) Order::query()->where('amount_due', '>', 0)->sum('amount_due')
            : 0.0;

        $payableTotal = Schema::hasTable('accounting_supplier_payables')
            ? (float) DB::table('accounting_supplier_payables')->whereIn('status', ['pending', 'partial', 'overdue'])->sum('amount')
            : 0.0;

        $todayIncome = (float) Transaction::query()
            ->whereDate('created_at', now()->toDateString())
            ->where('type', 'payment')
            ->sum('amount');

        $todayExpense = (float) Transaction::query()
            ->whereDate('created_at', now()->toDateString())
            ->whereIn('type', ['refund', 'expense'])
            ->sum('amount');

        $unpaidOrders = Schema::hasColumn('orders', 'amount_due')
            ? (int) Order::query()->where('amount_due', '>', 0)->count()
            : (int) Order::query()->where('payment_status', '!=', 'paid')->count();

        $overdueOrders = Schema::hasColumn('orders', 'amount_due')
            ? (int) Order::query()->where('amount_due', '>', 0)->whereDate('created_at', '<', now()->subDays(7)->toDateString())->count()
            : 0;

        return view('accounting.dashboard', [
            'user' => $request->user(),
            'from' => $from,
            'to' => $to,
            'rangeLabel' => $rangeLabel,
            'cards' => [
                'receivable_total' => $receivableTotal,
                'payable_total' => $payableTotal,
                'today_income' => $todayIncome,
                'today_expense' => $todayExpense,
                'unpaid_orders' => $unpaidOrders,
                'overdue_orders' => $overdueOrders,
            ],
        ]);
    }

    public function customerDebts(Request $request)
    {
        $query = Customer::query()->with('assignedTo:id,name');

        if ($keyword = trim((string) $request->input('keyword', ''))) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        $customers = $query->paginate(20)->appends($request->query());

        $rows = $customers->getCollection()->map(function (Customer $customer) {
            $debt = Schema::hasColumn('orders', 'amount_due')
                ? (float) Order::query()
                    ->where('customer_id', $customer->id)
                    ->where('amount_due', '>', 0)
                    ->sum('amount_due')
                : 0.0;

            $lastPaymentAt = Transaction::query()
                ->where('customer_id', $customer->id)
                ->where('type', 'payment')
                ->max('created_at');

            $dueDate = Order::query()
                ->where('customer_id', $customer->id)
                ->whereDate('created_at', '>=', now()->subMonths(6)->toDateString())
                ->min('created_at');

            return [
                'customer' => $customer,
                'debt' => $debt,
                'due_date' => $dueDate ? Carbon::parse($dueDate)->addDays(7) : null,
                'status' => $debt <= 0 ? 'Da thanh toan' : 'Con no',
                'payment_history' => $lastPaymentAt ? ('Lan gan nhat: ' . Carbon::parse($lastPaymentAt)->format('d/m/Y H:i')) : 'Chua co thanh toan',
            ];
        })->filter(fn ($row) => $row['debt'] > 0)->values();

        return view('accounting.customer_debts', [
            'customers' => $customers,
            'rows' => $rows,
            'keyword' => (string) $request->input('keyword', ''),
            'totalDebt' => $rows->sum('debt'),
        ]);
    }

    public function supplierDebts(Request $request)
    {
        $keyword = trim((string) $request->input('keyword', ''));

        $companies = Company::query()
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%");
            })
            ->orderBy('name')
            ->paginate(20)
            ->appends($request->query());

        $supplierPayables = collect();
        if (Schema::hasTable('accounting_supplier_payables')) {
            $supplierPayables = DB::table('accounting_supplier_payables')
                ->whereIn('company_id', $companies->pluck('id'))
                ->selectRaw('company_id, SUM(amount) as amount_due, MAX(due_date) as due_date, MAX(status) as status')
                ->groupBy('company_id')
                ->get()
                ->keyBy('company_id');
        }

        return view('accounting.supplier_debts', [
            'companies' => $companies,
            'supplierPayables' => $supplierPayables,
            'keyword' => $keyword,
        ]);
    }

    public function cashflow(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveDateRange($request);
        $type = (string) $request->input('type', '');

        $transactions = Transaction::query()
            ->with(['customer:id,name', 'order:id,code'])
            ->whereBetween('created_at', [$from, $to])
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->latest('created_at')
            ->paginate(25)
            ->appends($request->query());

        return view('accounting.cashflow', [
            'transactions' => $transactions,
            'from' => $from,
            'to' => $to,
            'rangeLabel' => $rangeLabel,
            'type' => $type,
            'incomeTotal' => (float) Transaction::query()->whereBetween('created_at', [$from, $to])->where('type', 'payment')->sum('amount'),
            'expenseTotal' => (float) Transaction::query()->whereBetween('created_at', [$from, $to])->whereIn('type', ['refund', 'expense'])->sum('amount'),
        ]);
    }

    public function reconciliation(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveDateRange($request);

        $orders = Order::query()
            ->with(['customer:id,name', 'warehouse:id,name'])
            ->whereBetween('created_at', [$from, $to])
            ->latest('created_at')
            ->paginate(25)
            ->appends($request->query());

        $stats = [
            'paid' => Order::query()->whereBetween('created_at', [$from, $to])->where('payment_status', 'paid')->count(),
            'unpaid' => Schema::hasColumn('orders', 'amount_due')
                ? Order::query()->whereBetween('created_at', [$from, $to])->where('amount_due', '>', 0)->count()
                : Order::query()->whereBetween('created_at', [$from, $to])->where('payment_status', '!=', 'paid')->count(),
            'partial' => Order::query()->whereBetween('created_at', [$from, $to])->where('payment_status', 'partial')->count(),
            'verify' => Order::query()->whereBetween('created_at', [$from, $to])->whereNull('payment_status')->count(),
        ];

        return view('accounting.reconciliation', compact('orders', 'stats', 'from', 'to', 'rangeLabel'));
    }

    public function inventory(Request $request)
    {
        $timeFilter = (string) $request->input('time_filter', 'today');
        if (!in_array($timeFilter, ['today', 'date'], true)) {
            $timeFilter = 'today';
        }

        $selectedDate = (string) $request->input('selected_date', now()->toDateString());
        $targetDate = $timeFilter === 'today' ? now()->toDateString() : $selectedDate;
        $warehouseId = (int) $request->input('warehouse_id', 0);

        $inventoryQuery = Inventory::query()
            ->with(['productVariant.product:id,name', 'warehouse:id,name'])
            ->when($warehouseId > 0, fn ($q) => $q->where('warehouse_id', $warehouseId));

        $inventories = $inventoryQuery
            ->orderByDesc('quantity')
            ->paginate(25)
            ->appends($request->query());

        $documentBase = InventoryDocumentItem::query()
            ->join('inventory_documents', 'inventory_documents.id', '=', 'inventory_document_items.inventory_document_id')
            ->when($warehouseId > 0, fn ($q) => $q->where('inventory_documents.warehouse_id', $warehouseId))
            ->whereDate('inventory_documents.document_date', $targetDate);

        $imports = (int) (clone $documentBase)
            ->where('inventory_documents.type', 'import')
            ->sum('inventory_document_items.quantity');

        $exports = (int) (clone $documentBase)
            ->where('inventory_documents.type', 'export')
            ->sum('inventory_document_items.quantity');

        $rangeLabel = $timeFilter === 'today'
            ? 'Hom nay'
            : ('Ngay da chon: ' . Carbon::parse($targetDate)->format('d/m/Y'));

        return view('accounting.inventory', [
            'inventories' => $inventories,
            'warehouses' => Warehouse::query()->orderBy('name')->get(),
            'warehouseId' => $warehouseId,
            'timeFilter' => $timeFilter,
            'selectedDate' => $selectedDate,
            'rangeLabel' => $rangeLabel,
            'imports' => $imports,
            'exports' => $exports,
            'closingStock' => (int) Inventory::query()->when($warehouseId > 0, fn ($q) => $q->where('warehouse_id', $warehouseId))->sum('quantity'),
        ]);
    }

    public function commissions(Request $request)
    {
        if (!Schema::hasTable('accounting_customer_commissions')) {
            return view('accounting.commissions', [
                'rows' => collect(),
                'customers' => Customer::query()->orderBy('name')->limit(200)->get(),
                'missingTable' => true,
            ]);
        }

        $rows = DB::table('accounting_customer_commissions as c')
            ->leftJoin('customers', 'customers.id', '=', 'c.customer_id')
            ->select('c.*', 'customers.name as customer_name')
            ->orderByDesc('c.effective_date')
            ->paginate(25);

        return view('accounting.commissions', [
            'rows' => $rows,
            'customers' => Customer::query()->orderBy('name')->limit(200)->get(),
            'missingTable' => false,
        ]);
    }

    public function storeCommission(Request $request)
    {
        if (!Schema::hasTable('accounting_customer_commissions')) {
            return back()->with('error', 'Bang hoa hong chua duoc tao. Vui long chay migrate.');
        }

        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
            'effective_date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::table('accounting_customer_commissions')->insert([
            'customer_id' => $validated['customer_id'],
            'type' => $validated['type'],
            'value' => $validated['value'],
            'effective_date' => $validated['effective_date'],
            'note' => $validated['note'] ?? null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Da luu muc hoa hong khach hang.');
    }

    public function discounts(Request $request)
    {
        if (!Schema::hasTable('accounting_customer_discounts')) {
            return view('accounting.discounts', [
                'rows' => collect(),
                'customers' => Customer::query()->orderBy('name')->limit(200)->get(),
                'missingTable' => true,
            ]);
        }

        $rows = DB::table('accounting_customer_discounts as d')
            ->leftJoin('customers', 'customers.id', '=', 'd.customer_id')
            ->select('d.*', 'customers.name as customer_name')
            ->orderByDesc('d.effective_date')
            ->paginate(25);

        return view('accounting.discounts', [
            'rows' => $rows,
            'customers' => Customer::query()->orderBy('name')->limit(200)->get(),
            'missingTable' => false,
        ]);
    }

    public function storeDiscount(Request $request)
    {
        if (!Schema::hasTable('accounting_customer_discounts')) {
            return back()->with('error', 'Bang chiet khau chua duoc tao. Vui long chay migrate.');
        }

        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
            'effective_date' => ['required', 'date'],
            'condition_note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::table('accounting_customer_discounts')->insert([
            'customer_id' => $validated['customer_id'],
            'type' => $validated['type'],
            'value' => $validated['value'],
            'effective_date' => $validated['effective_date'],
            'condition_note' => $validated['condition_note'] ?? null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Da luu muc chiet khau khach hang.');
    }

    public function dailyOrders(Request $request)
    {
        $filterMode = (string) $request->input('filter_mode', 'day');
        if (!in_array($filterMode, ['day', 'month', 'custom'], true)) {
            $filterMode = 'day';
        }

        $date = (string) $request->input('date', now()->toDateString());
        $month = (string) $request->input('month', now()->format('Y-m'));
        $fromDate = (string) $request->input('from_date', now()->toDateString());
        $toDate = (string) $request->input('to_date', now()->toDateString());

        $from = now()->startOfDay();
        $to = now()->endOfDay();

        if ($filterMode === 'month') {
            $monthStart = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $from = $monthStart->copy()->startOfDay();
            $to = $monthStart->copy()->endOfMonth()->endOfDay();
        } elseif ($filterMode === 'custom') {
            $from = Carbon::parse($fromDate)->startOfDay();
            $to = Carbon::parse($toDate)->endOfDay();
            if ($from->gt($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }
        } else {
            $from = Carbon::parse($date)->startOfDay();
            $to = Carbon::parse($date)->endOfDay();
        }

        $customerId = (int) $request->input('customer_id', 0);
        $paymentStatus = (string) $request->input('payment_status', '');
        $warehouseId = (int) $request->input('warehouse_id', 0);

        $orders = Order::query()
            ->with(['customer:id,name', 'warehouse:id,name'])
            ->whereBetween('created_at', [$from, $to])
            ->when($customerId > 0, fn ($q) => $q->where('customer_id', $customerId))
            ->when($paymentStatus !== '', fn ($q) => $q->where('payment_status', $paymentStatus))
            ->when($warehouseId > 0, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->latest('created_at')
            ->paginate(30)
            ->appends($request->query());

        return view('accounting.daily_orders', [
            'orders' => $orders,
            'filterMode' => $filterMode,
            'date' => $date,
            'month' => $month,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'customerId' => $customerId,
            'paymentStatus' => $paymentStatus,
            'warehouseId' => $warehouseId,
            'customers' => Customer::query()->orderBy('name')->limit(300)->get(),
            'warehouses' => Warehouse::query()->orderBy('name')->get(),
        ]);
    }

    public function orders(Request $request)
    {
        $date = (string) $request->input('date', now()->toDateString());
        $customerId = (int) $request->input('customer_id', 0);
        $saleId = (int) $request->input('sale_id', 0);
        $paymentStatus = trim((string) $request->input('payment_status', ''));
        $status = trim((string) $request->input('status', ''));
        $keyword = trim((string) $request->input('keyword', ''));

        $allowedPerPage = [10, 20, 50, 100];
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $allowedSortBy = ['created_at', 'code', 'total', 'customer_name', 'sale_name'];
        $sortBy = (string) $request->input('sort_by', 'created_at');
        if (!in_array($sortBy, $allowedSortBy, true)) {
            $sortBy = 'created_at';
        }

        $sortDir = strtolower((string) $request->input('sort_dir', 'desc'));
        $sortDir = in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'desc';

        $query = Order::query()
            ->with([
                'customer:id,name',
                'user:id,name',
                'items:id,order_id,product_id,product_variant_id,quantity',
                'items.product:id,name',
                'items.variant:id,name',
                'adjustments' => function ($q) {
                    $q->with(['requester:id,name', 'items.variant.product'])
                      ->whereIn('status', [
                          OrderAdjustment::STATUS_PENDING_APPROVAL,
                          OrderAdjustment::STATUS_APPROVED,
                          OrderAdjustment::STATUS_REJECTED,
                      ])
                      ->latest();
                },
            ])
            ->withSum('items as total_item_quantity', 'quantity')
            ->whereDate('created_at', $date)
            ->when($customerId > 0, fn ($q) => $q->where('customer_id', $customerId))
            ->when($saleId > 0, fn ($q) => $q->where('user_id', $saleId))
            ->when($paymentStatus !== '', fn ($q) => $q->where('payment_status', $paymentStatus))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($sub) use ($keyword) {
                    $sub->where('code', 'like', "%{$keyword}%")
                        ->orWhereHas('customer', fn ($customerQ) => $customerQ->where('name', 'like', "%{$keyword}%"))
                        ->orWhereHas('user', fn ($userQ) => $userQ->where('name', 'like', "%{$keyword}%"));
                });
            });

        if ($sortBy === 'customer_name') {
            $query->orderBy(
                DB::table('customers')
                    ->select('name')
                    ->whereColumn('customers.id', 'orders.customer_id')
                    ->limit(1),
                $sortDir
            );
        } elseif ($sortBy === 'sale_name') {
            $query->orderBy(
                DB::table('users')
                    ->select('name')
                    ->whereColumn('users.id', 'orders.user_id')
                    ->limit(1),
                $sortDir
            );
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        $orders = $query->paginate($perPage)->appends($request->query());

        $dailyOrderIds = Order::query()
            ->whereDate('created_at', $date)
            ->pluck('id');

        $dailyTotalItemQuantity = (float) DB::table('order_items')
            ->whereIn('order_id', $dailyOrderIds)
            ->sum('quantity');

        $dailyTotalOrders = (int) $dailyOrderIds->count();

        $filteredItemQuantity = (float) $orders->getCollection()->sum(function ($order) {
            return (float) ($order->total_item_quantity ?? 0);
        });

        $sales = User::query()
            ->whereIn('id', Order::query()->select('user_id')->whereNotNull('user_id')->distinct())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('accounting.orders', [
            'orders' => $orders,
            'date' => $date,
            'customerId' => $customerId,
            'saleId' => $saleId,
            'paymentStatus' => $paymentStatus,
            'status' => $status,
            'keyword' => $keyword,
            'perPage' => $perPage,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'customers' => Customer::query()->orderBy('name')->limit(300)->get(['id', 'name']),
            'sales' => $sales,
            'dailyTotalOrders' => $dailyTotalOrders,
            'dailyTotalItemQuantity' => $dailyTotalItemQuantity,
            'filteredItemQuantity' => $filteredItemQuantity,
            'authUser' => auth()->user(),
        ]);
    }

    public function dailySales(Request $request)
    {
        $fromDate = (string) $request->input('from_date', now()->toDateString());
        $toDate   = (string) $request->input('to_date', now()->toDateString());
        $from = Carbon::parse($fromDate)->startOfDay();
        $to   = Carbon::parse($toDate)->endOfDay();
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $saleId     = (int) $request->input('sale_id', 0);
        $customerId = (int) $request->input('customer_id', 0);
        $sort       = (string) $request->input('sort', 'date_desc');

        $allowedPerPage = [10, 20, 50, 100, 200];
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        // Sub-query: one approved adjustment item per order_item (latest id wins)
        $approvedAdjSub = DB::table('order_adjustment_items as oai_s')
            ->join('order_adjustments as oa_s', function ($j) {
                $j->on('oa_s.id', '=', 'oai_s.order_adjustment_id')
                  ->where('oa_s.status', '=', OrderAdjustment::STATUS_APPROVED);
            })
            ->selectRaw('oai_s.order_item_id, MAX(oai_s.id) as adj_item_id')
            ->groupBy('oai_s.order_item_id');

        $makeBase = function () use ($approvedAdjSub, $from, $to, $saleId, $customerId) {
            return DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->leftJoin('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
                ->leftJoin('customers', 'orders.customer_id', '=', 'customers.id')
                ->leftJoin('users', 'orders.user_id', '=', 'users.id')
                ->leftJoinSub($approvedAdjSub, 'adj_max', 'adj_max.order_item_id', '=', 'order_items.id')
                ->leftJoin('order_adjustment_items as adj', 'adj.id', '=', 'adj_max.adj_item_id')
                ->whereNotIn('orders.status', ['rejected', 'cancelled'])
                ->whereBetween('orders.created_at', [$from, $to])
                ->when($saleId > 0, fn ($q) => $q->where('orders.user_id', $saleId))
                ->when($customerId > 0, fn ($q) => $q->where('orders.customer_id', $customerId));
        };

        // ── Paginated list ────────────────────────────────────────────
        $listQ = $makeBase()->select([
            'order_items.id',
            'orders.id as order_id_val',
            'orders.created_at as order_date',
            'orders.code as order_code',
            'products.name as product_name',
            'products.unit as product_unit',
            DB::raw("COALESCE(product_variants.size, '') as variant_size"),
            DB::raw("COALESCE(product_variants.name, '') as variant_name"),
            'customers.name as customer_name',
            DB::raw("COALESCE(customers.customer_code, '') as customer_code"),
            'users.name as sale_name',
            'order_items.quantity',
            'order_items.price',
            'order_items.total',
            'order_items.total_weight',
            'order_items.is_priced_by_kg',
            DB::raw("COALESCE(adj.adjusted_quantity, order_items.quantity) as eff_qty"),
            DB::raw("COALESCE(adj.adjusted_price,    order_items.price)    as eff_price"),
            DB::raw("COALESCE(adj.adjusted_weight,   order_items.total_weight) as eff_weight"),
            DB::raw("CASE WHEN adj.id IS NOT NULL THEN 1 ELSE 0 END as has_adj"),
            DB::raw("CASE WHEN adj.id IS NOT NULL THEN
                        CASE WHEN order_items.is_priced_by_kg = 1
                            THEN COALESCE(adj.adjusted_weight, order_items.total_weight) * COALESCE(adj.adjusted_price, order_items.price)
                            ELSE COALESCE(adj.adjusted_quantity, order_items.quantity)   * COALESCE(adj.adjusted_price, order_items.price)
                        END
                     ELSE order_items.total END as eff_total"),
        ]);

        match ($sort) {
            'date_asc'     => $listQ->orderBy('orders.created_at'),
            'product_asc'  => $listQ->orderBy('products.name')->orderByDesc('orders.created_at'),
            'product_desc' => $listQ->orderByDesc('products.name')->orderByDesc('orders.created_at'),
            'amount_asc'   => $listQ->orderBy('order_items.total')->orderByDesc('orders.created_at'),
            'amount_desc'  => $listQ->orderByDesc('order_items.total')->orderByDesc('orders.created_at'),
            'qty_asc'      => $listQ->orderBy('order_items.quantity')->orderByDesc('orders.created_at'),
            'qty_desc'     => $listQ->orderByDesc('order_items.quantity')->orderByDesc('orders.created_at'),
            'weight_asc'   => $listQ->orderBy('order_items.total_weight')->orderByDesc('orders.created_at'),
            'weight_desc'  => $listQ->orderByDesc('order_items.total_weight')->orderByDesc('orders.created_at'),
            default        => $listQ->orderByDesc('orders.created_at'),
        };

        $items = $listQ->paginate($perPage)->appends($request->query());

        // ── Grand summary (all pages) ──────────────────────────────────
        $effTotalExpr = "CASE WHEN adj.id IS NOT NULL THEN
            CASE WHEN order_items.is_priced_by_kg = 1
                THEN COALESCE(adj.adjusted_weight, order_items.total_weight) * COALESCE(adj.adjusted_price, order_items.price)
                ELSE COALESCE(adj.adjusted_quantity, order_items.quantity)   * COALESCE(adj.adjusted_price, order_items.price)
            END
         ELSE order_items.total END";

        $summary = $makeBase()->selectRaw("
            COUNT(DISTINCT order_items.id)                                                          as item_count,
            COUNT(DISTINCT order_items.order_id)                                                    as order_count,
            SUM(COALESCE(adj.adjusted_quantity,  order_items.quantity))                             as grand_qty,
            SUM(COALESCE(adj.adjusted_weight,    order_items.total_weight))                         as grand_weight,
            SUM({$effTotalExpr})                                                                     as grand_total
        ")->first();

        // ── Product stats ──────────────────────────────────────────────
        $productStats = $makeBase()->select([
            'products.id as product_id',
            'products.name as product_name',
            'products.unit as product_unit',
            DB::raw("SUM(COALESCE(adj.adjusted_quantity,  order_items.quantity))         as total_qty"),
            DB::raw("SUM(COALESCE(adj.adjusted_weight,    order_items.total_weight))     as total_weight"),
            DB::raw("SUM({$effTotalExpr})                                                as total_amount"),
        ])->groupBy('products.id', 'products.name', 'products.unit')
          ->orderByDesc('total_amount')
          ->get();

        $sales     = User::query()->orderBy('name')->select('id', 'name')->get();
        $customers = Customer::query()->orderBy('name')->select('id', 'name', 'customer_code')->get();

        return view('accounting.daily_sales', compact(
            'items', 'productStats', 'summary',
            'fromDate', 'toDate', 'saleId', 'customerId',
            'sort', 'perPage', 'sales', 'customers',
        ));
    }

    public function financialReports(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveDateRange($request);

        $revenue = (float) Order::query()->whereBetween('created_at', [$from, $to])->sum('total');
        $received = (float) Transaction::query()->whereBetween('created_at', [$from, $to])->where('type', 'payment')->sum('amount');
        $cost = (float) Transaction::query()->whereBetween('created_at', [$from, $to])->whereIn('type', ['refund', 'expense'])->sum('amount');
        $profit = $received - $cost;

        $series = Transaction::query()
            ->selectRaw('DATE(created_at) as day_key')
            ->selectRaw("SUM(CASE WHEN type = 'payment' THEN amount ELSE 0 END) as income")
            ->selectRaw("SUM(CASE WHEN type IN ('refund', 'expense') THEN amount ELSE 0 END) as expense")
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('day_key')
            ->orderBy('day_key')
            ->get();

        return view('accounting.financial_reports', compact('revenue', 'received', 'cost', 'profit', 'series', 'from', 'to', 'rangeLabel'));
    }

    private function resolveDateRange(Request $request): array
    {
        $range = (string) $request->input('range', 'month');
        $today = Carbon::today();

        $from = match ($range) {
            'day' => $today->copy()->startOfDay(),
            'week' => $today->copy()->startOfWeek(),
            'year' => $today->copy()->startOfYear(),
            'custom' => $request->filled('from_date')
                ? Carbon::parse((string) $request->input('from_date'))->startOfDay()
                : $today->copy()->startOfMonth(),
            default => $today->copy()->startOfMonth(),
        };

        $to = match ($range) {
            'day' => $today->copy()->endOfDay(),
            'week' => $today->copy()->endOfWeek(),
            'year' => $today->copy()->endOfYear(),
            'custom' => $request->filled('to_date')
                ? Carbon::parse((string) $request->input('to_date'))->endOfDay()
                : $today->copy()->endOfDay(),
            default => $today->copy()->endOfMonth(),
        };

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $rangeLabel = match ($range) {
            'day' => 'Theo ngay',
            'week' => 'Theo tuan',
            'year' => 'Theo nam',
            'custom' => 'Tuy chon',
            default => 'Theo thang',
        };

        return [$from, $to, $rangeLabel];
    }

    public function transactionCreate(Request $request)
    {
        return view('accounting.transaction_create');
    }

    public function transactionStore(Request $request)
    {
        $data = $request->validate([
            'order_id'      => 'nullable|exists:orders,id',
            'customer_id'   => 'nullable|exists:customers,id',
            'amount'        => 'required|numeric|min:0.01',
            'type'          => 'required|in:payment,refund,fee,extra_income,extra_expense',
            'method'        => 'nullable|string|max:50',
            'note'          => 'nullable|string|max:1000',
            'receipt_image' => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('receipt_image')) {
            $data['receipt_image_path'] = $request->file('receipt_image')->store('transactions/receipts', 'public');
        }
        unset($data['receipt_image']);

        $data['submitted_by'] = auth()->id();
        $data['status'] = Transaction::STATUS_PENDING_APPROVAL;

        $transaction = Transaction::create($data);

        $approvalService = app(\App\Services\ApprovalService::class);
        $hasWorkflow = $approvalService->initTransactionApproval($transaction);

        if (!$hasWorkflow) {
            // No workflow configured → auto-approve
            $transaction->update([
                'status' => Transaction::STATUS_APPROVED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
            $this->applyTransactionToOrder($transaction);
            return redirect()->route('accounting.transactions.create')
                ->with('success', 'Da tao va duyet giao dich #' . $transaction->id . ' thanh cong.');
        }

        return redirect()->route('accounting.transactions.create')
            ->with('success', 'Da gui giao dich #' . $transaction->id . ' cho quy trinh duyet. Cho cap tren xac nhan.');
    }

    public function transactionApprove(Request $request, Transaction $transaction)
    {
        $user = auth()->user();
        $approvalService = app(\App\Services\ApprovalService::class);

        abort_unless(
            $user->hasRole('admin') ||
            $user->hasRole('accountant') ||
            $approvalService->canApproveTransactionStep($transaction, $user),
            403
        );

        if ($transaction->status !== Transaction::STATUS_PENDING_APPROVAL) {
            return back()->with('error', 'Giao dich khong o trang thai cho duyet.');
        }

        $note = trim((string) $request->input('note', ''));

        $hasPendingStep = $transaction->approvalSteps()->where('status', 'pending')->exists();
        $allApproved = true;

        if ($hasPendingStep) {
            $allApproved = $approvalService->approveTransactionStep($transaction, $user, $note ?: null);
        }

        if ($allApproved) {
            $transaction->update([
                'status' => Transaction::STATUS_APPROVED,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);
            $this->applyTransactionToOrder($transaction);
            return back()->with('success', 'Da duyet giao dich #' . $transaction->id . ' thanh cong.');
        }

        return back()->with('success', 'Da duyet buoc nay. Giao dich chuyen sang buoc tiep theo.');
    }

    public function transactionReject(Request $request, Transaction $transaction)
    {
        $user = auth()->user();
        $approvalService = app(\App\Services\ApprovalService::class);

        abort_unless(
            $user->hasRole('admin') ||
            $user->hasRole('accountant') ||
            $approvalService->canApproveTransactionStep($transaction, $user),
            403
        );

        if ($transaction->status !== Transaction::STATUS_PENDING_APPROVAL) {
            return back()->with('error', 'Giao dich khong o trang thai cho duyet.');
        }

        $data = $request->validate(['reason' => 'required|string|max:2000']);

        $hasPendingStep = $transaction->approvalSteps()->where('status', 'pending')->exists();
        if ($hasPendingStep) {
            $approvalService->rejectTransactionStep($transaction, $user, $data['reason']);
        }

        $transaction->update([
            'status' => Transaction::STATUS_REJECTED,
            'rejected_by' => $user->id,
            'rejected_at' => now(),
            'reject_reason' => $data['reason'],
        ]);

        return back()->with('success', 'Da tu choi giao dich #' . $transaction->id . '.');
    }

    public function apiOrdersList(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 15), 100);
        $date = $request->input('date', '');
        $saleId = (int) $request->input('sale_id', 0);
        $customerId = (int) $request->input('customer_id', 0);
        $keyword = trim((string) $request->input('keyword', ''));

        $query = Order::query()
            ->with(['customer:id,name', 'user:id,name'])
            ->orderByDesc('created_at')
            ->when($date !== '', fn ($q) => $q->whereDate('created_at', $date))
            ->when($saleId > 0, fn ($q) => $q->where('user_id', $saleId))
            ->when($customerId > 0, fn ($q) => $q->where('customer_id', $customerId))
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where(function ($sub) use ($keyword) {
                    $sub->where('code', 'like', "%{$keyword}%")
                        ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$keyword}%"));
                });
            });

        $paginated = $query->paginate($perPage)->appends($request->query());

        $items = $paginated->getCollection()->map(fn ($o) => [
            'id'             => $o->id,
            'code'           => $o->code ?: ('#' . $o->id),
            'customer_name'  => $o->customer?->name ?? '-',
            'customer_id'    => $o->customer_id,
            'sale_name'      => $o->user?->name ?? '-',
            'total'          => (float) $o->total,
            'amount_due'     => (float) $o->amount_due,
            'amount_paid'    => (float) $o->amount_paid,
            'payment_status' => $o->payment_status,
            'status'         => $o->status,
            'created_at'     => $o->created_at?->format('d/m/Y H:i'),
        ]);

        return response()->json([
            'data'          => $items,
            'current_page'  => $paginated->currentPage(),
            'last_page'     => $paginated->lastPage(),
            'total'         => $paginated->total(),
            'per_page'      => $paginated->perPage(),
        ]);
    }

    public function apiCustomersList(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 15), 100);
        $keyword = trim((string) $request->input('keyword', ''));
        $sortBy  = in_array($request->input('sort_by'), ['name', 'id'], true) ? $request->input('sort_by') : 'name';
        $sortDir = $request->input('sort_dir', 'asc') === 'desc' ? 'desc' : 'asc';

        $paginated = Customer::query()
            ->when($keyword !== '', fn ($q) => $q->where(function ($sub) use ($keyword) {
                $sub->where('name', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('customer_code', 'like', "%{$keyword}%");
            }))
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage)
            ->appends($request->query());

        $items = $paginated->getCollection()->map(fn ($c) => [
            'id'    => $c->id,
            'name'  => $c->name,
            'phone' => $c->phone,
            'code'  => $c->customer_code,
        ]);

        return response()->json([
            'data'         => $items,
            'current_page' => $paginated->currentPage(),
            'last_page'    => $paginated->lastPage(),
            'total'        => $paginated->total(),
            'per_page'     => $paginated->perPage(),
        ]);
    }

    public function apiOrderDetail(Order $order)
    {
        $order->load('customer');

        $totalPaid = (float) $order->transactions()->where('status', Transaction::STATUS_APPROVED)->where('type', 'payment')->sum('amount');
        $totalRefunded = (float) $order->transactions()->where('status', Transaction::STATUS_APPROVED)->where('type', 'refund')->sum('amount');
        $debt = max(0, (float) $order->total - $totalPaid + $totalRefunded);

        $customer = $order->customer;
        $customerDebt = $customer
            ? (float) Order::where('customer_id', $customer->id)
                ->whereNotIn('status', ['cancelled', 'rejected'])
                ->sum('amount_due')
            : 0;

        return response()->json([
            'order' => [
                'id'             => $order->id,
                'code'           => $order->code ?: ('#' . $order->id),
                'total'          => (float) $order->total,
                'amount_paid'    => (float) $order->amount_paid,
                'amount_due'     => (float) $order->amount_due,
                'payment_status' => $order->payment_status,
                'status'         => $order->status,
                'created_at'     => $order->created_at?->format('d/m/Y H:i'),
            ],
            'customer' => $customer ? [
                'id'    => $customer->id,
                'name'  => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'total_debt' => $customerDebt,
            ] : null,
        ]);
    }

    public function apiCustomerDetail(Customer $customer)
    {
        $totalDebt = (float) Order::where('customer_id', $customer->id)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->sum('amount_due');

        $totalOrders = Order::where('customer_id', $customer->id)->count();
        $totalSpent = (float) Order::where('customer_id', $customer->id)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->sum('total');

        $lastOrder = Order::where('customer_id', $customer->id)->latest()->first();

        return response()->json([
            'id'          => $customer->id,
            'name'        => $customer->name,
            'phone'       => $customer->phone,
            'email'       => $customer->email,
            'address'     => $customer->address,
            'code'        => $customer->customer_code,
            'total_debt'  => $totalDebt,
            'total_orders'=> $totalOrders,
            'total_spent' => $totalSpent,
            'last_order_at' => $lastOrder?->created_at?->format('d/m/Y'),
        ]);
    }

    private function applyTransactionToOrder(Transaction $transaction): void
    {
        if (!$transaction->order_id) {
            return;
        }

        $order = $transaction->order;
        if (!$order) {
            return;
        }

        $totalPaid = (float) $order->transactions()->where('status', Transaction::STATUS_APPROVED)->where('type', 'payment')->sum('amount')
                   - (float) $order->transactions()->where('status', Transaction::STATUS_APPROVED)->where('type', 'refund')->sum('amount');

        $order->amount_paid = $totalPaid;
        $order->amount_due  = max(0, (float) $order->total - $totalPaid);
        $order->payment_status = match (true) {
            $totalPaid >= (float) $order->total => 'paid',
            $totalPaid > 0                      => 'partially_paid',
            default                             => 'unpaid',
        };
        $order->save();
    }
}

