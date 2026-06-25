<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Account;
use App\Models\AccountBalanceRefreshLog;
use App\Models\AccountingReconciliation;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\InventoryDocument;
use App\Models\InventoryDocumentItem;
use App\Models\Order;
use App\Models\OrderAdjustment;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\AccountingOrderRevenueConfirmed;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountingDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:account,accountant,accounting,ceo,director,Director,admin']);
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
        $debtDaysMin = $request->filled('debt_days_min') ? max(0, (int) $request->input('debt_days_min')) : null;
        $debtDaysMax = $request->filled('debt_days_max') ? max(0, (int) $request->input('debt_days_max')) : null;
        $sortBy = (string) $request->input('sort_by', 'latest_debt_desc');
        $fromDate = $request->filled('from_date')
            ? Carbon::parse($request->input('from_date'))->startOfDay()
            : null;
        $toDate = $request->filled('to_date')
            ? Carbon::parse($request->input('to_date'))->endOfDay()
            : null;

        if ($fromDate && $toDate && $fromDate->gt($toDate)) {
            [$fromDate, $toDate] = [$toDate->copy()->startOfDay(), $fromDate->copy()->endOfDay()];
        }

        if ($keyword = trim((string) $request->input('keyword', ''))) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        $rows = $query->get()->map(function (Customer $customer) use ($fromDate, $toDate) {
            $summary = $this->customerDebtSummary($customer, $fromDate, $toDate);
            $debt = $summary['current_debt'];

            $lastPaymentAt = Transaction::query()
                ->where('customer_id', $customer->id)
                ->where('type', 'payment')
                ->when($fromDate, fn ($query) => $query->where('created_at', '>=', $fromDate))
                ->when($toDate, fn ($query) => $query->where('created_at', '<=', $toDate))
                ->max('created_at');

            $firstOrderDebtAt = Order::query()
                ->where('customer_id', $customer->id)
                ->whereHas('accountingReconciliation', fn ($query) => $query->where('status', AccountingReconciliation::STATUS_CONFIRMED))
                ->when($fromDate, fn ($query) => $query->where('created_at', '>=', $fromDate))
                ->when($toDate, fn ($query) => $query->where('created_at', '<=', $toDate))
                ->min('created_at');

            $firstAdjustmentDebtAt = Transaction::query()
                ->where('customer_id', $customer->id)
                ->whereIn('type', $this->customerDebtAdjustmentTypes())
                ->when($fromDate, fn ($query) => $query->where('created_at', '>=', $fromDate))
                ->when($toDate, fn ($query) => $query->where('created_at', '<=', $toDate))
                ->min('created_at');

            $latestOrderDebtAt = Order::query()
                ->where('customer_id', $customer->id)
                ->whereHas('accountingReconciliation', fn ($query) => $query->where('status', AccountingReconciliation::STATUS_CONFIRMED))
                ->when($fromDate, fn ($query) => $query->where('created_at', '>=', $fromDate))
                ->when($toDate, fn ($query) => $query->where('created_at', '<=', $toDate))
                ->max('created_at');

            $latestAdjustmentDebtAt = Transaction::query()
                ->where('customer_id', $customer->id)
                ->whereIn('type', $this->customerDebtAdjustmentTypes())
                ->when($fromDate, fn ($query) => $query->where('created_at', '>=', $fromDate))
                ->when($toDate, fn ($query) => $query->where('created_at', '<=', $toDate))
                ->max('created_at');

            $firstDebtAt = collect([$firstOrderDebtAt, $firstAdjustmentDebtAt])
                ->filter()
                ->map(fn ($date) => Carbon::parse($date))
                ->sort()
                ->first();

            $latestDebtAt = collect([$latestOrderDebtAt, $latestAdjustmentDebtAt])
                ->filter()
                ->map(fn ($date) => Carbon::parse($date))
                ->sortDesc()
                ->first();

            return [
                'customer' => $customer,
                'debt' => $debt,
                'debt_increase' => $summary['debt_increase'],
                'payments' => $summary['payments'],
                'debt_type' => $this->customerDebtTypeMeta((string) ($customer->debt_type ?: 'normal')),
                'due_date' => $firstDebtAt ? $firstDebtAt->copy()->addDays(7) : null,
                'first_debt_at' => $firstDebtAt,
                'latest_debt_at' => $latestDebtAt,
                'unpaid_days' => $debt > 0 && $firstDebtAt ? $firstDebtAt->diffInDays(now()) : 0,
                'status' => $debt <= 0 ? 'Đã thanh toán' : 'Còn nợ',
                'payment_history' => $lastPaymentAt ? ('Lần gần nhất: ' . Carbon::parse($lastPaymentAt)->format('d/m/Y H:i')) : 'Chưa có thanh toán',
            ];
        })
            ->filter(function (array $row) use ($debtDaysMin, $debtDaysMax): bool {
                if ($debtDaysMin === null && $debtDaysMax === null) {
                    return true;
                }

                if ($row['debt'] <= 0) {
                    return false;
                }

                if ($debtDaysMin !== null && $row['unpaid_days'] < $debtDaysMin) {
                    return false;
                }

                if ($debtDaysMax !== null && $row['unpaid_days'] > $debtDaysMax) {
                    return false;
                }

                return true;
            });

        $rows = (match ($sortBy) {
            'customer_asc' => $rows->sortBy(fn (array $row) => mb_strtolower((string) $row['customer']->name)),
            'customer_desc' => $rows->sortByDesc(fn (array $row) => mb_strtolower((string) $row['customer']->name)),
            'debt_type_asc' => $rows->sortBy(fn (array $row) => $row['debt_type']['label']),
            'debt_type_desc' => $rows->sortByDesc(fn (array $row) => $row['debt_type']['label']),
            'debt_increase_asc' => $rows->sortBy('debt_increase'),
            'debt_increase_desc' => $rows->sortByDesc('debt_increase'),
            'payments_asc' => $rows->sortBy('payments'),
            'payments_desc' => $rows->sortByDesc('payments'),
            'debt_asc' => $rows->sortBy('debt'),
            'debt_desc' => $rows->sortByDesc('debt'),
            'status_asc' => $rows->sortBy('status'),
            'status_desc' => $rows->sortByDesc('status'),
            'unpaid_days_asc' => $rows->sortBy('unpaid_days'),
            'unpaid_days_desc' => $rows->sortByDesc('unpaid_days'),
            'latest_debt_asc' => $rows->sortBy(fn (array $row) => optional($row['latest_debt_at'])->timestamp ?? 0),
            'latest_debt_desc' => $rows->sortByDesc(fn (array $row) => optional($row['latest_debt_at'])->timestamp ?? 0),
            default => $rows->sortByDesc('debt'),
        })->values();

        $perPage = 20;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $customers = new LengthAwarePaginator(
            $rows->forPage($currentPage, $perPage)->values(),
            $rows->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('accounting.customer_debts', [
            'customers' => $customers,
            'rows' => $customers->getCollection(),
            'keyword' => (string) $request->input('keyword', ''),
            'debtDaysMin' => $debtDaysMin,
            'debtDaysMax' => $debtDaysMax,
            'sortBy' => $sortBy,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'debtTypeOptions' => $this->customerDebtTypeOptions(),
            'totalDebt' => $customers->getCollection()->sum('debt'),
        ]);
    }

    public function customerDebtShow(Request $request, Customer $customer)
    {
        $customer->loadMissing(['assignedTo:id,name']);

        $fromDate = $request->filled('from_date')
            ? Carbon::parse($request->input('from_date'))->startOfDay()
            : null;
        $toDate = $request->filled('to_date')
            ? Carbon::parse($request->input('to_date'))->endOfDay()
            : null;

        if ($fromDate && $toDate && $fromDate->gt($toDate)) {
            [$fromDate, $toDate] = [$toDate->copy()->startOfDay(), $fromDate->copy()->endOfDay()];
        }

        $summary = $this->customerDebtSummary($customer, $fromDate, $toDate);

        $orders = Order::query()
            ->where('customer_id', $customer->id)
            ->whereHas('accountingReconciliation', fn ($query) => $query->where('status', AccountingReconciliation::STATUS_CONFIRMED))
            ->when($fromDate, fn ($query) => $query->where('created_at', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->where('created_at', '<=', $toDate))
            ->with([
                'items.product',
                'items.variant.product',
                'transactions' => fn ($query) => $query->whereIn('type', ['payment', 'refund']),
                'accountingReconciliation',
            ])
            ->latest()
            ->get();

        $orderDebtRows = $orders->map(function (Order $order): array {
            $payments = (float) $order->transactions->where('type', 'payment')->sum('amount');
            $refunds = (float) $order->transactions->where('type', 'refund')->sum('amount');
            $paid = max($payments - $refunds, 0);
            $total = (float) ($order->accountingReconciliation?->recognized_revenue ?? 0);

            return [
                'date' => $order->created_at,
                'label' => $order->code ?: ('#' . $order->id),
                'description' => $order->note ?: 'Phát sinh công nợ theo đơn hàng',
                'amount' => $total,
                'paid' => $paid,
                'remaining' => max($total - $paid, 0),
                'url' => accounting_route('orders.detail', $order),
                'items' => $order->items->map(function ($item): array {
                    $variant = $item->variant;
                    $product = $item->product ?? $variant?->product;

                    return [
                        'product_name' => $product?->name ?: '-',
                        'size' => $variant?->size ?: ($variant?->name ?: ($variant?->sku ?: '-')),
                        'quantity' => (float) ($item->quantity ?? 0),
                        'weight' => (float) ($item->total_weight ?? $item->display_total_value ?? 0),
                        'price' => (float) ($item->price ?? 0),
                        'total' => (float) ($item->total ?? ((float) ($item->price ?? 0) * (float) ($item->quantity ?? 0))),
                    ];
                })->values(),
            ];
        });

        $adjustments = Transaction::query()
            ->where('customer_id', $customer->id)
            ->whereIn('type', $this->customerDebtAdjustmentTypes())
            ->when($fromDate, fn ($query) => $query->where('created_at', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->where('created_at', '<=', $toDate))
            ->latest()
            ->get()
            ->map(function (Transaction $transaction): array {
                return [
                    'date' => $transaction->created_at,
                    'label' => $transaction->type === 'customer_opening_debt' ? 'Công nợ đầu kỳ' : 'Công nợ bổ sung',
                    'description' => $transaction->note ?: '-',
                    'amount' => (float) $transaction->amount,
                    'paid' => 0.0,
                    'remaining' => (float) $transaction->amount,
                    'url' => null,
                    'items' => collect(),
                ];
            });

        $debtIncreases = $orderDebtRows
            ->concat($adjustments)
            ->sortByDesc(fn (array $row) => optional($row['date'])->timestamp ?? 0)
            ->values();

        $payments = Transaction::query()
            ->where('customer_id', $customer->id)
            ->where('type', 'payment')
            ->where(function ($query): void {
                $query->whereNull('order_id')
                    ->orWhereHas('order.accountingReconciliation', fn ($reconciliation) => $reconciliation->where('status', AccountingReconciliation::STATUS_CONFIRMED));
            })
            ->when($fromDate, fn ($query) => $query->where('created_at', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->where('created_at', '<=', $toDate))
            ->with('order:id,code')
            ->latest()
            ->get();

        return view('accounting.customer_debt_show', [
            'customer' => $customer,
            'summary' => $summary,
            'debtIncreases' => $debtIncreases,
            'payments' => $payments,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'debtTypeOptions' => $this->customerDebtTypeOptions(),
            'currentDebtType' => (string) ($customer->debt_type ?: 'normal'),
            'currentDebtTypeMeta' => $this->customerDebtTypeMeta((string) ($customer->debt_type ?: 'normal')),
        ]);
    }

    public function orderDetail(Order $order)
    {
        $order->load([
            'customer:id,name,phone,email,address,company_name,tax_code',
            'user:id,name',
            'shipper:id,name',
            'warehouse:id,name',
            'items.product:id,name',
            'items.variant:id,name,sku,size',
            'transactions' => fn ($query) => $query->latest(),
            'histories.user:id,name',
            'returnRecords.returnItems.productVariant.product:id,name',
            'returnRecords.warehouse:id,name',
            'returnRecords.warehouseConfirmer:id,name',
            'accountingReconciliation.confirmer:id,name',
        ]);

        $returnAmount = $this->returnAmountForOrder($order);
        $recognizedRevenue = $this->recognizedRevenueForOrder($order);
        $effectivePaid = $this->effectivePaidForOrder($order);
        $effectiveDue = max(0, $recognizedRevenue - $effectivePaid);

        return view('accounting.order_detail', [
            'order' => $order,
            'returnAmount' => $returnAmount,
            'recognizedRevenue' => $recognizedRevenue,
            'effectivePaid' => $effectivePaid,
            'effectiveDue' => $effectiveDue,
            'reconciliationUrl' => accounting_route('reconciliation', [
                'date' => optional($order->delivered_at ?? $order->created_at)->toDateString(),
                'order_id' => $order->id,
            ]),
        ]);
    }

    public function customerDebtTypeUpdate(Request $request, Customer $customer)
    {
        $options = $this->customerDebtTypeOptions();

        $validated = $request->validate([
            'debt_type' => ['required', 'in:' . implode(',', array_keys($options))],
        ]);

        $customer->update([
            'debt_type' => $validated['debt_type'],
        ]);

        return redirect()
            ->route(accounting_route_name('customer-debts.show'), $customer)
            ->with('success', 'Đã cập nhật loại công nợ khách hàng.');
    }

    public function customerDebtAdjustmentStore(Request $request, Customer $customer)
    {
        $rawAmount = str_replace(['.', ',', ' '], '', (string) $request->input('amount', ''));
        $request->merge(['amount' => $rawAmount]);

        $validated = $request->validate([
            'adjustment_type' => ['required', 'in:opening,additional'],
            'amount' => ['required', 'numeric', 'min:1000'],
            'effective_date' => ['nullable', 'date'],
            'note' => ['required', 'string', 'max:255'],
        ]);

        $createdAt = !empty($validated['effective_date'])
            ? Carbon::parse($validated['effective_date'])->setTimeFrom(now())
            : now();

        $transaction = Transaction::create([
            'customer_id' => $customer->id,
            'amount' => round((float) $validated['amount'], 2),
            'type' => $validated['adjustment_type'] === 'opening'
                ? 'customer_opening_debt'
                : 'customer_debt_adjustment',
            'method' => $validated['adjustment_type'],
            'note' => $validated['note'],
            'submitted_by' => auth()->id(),
            'status' => Transaction::STATUS_APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $transaction->forceFill([
            'created_at' => $createdAt,
            'updated_at' => now(),
        ])->save();

        return redirect()
            ->route(accounting_route_name('customer-debts.show'), $customer)
            ->with('success', 'Đã cập nhật công nợ khách hàng.');
    }

    private function customerDebtAdjustmentTypes(): array
    {
        return ['customer_opening_debt', 'customer_debt_adjustment'];
    }

    private function customerDebtTypeOptions(): array
    {
        return [
            'hard_to_recover' => 'Khó thu hồi',
            'doubtful' => 'Khó đòi',
            'high_risk' => 'Rủi ro cao',
            'risk' => 'Rủi ro',
            'normal' => 'Bình Thường',
        ];
    }

    private function customerDebtTypeMeta(string $type): array
    {
        $labels = $this->customerDebtTypeOptions();
        $classes = [
            'hard_to_recover' => 'text-bg-danger',
            'doubtful' => 'text-bg-dark',
            'high_risk' => 'text-bg-warning',
            'risk' => 'text-bg-secondary',
            'normal' => 'text-bg-success',
        ];

        return [
            'value' => array_key_exists($type, $labels) ? $type : 'normal',
            'label' => $labels[$type] ?? $labels['normal'],
            'class' => $classes[$type] ?? $classes['normal'],
        ];
    }

    private function customerDebtSummary(Customer $customer, ?Carbon $fromDate = null, ?Carbon $toDate = null): array
    {
        $ordersTotal = (float) AccountingReconciliation::query()
            ->where('status', AccountingReconciliation::STATUS_CONFIRMED)
            ->whereHas('order', function ($query) use ($customer, $fromDate, $toDate): void {
                $query->where('customer_id', $customer->id)
                    ->when($fromDate, fn ($orderQuery) => $orderQuery->where('created_at', '>=', $fromDate))
                    ->when($toDate, fn ($orderQuery) => $orderQuery->where('created_at', '<=', $toDate));
            })
            ->sum('recognized_revenue');

        $adjustmentsTotal = (float) Transaction::query()
            ->where('customer_id', $customer->id)
            ->whereIn('type', $this->customerDebtAdjustmentTypes())
            ->when($fromDate, fn ($query) => $query->where('created_at', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->where('created_at', '<=', $toDate))
            ->sum('amount');

        $paymentsTotal = (float) Transaction::query()
            ->where('customer_id', $customer->id)
            ->where('type', 'payment')
            ->where(function ($query): void {
                $query->whereNull('order_id')
                    ->orWhereHas('order.accountingReconciliation', fn ($reconciliation) => $reconciliation->where('status', AccountingReconciliation::STATUS_CONFIRMED));
            })
            ->when($fromDate, fn ($query) => $query->where('created_at', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->where('created_at', '<=', $toDate))
            ->sum('amount');

        $refundsTotal = (float) Transaction::query()
            ->where('customer_id', $customer->id)
            ->where('type', 'refund')
            ->whereHas('order.accountingReconciliation', fn ($query) => $query->where('status', AccountingReconciliation::STATUS_CONFIRMED))
            ->when($fromDate, fn ($query) => $query->where('created_at', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->where('created_at', '<=', $toDate))
            ->sum('amount');

        $debtIncrease = $ordersTotal + $adjustmentsTotal + $refundsTotal;

        return [
            'orders_total' => $ordersTotal,
            'adjustments_total' => $adjustmentsTotal,
            'refunds_total' => $refundsTotal,
            'debt_increase' => $debtIncrease,
            'payments' => $paymentsTotal,
            'current_debt' => max($debtIncrease - $paymentsTotal, 0),
        ];
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
        $categoryId = (int) $request->input('category_id', 0);
        $accountId = (int) $request->input('account_id', 0);

        $baseQuery = Transaction::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('status', Transaction::STATUS_APPROVED)
            ->when($categoryId > 0, fn ($q) => $q->where('transaction_category_id', $categoryId))
            ->when($accountId > 0, fn ($q) => $q->where('account_id', $accountId));

        $pendingRequests = Transaction::query()
            ->with([
                'submitter:id,name',
                'transactionCategory:id,code,name,flow_direction',
                'account:id,name,type',
                'approvalSteps.step:id,role_slug,step_order',
            ])
            ->whereIn('status', [
                Transaction::STATUS_PENDING_APPROVAL,
                Transaction::STATUS_APPROVED_PENDING_COMPLETION,
            ])
            ->whereNotNull('request_source')
            ->latest('created_at')
            ->limit(20)
            ->get();
        $approvalService = app(\App\Services\ApprovalService::class);
        $pendingRequests->each(function (Transaction $transaction) use ($approvalService): void {
            if ($transaction->status === Transaction::STATUS_PENDING_APPROVAL) {
                $approvalService->ensureTransactionApprovalFlow($transaction);
            }
        });
        $pendingRequests->loadMissing('approvalSteps.step:id,role_slug,step_order');

        $transactions = (clone $baseQuery)
            ->with([
                'customer:id,name',
                'order:id,code',
                'submitter:id,name',
                'transactionCategory:id,code,name,flow_direction',
                'account:id,name,type,balance,warning_threshold',
            ])
            ->latest('created_at')
            ->paginate(25)
            ->appends($request->query());

        $accountSummaries = DB::table('accounts as a')
            ->leftJoin('transactions as t', function ($join) use ($from, $to, $categoryId) {
                $join->on('t.account_id', '=', 'a.id')
                    ->whereBetween('t.created_at', [$from, $to])
                    ->where('t.status', '=', Transaction::STATUS_APPROVED);
                if ($categoryId > 0) {
                    $join->where('t.transaction_category_id', '=', $categoryId);
                }
            })
            ->leftJoin('transaction_categories as tc', 'tc.id', '=', 't.transaction_category_id')
            ->when($accountId > 0, fn ($q) => $q->where('a.id', $accountId))
            ->selectRaw('a.id, a.name, a.type, a.balance, a.warning_threshold')
            ->selectRaw('COUNT(t.id) as txn_count')
            ->selectRaw("COALESCE(SUM(CASE WHEN COALESCE(tc.flow_direction, CASE WHEN t.type IN ('payment','extra_income') THEN 'in' ELSE 'out' END) = 'in' THEN t.amount ELSE 0 END), 0) as total_in")
            ->selectRaw("COALESCE(SUM(CASE WHEN COALESCE(tc.flow_direction, CASE WHEN t.type IN ('payment','extra_income') THEN 'in' ELSE 'out' END) = 'out' THEN t.amount ELSE 0 END), 0) as total_out")
            ->groupBy('a.id', 'a.name', 'a.type', 'a.balance', 'a.warning_threshold')
            ->orderBy('a.name')
            ->get();

        return view('accounting.cashflow', [
            'transactions' => $transactions,
            'accounts' => Account::active()->orderBy('name')->get(['id', 'name']),
            'accountId' => $accountId,
            'categoryId' => $categoryId,
            'type' => $request->input('type', ''),
            'transactionCategories' => TransactionCategory::active()->orderBy('sort_order')->get(['id', 'code', 'name', 'flow_direction']),
            'accountSummaries' => $accountSummaries,
            'pendingRequests' => $pendingRequests,
            'from' => $from,
            'to' => $to,
            'rangeLabel' => $rangeLabel,
            'incomeTotal' => (float) (clone $baseQuery)->whereIn('type', ['payment', 'extra_income'])->sum('amount'),
            'expenseTotal' => (float) (clone $baseQuery)->whereIn('type', ['refund', 'fee', 'expense', 'extra_expense'])->sum('amount'),
        ]);
    }

    public function cashflowShow(Transaction $transaction)
    {
        $transaction->load([
            'customer:id,name',
            'order:id,code,total',
            'submitter:id,name',
            'approver:id,name',
            'rejecter:id,name',
            'expenseType:id,name',
            'payeeUser:id,name',
            'transactionCategory:id,code,name,flow_direction',
            'account:id,name,type,balance',
            'destinationAccount:id,name,type,balance,account_number,bank_name',
            'approvalSteps.approver:id,name',
            'approvalSteps.step:id,role_slug,step_order',
        ]);

        $user = auth()->user();
        $approvalService = app(\App\Services\ApprovalService::class);
        if ($transaction->status === Transaction::STATUS_PENDING_APPROVAL && $transaction->request_source) {
            $approvalService->ensureTransactionApprovalFlow($transaction);
            $transaction->load([
                'approvalSteps.approver:id,name',
                'approvalSteps.step:id,role_slug,step_order',
            ]);
        }
        $canReview = $transaction->status === Transaction::STATUS_PENDING_APPROVAL && (
            $user->hasRole('admin') ||
            $approvalService->canApproveTransactionStep($transaction, $user)
        );
        $canComplete = $transaction->request_source
            && $transaction->status === Transaction::STATUS_APPROVED_PENDING_COMPLETION
            && ($user->hasRole('admin') || $user->hasRole('account') || $user->hasRole('accountant') || $user->hasRole('accounting'));

        return view('accounting.cashflow_show', [
            'transaction' => $transaction,
            'canReview' => $canReview,
            'canComplete' => $canComplete,
            'accounts' => Account::active()->orderBy('name')->get(['id', 'name', 'type', 'balance']),
            'transactionCategories' => TransactionCategory::active()
                ->orderBy('flow_direction')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'flow_direction']),
        ]);
    }

    public function cashflowPrint(Transaction $transaction)
    {
        abort_unless($transaction->request_source, 404);

        $transaction->load([
            'submitter:id,name,email,department_id,block_id',
            'submitter.department:id,name,block_id',
            'submitter.block:id,name',
            'approver:id,name',
            'rejecter:id,name',
            'transactionCategory:id,code,name,flow_direction',
            'account:id,name,type',
            'destinationAccount:id,name,type,account_number,bank_name',
        ]);

        return view('department_finance_requests.print', [
            'config' => [
                'label' => 'Kế toán',
            ],
            'transaction' => $transaction,
        ]);
    }

    public function reconciliation(Request $request)
    {
        $selectedDate = (string) $request->input('date', now()->toDateString());
        $targetDate = Carbon::parse($selectedDate)->toDateString();
        $orderId = (int) $request->input('order_id', 0);
        $saleId = (int) $request->input('sale_id', 0);
        $shipperId = (int) $request->input('shipper_id', 0);
        $status = trim((string) $request->input('status', ''));
        $paymentStatus = trim((string) $request->input('payment_status', ''));
        $accountingStatus = trim((string) $request->input('accounting_status', ''));

        $baseQuery = Order::query()
            ->with([
                'customer:id,name,phone,address',
                'user:id,name',
                'shipper:id,name',
                'returnRecords:id,order_id,status,refund_amount',
                'accountingReconciliation.confirmer:id,name',
            ])
            ->withSum('items as total_item_quantity', 'quantity')
            ->withSum('returnRecords as return_amount_sum', 'refund_amount')
            ->whereDate('delivered_at', $targetDate)
            ->when($orderId > 0, fn ($q) => $q->whereKey($orderId))
            ->when($saleId > 0, fn ($q) => $q->where('user_id', $saleId))
            ->when($shipperId > 0, fn ($q) => $q->where('shipper_id', $shipperId))
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($paymentStatus !== '', fn ($q) => $q->where('payment_status', $paymentStatus))
            ->when($accountingStatus === 'confirmed', fn ($q) => $q->whereHas('accountingReconciliation', fn ($r) => $r->where('status', AccountingReconciliation::STATUS_CONFIRMED)))
            ->when($accountingStatus === 'pending', fn ($q) => $q->whereDoesntHave('accountingReconciliation', fn ($r) => $r->where('status', AccountingReconciliation::STATUS_CONFIRMED)));

        $orders = (clone $baseQuery)
            ->orderByRaw('CASE WHEN delivered_at IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('delivered_at')
            ->paginate(25)
            ->appends($request->query());
        $orders->getCollection()->transform(function (Order $order) {
            $order->setAttribute('reconciliation_paid_amount', $this->effectivePaidForOrder($order));
            $order->setAttribute('reconciliation_due_amount', $this->effectiveDueForOrder($order));

            return $order;
        });

        $allForStats = (clone $baseQuery)->get();
        $confirmedCount = $allForStats->filter(fn (Order $order) => $order->accountingReconciliation?->status === AccountingReconciliation::STATUS_CONFIRMED)->count();
        $returnOrdersCount = $allForStats->filter(fn (Order $order) => (float) ($order->return_amount_sum ?? 0) > 0 || (bool) ($order->has_return_order ?? false))->count();

        $stats = [
            'total_orders' => $allForStats->count(),
            'total_items' => (float) $allForStats->sum('total_item_quantity'),
            'total_goods' => (float) $allForStats->sum(fn (Order $order) => (float) ($order->subtotal_amount ?? $order->total ?? 0)),
            'total_revenue' => (float) $allForStats->sum(fn (Order $order) => $this->recognizedRevenueForOrder($order)),
            'total_paid' => (float) $allForStats->sum(fn (Order $order) => $this->effectivePaidForOrder($order)),
            'total_due' => (float) $allForStats->sum(fn (Order $order) => $this->effectiveDueForOrder($order)),
            'total_shipping_fee' => (float) $allForStats->sum('shipping_fee'),
            'return_orders' => $returnOrdersCount,
            'confirmed' => $confirmedCount,
            'pending' => max(0, $allForStats->count() - $confirmedCount),
        ];

        return view('accounting.reconciliation', [
            'orders' => $orders,
            'stats' => $stats,
            'selectedDate' => $targetDate,
            'sales' => User::query()->whereHas('roles', fn ($q) => $q->whereIn('name', ['sale', 'leader_sale', 'sale_manager', 'manager_sale']))->orderBy('name')->get(['id', 'name']),
            'shippers' => User::query()->whereHas('roles', fn ($q) => $q->whereIn('name', ['shipper', 'ship']))->orderBy('name')->get(['id', 'name']),
            'saleId' => $saleId,
            'shipperId' => $shipperId,
            'status' => $status,
            'paymentStatus' => $paymentStatus,
            'accountingStatus' => $accountingStatus,
        ]);
    }

    public function paymentMatching(Request $request)
    {
        return view('accounting.payment_matching', [
            'accounts' => Account::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'type', 'balance']),
            'incomeCategories' => TransactionCategory::active()->where('flow_direction', 'in')->orderBy('sort_order')->get(['id', 'name', 'code']),
        ]);
    }

    public function paymentMatchingCustomers(Request $request)
    {
        $mode = (string) $request->input('mode', 'keyword');
        $keyword = trim((string) $request->input('keyword', ''));
        $transferContent = trim((string) $request->input('transfer_content', ''));
        $transferTokens = $this->paymentMatchingTokens($transferContent);

        $query = Customer::query()
            ->select(['id', 'name', 'phone', 'customer_code', 'customer_card_codes'])
            ->when($mode !== 'card' && $keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($sub) use ($keyword) {
                    $sub->where('name', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%")
                        ->orWhere('customer_code', 'like', "%{$keyword}%");
                });
            })
            ->when($mode === 'card' && Schema::hasColumn('customers', 'customer_card_codes'), fn ($query) => $query->whereNotNull('customer_card_codes'))
            ->orderBy('name');

        $customers = $query
            ->limit($mode === 'card' ? 500 : 30)
            ->get()
            ->map(function (Customer $customer) use ($transferTokens) {
                $codes = collect($customer->customer_card_codes ?: [])
                    ->map(fn ($code) => trim((string) $code))
                    ->filter()
                    ->values();
                $matchedCodes = $codes->filter(function (string $code) use ($transferTokens) {
                    $normalized = $this->normalizePaymentText($code);
                    return $normalized !== '' && collect($transferTokens)->contains(function (string $token) use ($normalized) {
                        return str_contains($token, $normalized) || str_contains($normalized, $token);
                    });
                })->values();

                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'code' => $customer->customer_code,
                    'card_codes' => $codes,
                    'matched_codes' => $matchedCodes,
                ];
            })
            ->when($mode === 'card', fn ($rows) => $rows->filter(fn (array $row) => count($row['matched_codes']) > 0)->values());

        return response()->json(['data' => $customers]);
    }

    public function paymentMatchingOrders(Request $request)
    {
        $rawAmount = preg_replace('/[^\d]/', '', (string) $request->input('amount', '')) ?: '0';
        $customerId = (int) $request->input('customer_id', 0);
        $amount = (float) $rawAmount;

        if ($customerId <= 0 || $amount <= 0) {
            return response()->json(['data' => []]);
        }

        $orders = Order::query()
            ->with(['customer:id,name', 'transactions' => fn ($query) => $query->where('status', Transaction::STATUS_APPROVED)->whereIn('type', ['payment', 'refund'])])
            ->where('customer_id', $customerId)
            ->whereNotIn('status', ['cancelled', 'canceled', 'rejected'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function (Order $order) use ($amount) {
                $paid = (float) $order->transactions->where('type', 'payment')->sum('amount')
                    - (float) $order->transactions->where('type', 'refund')->sum('amount');
                $remaining = (float) ($order->amount_due ?? 0);
                if ($remaining <= 0) {
                    $remaining = max(0, (float) ($order->total ?? 0) - $paid);
                }
                $isEnough = $remaining + 0.0001 >= $amount;

                return [
                    'id' => $order->id,
                    'code' => $order->code ?: ('#' . $order->id),
                    'created_at' => $order->created_at?->format('d/m/Y'),
                    'note' => $order->note ?: 'Đơn hàng',
                    'total' => (float) ($order->total ?? 0),
                    'amount_paid' => max($paid, 0),
                    'amount_due' => $remaining,
                    'payment_status' => $order->payment_status,
                    'is_enough' => $isEnough,
                    'short_amount' => $isEnough ? 0 : max(0, $amount - $remaining),
                ];
            })
            ->filter(fn (array $order) => (float) $order['amount_due'] > 0)
            ->sortByDesc(fn (array $order) => $order['is_enough'] ? 1 : 0)
            ->values();

        return response()->json(['data' => $orders]);
    }

    public function storeMatchedPayment(Request $request)
    {
        $rawAmount = preg_replace('/[^\d]/', '', (string) $request->input('amount', '')) ?: '0';
        $request->merge(['amount' => $rawAmount]);

        $validated = $request->validate([
            'transfer_content' => ['required', 'string', 'max:2000'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'customer_id' => ['required', 'exists:customers,id'],
            'order_id' => ['required', 'exists:orders,id'],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'transaction_category_id' => ['nullable', 'exists:transaction_categories,id'],
            'card_codes' => ['nullable', 'string', 'max:2000'],
        ]);

        $order = Order::query()
            ->with(['transactions' => fn ($query) => $query->where('status', Transaction::STATUS_APPROVED)->whereIn('type', ['payment', 'refund'])])
            ->where('customer_id', $validated['customer_id'])
            ->whereNotIn('status', ['cancelled', 'canceled', 'rejected'])
            ->findOrFail($validated['order_id']);
        $paid = (float) $order->transactions->where('type', 'payment')->sum('amount')
            - (float) $order->transactions->where('type', 'refund')->sum('amount');
        $remaining = (float) ($order->amount_due ?? 0);
        if ($remaining <= 0) {
            $remaining = max(0, (float) ($order->total ?? 0) - $paid);
        }
        if ((float) $validated['amount'] > $remaining + 0.0001) {
            return back()
                ->withErrors(['amount' => 'Số tiền chuyển khoản lớn hơn công nợ còn lại của đơn đã chọn.'])
                ->withInput();
        }

        $categoryId = $validated['transaction_category_id'] ?? null;
        if (!$categoryId) {
            $categoryId = TransactionCategory::active()->where('flow_direction', 'in')->orderBy('sort_order')->value('id');
        }

        $transaction = null;
        DB::transaction(function () use ($validated, $request, $order, $categoryId, &$transaction): void {
            $customer = Customer::query()->lockForUpdate()->findOrFail($validated['customer_id']);
            $cardCodes = collect(preg_split('/[\r\n,;]+/', (string) ($validated['card_codes'] ?? ''), -1, PREG_SPLIT_NO_EMPTY))
                ->map(fn ($code) => trim((string) $code))
                ->filter()
                ->merge($this->paymentMatchingCardCodeCandidates((string) $validated['transfer_content']))
                ->unique()
                ->values();

            if (Schema::hasColumn('customers', 'customer_card_codes')) {
                $existing = collect($customer->customer_card_codes ?: [])->map(fn ($code) => trim((string) $code))->filter();
                $customer->customer_card_codes = $existing->merge($cardCodes)->unique()->values()->all();
                $customer->save();
            }

            $transaction = Transaction::create([
                'order_id' => $order->id,
                'customer_id' => $customer->id,
                'amount' => (float) $validated['amount'],
                'type' => 'payment',
                'method' => 'bank',
                'transaction_category_id' => $categoryId,
                'account_id' => $validated['account_id'] ?? null,
                'note' => mb_substr('Thanh toán CK: ' . $validated['transfer_content'], 0, 255),
                'status' => Transaction::STATUS_APPROVED,
                'submitted_by' => $request->user()->id,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            $this->applyTransactionToOrder($transaction);
        });

        return redirect()->route(accounting_route_name('payment-matching'))
            ->with('success', 'Đã ghi nhận thanh toán #' . ($transaction?->id ?? '') . ' cho đơn ' . ($order->code ?: ('#' . $order->id)) . '.');
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
        $sortBy = (string) $request->input('sort_by', 'product_variant');
        if (!in_array($sortBy, ['product_variant', 'warehouse', 'quantity', 'selling_price', 'amount'], true)) {
            $sortBy = 'product_variant';
        }
        $sortDir = strtolower((string) $request->input('sort_dir', 'asc'));
        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        $today = now()->toDateString();
        $activeRuleSub = DB::table('product_price_rules as ppr_s')
            ->selectRaw('ppr_s.product_variant_id, MAX(ppr_s.id) as latest_rule_id')
            ->where(function ($q) use ($today) {
                $q->whereNull('ppr_s.start_date')
                  ->orWhereDate('ppr_s.start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('ppr_s.end_date')
                  ->orWhereDate('ppr_s.end_date', '>=', $today);
            })
            ->groupBy('ppr_s.product_variant_id');

        $inventoryBase = Inventory::query()
            ->leftJoin('product_variants as pv', 'pv.id', '=', 'inventories.product_variant_id')
            ->leftJoin('products as p', 'p.id', '=', 'pv.product_id')
            ->leftJoin('warehouses as wh', 'wh.id', '=', 'inventories.warehouse_id')
            ->leftJoinSub($activeRuleSub, 'active_rule', 'active_rule.product_variant_id', '=', 'inventories.product_variant_id')
            ->leftJoin('product_price_rules as ppr', 'ppr.id', '=', 'active_rule.latest_rule_id')
            ->select([
                'inventories.*',
                DB::raw('COALESCE(ppr.price, p.price, 0) as selling_price'),
            ])
            ->with(['productVariant.product:id,name', 'warehouse:id,name'])
            ->when($warehouseId > 0, fn ($q) => $q->where('warehouse_id', $warehouseId));

        $inventoryQuery = clone $inventoryBase;

        if ($sortBy === 'warehouse') {
            $inventoryQuery
                ->orderBy('wh.name', $sortDir)
                ->orderBy('p.name')
                ->orderByRaw("COALESCE(pv.name, '')")
                ->orderByRaw("COALESCE(pv.size, '')")
                ->orderByDesc('inventories.quantity');
        } elseif ($sortBy === 'quantity') {
            $inventoryQuery
                ->orderBy('inventories.quantity', $sortDir)
                ->orderBy('p.name')
                ->orderByRaw("COALESCE(pv.name, '')")
                ->orderByRaw("COALESCE(pv.size, '')")
                ->orderBy('wh.name');
        } elseif ($sortBy === 'selling_price') {
            $inventoryQuery
                ->orderByRaw('COALESCE(ppr.price, p.price, 0) ' . strtoupper($sortDir))
                ->orderBy('p.name')
                ->orderByRaw("COALESCE(pv.name, '')")
                ->orderByRaw("COALESCE(pv.size, '')")
                ->orderBy('wh.name');
        } elseif ($sortBy === 'amount') {
            $inventoryQuery
                ->orderByRaw('inventories.quantity * COALESCE(ppr.price, p.price, 0) ' . strtoupper($sortDir))
                ->orderBy('p.name')
                ->orderByRaw("COALESCE(pv.name, '')")
                ->orderByRaw("COALESCE(pv.size, '')")
                ->orderBy('wh.name');
        } else {
            $inventoryQuery
                ->orderBy('p.name', $sortDir)
                ->orderByRaw("COALESCE(pv.name, '')")
                ->orderByRaw("COALESCE(pv.size, '')")
                ->orderBy('wh.name')
                ->orderByDesc('inventories.quantity');
        }

        $inventories = $inventoryQuery
            ->paginate(25)
            ->appends($request->query());

        $totalAmount = (float) DB::table('inventories')
            ->leftJoin('product_variants as pv', 'pv.id', '=', 'inventories.product_variant_id')
            ->leftJoin('products as p', 'p.id', '=', 'pv.product_id')
            ->leftJoinSub($activeRuleSub, 'active_rule', 'active_rule.product_variant_id', '=', 'inventories.product_variant_id')
            ->leftJoin('product_price_rules as ppr', 'ppr.id', '=', 'active_rule.latest_rule_id')
            ->when($warehouseId > 0, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->selectRaw('SUM(inventories.quantity * COALESCE(ppr.price, p.price, 0)) as total_amount')
            ->value('total_amount');

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
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'timeFilter' => $timeFilter,
            'selectedDate' => $selectedDate,
            'rangeLabel' => $rangeLabel,
            'imports' => $imports,
            'exports' => $exports,
            'totalAmount' => $totalAmount,
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
            ->with(['customer:id,name', 'warehouse:id,name', 'user:id,name'])
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
                'customer:id,name,address,delivery_time',
                'user:id,name',
                'shipper:id,name',
                'warehouse:id,name',
                'items.product.avatar.media',
                'items.variant.avatar.media',
                'accountingReconciliation.confirmer:id,name',
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

        $received = (float) Transaction::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('status', Transaction::STATUS_APPROVED)
            ->where('type', 'payment')
            ->sum('amount');

        $cost = (float) Transaction::query()
            ->whereBetween('created_at', [$from, $to])
            ->where('status', Transaction::STATUS_APPROVED)
            ->whereIn('type', ['refund', 'expense'])
            ->sum('amount');

        $profit = $received - $cost;

        $series = Transaction::query()
            ->selectRaw('DATE(created_at) as day_key')
            ->selectRaw("SUM(CASE WHEN type = 'payment' THEN amount ELSE 0 END) as income")
            ->selectRaw("SUM(CASE WHEN type IN ('refund', 'expense') THEN amount ELSE 0 END) as expense")
            ->whereBetween('created_at', [$from, $to])
            ->where('status', Transaction::STATUS_APPROVED)
            ->groupBy('day_key')
            ->orderBy('day_key')
            ->get();

        // Transaction by category with customers and accounts
        $accountFilterId = $request->input('account_id');
        $catStatsQuery = Transaction::query()
            ->with([
                'transactionCategory:id,code,name,flow_direction',
                'customer:id,name',
                'account:id,name,type'
            ])
            ->whereBetween('created_at', [$from, $to])
            ->where('status', Transaction::STATUS_APPROVED)
            ->whereNotNull('transaction_category_id');

        if ($accountFilterId) {
            $catStatsQuery->where('account_id', $accountFilterId);
        }

        $allTransactionsByCategory = $catStatsQuery->get();

        // Group and aggregate
        $catStats = collect();
        foreach ($allTransactionsByCategory->groupBy('transaction_category_id') as $categoryId => $transactions) {
            $totalAmount = $transactions->sum('amount');
            $totalCount = $transactions->count();

            // Get unique customers and accounts for this category
            $customers = $transactions
                ->filter(fn($t) => $t->customer_id)
                ->map(fn($t) => ['id' => $t->customer_id, 'name' => $t->customer?->name ?? 'N/A'])
                ->unique('id')
                ->values();

            $accounts = $transactions
                ->filter(fn($t) => $t->account_id)
                ->map(fn($t) => ['id' => $t->account_id, 'name' => $t->account?->name ?? 'N/A', 'type' => $t->account?->type ?? 'N/A'])
                ->unique('id')
                ->values();

            $catStats->push((object)[
                'transaction_category_id' => $categoryId,
                'transactionCategory' => $transactions->first()?->transactionCategory,
                'total_count' => $totalCount,
                'total_amount' => $totalAmount,
                'customers' => $customers,
                'accounts' => $accounts,
            ]);
        }

        $catStats = $catStats->sortByDesc('total_amount')->values();

        // Get available accounts for filter
        $accounts = Account::active()->orderBy('name')->get(['id', 'name', 'type']);

        return view('accounting.financial_reports', compact('revenue', 'received', 'cost', 'profit', 'series', 'from', 'to', 'rangeLabel', 'catStats', 'accounts', 'accountFilterId'));
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
        $transactionCategories = \App\Models\TransactionCategory::active()->orderBy('sort_order')->get();
        $accounts = \App\Models\Account::active()->orderBy('name')->get(['id', 'name', 'type', 'balance', 'warning_threshold']);
        return view('accounting.transaction_create', compact('transactionCategories', 'accounts'));
    }

    public function transactionEdit(Transaction $transaction)
    {
        $transaction->load(['transactionCategory:id,code,name,flow_direction', 'order.customer:id,name', 'customer:id,name', 'account:id,name,type,balance,warning_threshold']);

        $transactionCategories = \App\Models\TransactionCategory::active()->orderBy('sort_order')->get();
        $accounts = \App\Models\Account::active()->orderBy('name')->get(['id', 'name', 'type', 'balance', 'warning_threshold']);

        return view('accounting.transaction_create', compact('transactionCategories', 'accounts', 'transaction'));
    }

    public function transactionStore(Request $request)
    {
        // Strip thousand-separator formatting from amount
        $raw = str_replace(['.', ' '], '', $request->input('amount', ''));
        $request->merge(['amount' => $raw]);

        $data = $request->validate([
            'order_id'        => 'nullable|exists:orders,id',
            'customer_id'     => 'nullable|exists:customers,id',
            'amount'          => 'required|numeric|min:0.01',
            'expense_type_id' => 'nullable|exists:expense_types,id',
            'payee_user_id'   => 'nullable|exists:users,id',
            'method'          => 'nullable|string|max:50',
            'transaction_category_id' => 'required|exists:transaction_categories,id',
            'account_id'      => 'nullable|exists:accounts,id',
            'note'            => 'nullable|string|max:1000',
            'receipt_image'   => 'nullable|image|max:5120',
        ]);

        // Infer 'type' from transaction category's flow_direction
        $category = \App\Models\TransactionCategory::find($data['transaction_category_id']);
        $flowDirection = $category?->flow_direction ?? 'out';
        $data['type'] = $flowDirection === 'in' ? 'payment' : 'refund';

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
            return redirect()->route(accounting_route_name('transactions.create'))
                ->with('success', 'Da tao va duyet giao dich #' . $transaction->id . ' thanh cong.');
        }

        return redirect()->route(accounting_route_name('transactions.create'))
            ->with('success', 'Da gui giao dich #' . $transaction->id . ' cho quy trinh duyet. Cho cap tren xac nhan.');
    }

    public function transactionUpdate(Request $request, Transaction $transaction)
    {
        $previousAccountId = $transaction->account_id;
        $previousOrderId = $transaction->order_id;

        $raw = str_replace(['.', ' '], '', $request->input('amount', ''));
        $request->merge(['amount' => $raw]);

        $data = $request->validate([
            'order_id'        => 'nullable|exists:orders,id',
            'customer_id'     => 'nullable|exists:customers,id',
            'amount'          => 'required|numeric|min:0.01',
            'expense_type_id' => 'nullable|exists:expense_types,id',
            'payee_user_id'   => 'nullable|exists:users,id',
            'method'          => 'nullable|string|max:50',
            'transaction_category_id' => 'required|exists:transaction_categories,id',
            'account_id'      => 'nullable|exists:accounts,id',
            'note'            => 'nullable|string|max:1000',
            'receipt_image'   => 'nullable|image|max:5120',
        ]);

        $category = \App\Models\TransactionCategory::find($data['transaction_category_id']);
        $flowDirection = $category?->flow_direction ?? 'out';
        $data['type'] = $flowDirection === 'in' ? 'payment' : 'refund';

        if ($request->hasFile('receipt_image')) {
            $data['receipt_image_path'] = $request->file('receipt_image')->store('transactions/receipts', 'public');
        }
        unset($data['receipt_image']);

        $transaction->update($data);

        $this->syncTransactionAccountingState($transaction, $previousAccountId, $previousOrderId);

        return redirect()->route(accounting_route_name('cashflow.show'), $transaction)
            ->with('success', 'Đã cập nhật giao dịch #' . $transaction->id . '.');
    }

    public function transactionApprove(Request $request, Transaction $transaction)
    {
        $user = auth()->user();
        $approvalService = app(\App\Services\ApprovalService::class);
        if ($transaction->status === Transaction::STATUS_PENDING_APPROVAL && $transaction->request_source) {
            $approvalService->ensureTransactionApprovalFlow($transaction);
        }
        abort_unless(
            $user->hasRole('admin') ||
            $approvalService->canApproveTransactionStep($transaction, $user),
            403
        );

        if ($transaction->status !== Transaction::STATUS_PENDING_APPROVAL) {
            return back()->with('error', 'Giao dich khong o trang thai cho duyet.');
        }

        $note = trim((string) $request->input('note', ''));
        $currentStep = $approvalService->getCurrentPendingTransactionStep($transaction);
        $currentRole = strtolower((string) ($currentStep?->step?->role_slug ?? ''));
        $isAccountingStep = in_array($currentRole, $approvalService->financeAccountingRoleSlugs(), true);

        if ($transaction->request_source && $isAccountingStep) {
            $validated = $request->validate([
                'transaction_category_id' => ['required', 'integer', 'exists:transaction_categories,id'],
                'account_id' => ['required', 'integer', 'exists:accounts,id'],
            ]);

            $requestedFlow = in_array((string) $transaction->type, ['payment', 'extra_income'], true) ? 'in' : 'out';
            $category = TransactionCategory::query()
                ->whereKey((int) $validated['transaction_category_id'])
                ->where('flow_direction', $requestedFlow)
                ->first();

            if (!$category) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'transaction_category_id' => 'Danh mục kế toán không phù hợp với dòng tiền của phiếu.',
                ]);
            }

            if (
                $category->flow_direction === 'out'
                && $transaction->destination_type === 'internal'
                && (int) $validated['account_id'] === (int) $transaction->destination_account_id
            ) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'account_id' => 'Tài khoản thực hiện phải khác tài khoản nhận khi chuyển khoản nội bộ.',
                ]);
            }

            $transaction->forceFill([
                'transaction_category_id' => $category->id,
                'account_id' => (int) $validated['account_id'],
                'type' => $category->flow_direction === 'in' ? 'extra_income' : 'extra_expense',
            ])->save();
        }

        $hasPendingStep = $transaction->approvalSteps()->where('status', 'pending')->exists();
        $allApproved = true;

        if ($hasPendingStep) {
            $allApproved = $approvalService->approveTransactionStep($transaction, $user, $note ?: null);
        }

        if ($allApproved) {
            if ($transaction->request_source) {
                $transaction->update([
                    'status' => Transaction::STATUS_APPROVED_PENDING_COMPLETION,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                ]);

                return back()->with('success', 'Director đã duyệt phiếu #' . $transaction->id . '. Phiếu đã chuyển về kế toán để hoàn thành chuyển tiền.');
            }

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

    public function transactionComplete(Request $request, Transaction $transaction)
    {
        $user = auth()->user();

        abort_unless(
            $user->hasRole('admin') || $user->hasRole('account') || $user->hasRole('accountant') || $user->hasRole('accounting'),
            403
        );

        if (!$transaction->request_source || $transaction->status !== Transaction::STATUS_APPROVED_PENDING_COMPLETION) {
            return back()->with('error', 'Phiếu không ở trạng thái chờ kế toán hoàn thành.');
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if (!$transaction->transaction_category_id || !$transaction->account_id) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'account_id' => 'Phiếu chưa có danh mục kế toán hoặc tài khoản thực hiện. Vui lòng quay lại bước kế toán xác nhận.',
            ]);
        }

        $note = trim((string) ($validated['note'] ?? ''));
        $currentNote = trim((string) $transaction->note);

        $transaction->forceFill([
            'note' => $note !== '' ? trim($currentNote . "\nKế toán hoàn thành: " . $note) : $transaction->note,
            'status' => Transaction::STATUS_APPROVED,
        ])->save();

        $this->applyTransactionToOrder($transaction);

        return back()->with('success', 'Đã hoàn thành phiếu #' . $transaction->id . ' và ghi nhận chuyển tiền thực tế.');
    }

    public function transactionReject(Request $request, Transaction $transaction)
    {
        $user = auth()->user();
        $approvalService = app(\App\Services\ApprovalService::class);
        if ($transaction->status === Transaction::STATUS_PENDING_APPROVAL && $transaction->request_source) {
            $approvalService->ensureTransactionApprovalFlow($transaction);
        }

        abort_unless(
            $user->hasRole('admin') ||
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

    public function reconciliationDetail(Order $order)
    {
        $order->load([
            'customer:id,name,phone,address',
            'user:id,name',
            'shipper:id,name',
            'warehouse:id,name',
            'items.product:id,name',
            'items.variant:id,name,sku,size',
            'histories.user:id,name',
            'transactions.submitter:id,name',
            'transactions.approver:id,name',
            'returnRecords.returnItems.productVariant.product:id,name',
            'returnRecords.warehouse:id,name',
            'returnRecords.warehouseConfirmer:id,name',
            'accountingReconciliation.confirmer:id,name',
        ]);

        $returnAmount = $this->returnAmountForOrder($order);
        $recognizedRevenue = $this->recognizedRevenueForOrder($order);
        $effectivePaid = $this->effectivePaidForOrder($order);
        $effectiveDue = $this->effectiveDueForOrder($order);
        [$canConfirm, $blockReason] = $this->canAccountingConfirmOrder($order);

        $approvedHistory = $order->histories
            ->whereIn('action', ['approve_order', 'order_approved', 'approve'])
            ->sortByDesc('id')
            ->first();
        $packingHistory = $order->histories
            ->whereIn('action', ['complete_packing', 'warehouse_complete_packing'])
            ->sortByDesc('id')
            ->first();
        $deliveryHistory = $order->histories
            ->whereIn('action', ['mark_delivered', 'delivered', 'mobile_update_status'])
            ->sortByDesc('id')
            ->first();
        $paymentTransaction = $order->transactions
            ->where('status', Transaction::STATUS_APPROVED)
            ->where('type', 'payment')
            ->sortByDesc('id')
            ->first();

        return response()->json([
            'order' => [
                'id' => $order->id,
                'code' => $order->code ?: ('#' . $order->id),
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total' => (float) $order->total,
                'subtotal_amount' => (float) ($order->subtotal_amount ?? $order->total ?? 0),
                'total_discount' => (float) ($order->total_discount ?? 0),
                'amount_paid' => $effectivePaid,
                'amount_due' => $effectiveDue,
                'accounting_amount_paid' => (float) ($order->amount_paid ?? 0),
                'shipper_collected_amount' => (float) ($order->collected_amount ?? 0),
                'shipping_fee' => (float) ($order->shipping_fee ?? 0),
                'return_amount' => $returnAmount,
                'recognized_revenue' => $recognizedRevenue,
                'delivered_at' => optional($order->delivered_at)->format('d/m/Y H:i'),
                'customer' => [
                    'name' => $order->customer?->name ?? '-',
                    'phone' => $order->customer?->phone ?? '-',
                    'address' => $order->customer?->address ?? '-',
                ],
                'sale' => $order->user?->name ?? '-',
                'shipper' => $order->shipper?->name ?? '-',
                'warehouse' => $order->warehouse?->name ?? '-',
                'note' => $order->note,
                'shipper_note' => $order->shipper_note,
            ],
            'items' => $order->items->map(fn ($item) => [
                'product_name' => $item->product?->name ?? 'San pham',
                'variant_name' => $item->variant?->name ?? '-',
                'name' => $item->variant?->name ?? $item->product?->name ?? 'San pham',
                'sku' => $item->variant?->sku,
                'size' => $item->variant?->size,
                'quantity' => (float) ($item->quantity ?? 0),
                'total_label' => $item->display_total_label,
                'weight' => (float) ($item->actual_weight ?? $item->packed_weight ?? $item->total_weight ?? 0),
                'unit_price' => (float) ($item->price ?? $item->unit_price ?? 0),
                'line_total' => (float) ($item->subtotal ?? $item->total ?? ((float) ($item->quantity ?? 0) * (float) ($item->price ?? $item->unit_price ?? 0))),
            ])->values(),
            'approval' => [
                'created_by' => $order->user?->name ?? '-',
                'approved_by' => $approvedHistory?->user?->name ?? '-',
                'approved_at' => optional($approvedHistory?->created_at)->format('d/m/Y H:i'),
                'note' => $approvedHistory?->note,
            ],
            'packing' => [
                'packed_by' => $packingHistory?->user?->name ?? '-',
                'packed_at' => optional($packingHistory?->created_at)->format('d/m/Y H:i'),
                'warehouse' => $order->warehouse?->name ?? '-',
                'note' => $packingHistory?->note,
            ],
            'delivery' => [
                'shipper' => $order->shipper?->name ?? '-',
                'status' => $order->status,
                'delivered_at' => optional($order->delivered_at)->format('d/m/Y H:i'),
                'shipping_fee' => (float) ($order->shipping_fee ?? 0),
                'note' => $deliveryHistory?->note ?? $order->shipper_note,
            ],
            'payment' => [
                'total_due' => (float) $order->total,
                'paid_amount' => $effectivePaid,
                'accounting_paid_amount' => (float) ($order->amount_paid ?? 0),
                'shipper_collected_amount' => (float) ($order->collected_amount ?? 0),
                'amount_due' => $effectiveDue,
                'method' => $order->payment_method,
                'paid_at' => optional($paymentTransaction?->created_at)->format('d/m/Y H:i'),
                'confirmed_by' => $paymentTransaction?->approver?->name ?? $paymentTransaction?->submitter?->name ?? '-',
            ],
            'returns' => $order->returnRecords->map(fn ($return) => [
                'status' => $return->status,
                'reason' => $return->reason,
                'warehouse' => $return->warehouse?->name ?? '-',
                'confirmed_by' => $return->warehouseConfirmer?->name ?? '-',
                'confirmed_at' => optional($return->warehouse_confirmed_at)->format('d/m/Y H:i'),
                'refund_amount' => (float) ($return->refund_amount ?? 0),
                'items' => $return->returnItems->map(fn ($item) => [
                    'name' => $item->productVariant?->product?->name ?? $item->productVariant?->name ?? 'San pham',
                    'quantity' => (float) ($item->quantity ?? 0),
                    'received_weight' => (float) ($item->received_weight ?? 0),
                ])->values(),
            ])->values(),
            'reconciliation' => [
                'status' => $order->accountingReconciliation?->status ?? AccountingReconciliation::STATUS_PENDING,
                'confirmed_by' => $order->accountingReconciliation?->confirmer?->name,
                'confirmed_at' => optional($order->accountingReconciliation?->confirmed_at)->format('d/m/Y H:i'),
                'note' => $order->accountingReconciliation?->note,
                'can_confirm' => $canConfirm,
                'block_reason' => $blockReason,
            ],
        ]);
    }

    public function confirmReconciliation(Request $request, Order $order)
    {
        if (!Schema::hasTable('accounting_reconciliations')) {
            return response()->json(['message' => 'Bang doi soat ke toan chua duoc tao. Vui long chay migrate.'], 500);
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $order->load(['returnRecords.returnItems', 'accountingReconciliation', 'user']);
        [$canConfirm, $blockReason] = $this->canAccountingConfirmOrder($order);
        if (!$canConfirm) {
            return response()->json(['message' => $blockReason], 422);
        }

        if ($order->accountingReconciliation?->status === AccountingReconciliation::STATUS_CONFIRMED) {
            return response()->json(['message' => 'Don hang da duoc ke toan xac nhan.'], 422);
        }

        $reconciliation = null;
        DB::transaction(function () use ($order, $request, $validated, &$reconciliation): void {
            $returnAmount = $this->returnAmountForOrder($order);
            $recognizedRevenue = $this->recognizedRevenueForOrder($order);
            $effectivePaid = $this->effectivePaidForOrder($order);

            $reconciliation = AccountingReconciliation::query()->updateOrCreate(
                ['order_id' => $order->id],
                [
                    'sale_id' => $order->user_id,
                    'shipper_id' => $order->shipper_id,
                    'total_amount' => (float) ($order->total ?? 0),
                    'paid_amount' => $effectivePaid,
                    'shipping_fee' => (float) ($order->shipping_fee ?? 0),
                    'return_amount' => $returnAmount,
                    'recognized_revenue' => $recognizedRevenue,
                    'status' => AccountingReconciliation::STATUS_CONFIRMED,
                    'confirmed_by' => $request->user()->id,
                    'confirmed_at' => now(),
                    'note' => $validated['note'] ?? null,
                ]
            );

            $amountDue = max(0, $recognizedRevenue - $effectivePaid);
            $order->forceFill([
                'status' => Order::STATUS_COMPLETED,
                'amount_due' => $amountDue,
                'payment_status' => match (true) {
                    $amountDue <= 0.0001 => 'paid',
                    $effectivePaid > 0 => 'partially_paid',
                    default => 'unpaid',
                },
            ])->save();

            $this->createCommissionForCompletedOrder($order, $recognizedRevenue, (int) $request->user()->id);
        });

        $reconciliation?->load(['order', 'sale']);
        if ($reconciliation?->sale) {
            $reconciliation->sale->notify(new AccountingOrderRevenueConfirmed($reconciliation));
        }

        return response()->json([
            'message' => 'Da xac nhan doi soat va ghi nhan doanh thu.',
            'reconciliation' => [
                'status' => AccountingReconciliation::STATUS_CONFIRMED,
                'confirmed_by' => $request->user()->name,
                'confirmed_at' => now()->format('d/m/Y H:i'),
                'recognized_revenue' => (float) ($reconciliation?->recognized_revenue ?? 0),
            ],
        ]);
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

    private function normalizePaymentText(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', mb_strtolower($value)) ?: '';
    }

    private function paymentMatchingTokens(string $value): array
    {
        preg_match_all('/[a-zA-Z0-9]{4,}/', $value, $matches);

        return collect($matches[0] ?? [])
            ->map(fn ($token) => $this->normalizePaymentText((string) $token))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function paymentMatchingCardCodeCandidates(string $value): array
    {
        return collect($this->paymentMatchingTokens($value))
            ->filter(fn (string $token) => strlen($token) >= 6 && preg_match('/\d/', $token))
            ->values()
            ->all();
    }

    private function applyTransactionToOrder(Transaction $transaction): void
    {
        if ($transaction->status !== Transaction::STATUS_APPROVED) {
            return;
        }
        if ($transaction->account_id) {
            $this->refreshAccountBalanceById((int) $transaction->account_id);
        }
        if ($transaction->destination_type === 'internal' && $transaction->destination_account_id) {
            $this->refreshAccountBalanceById((int) $transaction->destination_account_id);
        }

        if ($transaction->order_id) {
            $this->refreshOrderFinancialState($transaction->order);
        }
    }

    private function syncTransactionAccountingState(Transaction $transaction, ?int $previousAccountId = null, ?int $previousOrderId = null): void
    {
        if ($transaction->status !== Transaction::STATUS_APPROVED) {
            if ($previousOrderId && $previousOrderId !== $transaction->order_id) {
                $previousOrder = Order::query()->find($previousOrderId);
                if ($previousOrder) {
                    $this->refreshOrderFinancialState($previousOrder);
                }
            }

            return;
        }

        collect([$previousAccountId, $transaction->account_id, $transaction->destination_account_id])
            ->filter(fn ($accountId) => (int) $accountId > 0)
            ->unique()
            ->each(function ($accountId) {
                $this->refreshAccountBalanceById((int) $accountId);
            });

        collect([$previousOrderId, $transaction->order_id])
            ->filter(fn ($orderId) => (int) $orderId > 0)
            ->unique()
            ->each(function ($orderId) {
                $order = Order::query()->find((int) $orderId);
                if ($order) {
                    $this->refreshOrderFinancialState($order);
                }
            });
    }

    private function refreshAccountBalanceById(int $accountId): void
    {
        $account = Account::query()->find($accountId);
        if (!$account) {
            return;
        }

        $openingBalance = (float) ($account->opening_balance ?? 0);
        $allTransactions = Transaction::query()
            ->with('transactionCategory:id,flow_direction')
            ->where('account_id', $account->id)
            ->where('status', Transaction::STATUS_APPROVED)
            ->get(['id', 'amount', 'type', 'transaction_category_id']);

        $txnNet = (float) 0;
        foreach ($allTransactions as $txn) {
            $flowDirection = $txn->transactionCategory?->flow_direction
                ?? (in_array((string) $txn->type, ['payment', 'extra_income'], true) ? 'in' : 'out');

            if ($flowDirection === 'in') {
                $txnNet += (float) $txn->amount;
            } else {
                $txnNet -= (float) $txn->amount;
            }
        }

        $internalTransfersIn = (float) Transaction::query()
            ->where('destination_type', 'internal')
            ->where('destination_account_id', $account->id)
            ->where('status', Transaction::STATUS_APPROVED)
            ->whereHas('transactionCategory', fn ($query) => $query->where('flow_direction', 'out'))
            ->sum('amount');

        $txnNet += $internalTransfersIn;

        $account->update(['balance' => $openingBalance + $txnNet]);
    }

    private function refreshOrderFinancialState(Order $order): void
    {
        $totalPaid = (float) $order->transactions()->where('status', Transaction::STATUS_APPROVED)->where('type', 'payment')->sum('amount')
                   - (float) $order->transactions()->where('status', Transaction::STATUS_APPROVED)->where('type', 'refund')->sum('amount');

        $order->amount_paid = $totalPaid;
        $order->amount_due  = max(0, (float) $order->total - $totalPaid);
        $order->payment_status = match (true) {
            $totalPaid >= (float) $order->total => 'paid',
            $totalPaid > 0                      => 'partially_paid',
            default                             => 'unpaid',
        };

        $isFullyPaid = $totalPaid >= (float) $order->total;
        if ($isFullyPaid && !in_array((string) $order->status, ['completed', 'cancelled', 'returned', 'returned_completed'], true)) {
            $order->status = 'completed';
        }

        $order->save();
    }

    private function canAccountingConfirmOrder(Order $order): array
    {
        if (in_array((string) $order->status, ['cancelled', 'canceled', 'rejected'], true)) {
            return [false, 'Don da huy hoac bi tu choi, khong the xac nhan doanh thu.'];
        }

        if (!in_array((string) $order->status, ['delivered', 'completed'], true)) {
            return [false, 'Don chua giao thanh cong.'];
        }

        $pendingReturn = $order->returnRecords
            ->filter(fn ($return) => !in_array((string) $return->status, ['warehouse_confirmed', 'completed', 'cancelled', 'rejected'], true))
            ->first();
        if ($pendingReturn) {
            return [false, 'Don co hang tra chua duoc kho xu ly xong.'];
        }

        return [true, null];
    }

    private function returnAmountForOrder(Order $order): float
    {
        if (!$order->relationLoaded('returnRecords')) {
            $order->load('returnRecords');
        }

        return (float) $order->returnRecords
            ->whereIn('status', ['warehouse_confirmed', 'completed'])
            ->sum(fn ($return) => (float) ($return->refund_amount ?? 0));
    }

    private function effectivePaidForOrder(Order $order): float
    {
        $accountingPaid = (float) ($order->amount_paid ?? 0);
        $shipperCollected = (float) ($order->collected_amount ?? 0);

        return max($accountingPaid, $shipperCollected);
    }

    private function effectiveDueForOrder(Order $order): float
    {
        return max(0, $this->recognizedRevenueForOrder($order) - $this->effectivePaidForOrder($order));
    }

    private function recognizedRevenueForOrder(Order $order): float
    {
        $orderTotal = (float) ($order->total ?? 0);

        return max(0, $orderTotal - $this->returnAmountForOrder($order));
    }

    private function createCommissionForCompletedOrder(Order $order, ?float $recognizedRevenue = null, ?int $confirmedBy = null): void
    {
        if (!Schema::hasTable('order_commissions')) {
            return;
        }

        $saleUserId = (int) ($order->user_id ?? 0);
        if ($saleUserId <= 0) {
            return;
        }

        $snapshotPercent = (float) ($order->commission_percent_snapshot ?? 0);
        if ($snapshotPercent <= 0 && $order->customer_id) {
            $snapshotPercent = (float) Customer::query()
                ->where('id', $order->customer_id)
                ->value('commission_percent');
        }

        $orderTotal = (float) ($recognizedRevenue ?? $order->total ?? 0);
        $commissionAmount = round(($orderTotal * $snapshotPercent) / 100, 2);

        DB::table('order_commissions')->updateOrInsert(
            ['order_id' => $order->id],
            [
                'sale_user_id' => $saleUserId,
                'customer_id' => $order->customer_id,
                'order_total' => $orderTotal,
                'commission_percent' => $snapshotPercent,
                'commission_amount' => $commissionAmount,
                'status' => 'confirmed',
                'confirmed_by' => $confirmedBy ?: auth()->id(),
                'confirmed_at' => now(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $order->forceFill([
            'commission_percent_snapshot' => $snapshotPercent,
            'commission_amount_snapshot' => $commissionAmount,
            'commission_created_at' => now(),
        ])->save();
    }

    public function apiReconcileAccountBalances(Request $request)
    {
        $accountId = $request->input('account_id');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $accountsToReconcile = $accountId
            ? Account::query()->where('id', $accountId)->get()
            : Account::query()->where('is_active', true)->get();

        $reconciliationResults = [];
        $totalUpdated = 0;
        $totalAmount = 0;

        DB::transaction(function () use (
            $accountsToReconcile,
            &$reconciliationResults,
            &$totalUpdated,
            &$totalAmount,
            $accountId,
            $fromDate,
            $toDate
        ) {
            foreach ($accountsToReconcile as $account) {
                $oldBalance = (float) $account->balance;
                // opening_balance is the anchor: initial + manual deposits/withdrawals (NOT from transactions)
                $openingBalance = (float) ($account->opening_balance ?? 0);

                // Calculate transaction net from ALL approved transactions (no date filter)
                $allTransactions = Transaction::query()
                    ->with('transactionCategory:id,flow_direction')
                    ->where('account_id', $account->id)
                    ->where('status', Transaction::STATUS_APPROVED)
                    ->get(['id', 'amount', 'type', 'transaction_category_id']);

                $txnNet = (float) 0;
                foreach ($allTransactions as $txn) {
                    $flowDirection = $txn->transactionCategory?->flow_direction
                        ?? (in_array((string) $txn->type, ['payment', 'extra_income'], true) ? 'in' : 'out');

                    if ($flowDirection === 'in') {
                        $txnNet += (float) $txn->amount;
                    } else {
                        $txnNet -= (float) $txn->amount;
                    }
                }

                // Correct balance = opening balance + net of all approved transactions
                $calculatedBalance = $openingBalance + $txnNet;

                $difference = $calculatedBalance - $oldBalance;
                $hasDiscrepancy = abs($difference) > 0.01; // Allow 1 cent tolerance

                if ($hasDiscrepancy) {
                    $account->update(['balance' => $calculatedBalance]);
                    $totalUpdated++;
                    $totalAmount += abs($difference);
                }

                $reconciliationResults[] = [
                    'account_id' => $account->id,
                    'account_name' => $account->name,
                    'account_type' => $account->type,
                    'opening_balance' => $openingBalance,
                    'txn_net' => $txnNet,
                    'old_balance' => $oldBalance,
                    'calculated_balance' => $calculatedBalance,
                    'difference' => $difference,
                    'transaction_count' => $allTransactions->count(),
                    'updated' => $hasDiscrepancy,
                ];
            }

            AccountBalanceRefreshLog::create([
                'refreshed_by' => auth()->id(),
                'filter_account_id' => $accountId ?: null,
                'from_date' => $fromDate ?: null,
                'to_date' => $toDate ?: null,
                'accounts_reconciled' => count($accountsToReconcile),
                'accounts_updated' => $totalUpdated,
                'total_amount_adjusted' => $totalAmount,
                'results_json' => $reconciliationResults,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => "Da kiem tra va cap nhat " . $totalUpdated . " tai khoan. Tong sai khac: " . number_format($totalAmount) . "d",
            'accounts_reconciled' => count($accountsToReconcile),
            'accounts_updated' => $totalUpdated,
            'total_amount_adjusted' => $totalAmount,
            'results' => $reconciliationResults,
        ]);
    }

    public function refreshHistory(Request $request)
    {
        $accountId = (int) $request->input('account_id', 0);
        $fromDate = (string) $request->input('from_date', '');
        $toDate = (string) $request->input('to_date', '');

        $query = AccountBalanceRefreshLog::query()
            ->with([
                'performer:id,name',
                'filterAccount:id,name,type',
            ])
            ->latest();

        if ($accountId > 0) {
            $query->where('filter_account_id', $accountId);
        }

        if ($fromDate !== '') {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate !== '') {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $runs = $query->paginate(20)->appends($request->query());
        $accounts = Account::active()->orderBy('name')->get(['id', 'name', 'type']);

        return view('accounting.refresh_history', [
            'runs' => $runs,
            'accounts' => $accounts,
            'accountId' => $accountId,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }
}
