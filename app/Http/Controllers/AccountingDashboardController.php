<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryDocumentItem;
use App\Models\Order;
use App\Models\Transaction;
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
}
