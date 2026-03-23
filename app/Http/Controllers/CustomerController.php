<?php
namespace App\Http\Controllers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AdminActivityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CustomerImport;
use App\Exports\CustomerExport;


class CustomerController extends Controller
{
    private static ?array $orderColumnsCache = null;

    private function orderColumns(): array
    {
        if (self::$orderColumnsCache === null) {
            self::$orderColumnsCache = Schema::getColumnListing('orders');
        }

        return self::$orderColumnsCache;
    }

    private function hasOrderColumn(string $column): bool
    {
        return in_array($column, $this->orderColumns(), true);
    }

    // Export excel
    public function export()
    {
        return Excel::download(new CustomerExport, 'customers.xlsx');
    }

    // Hiển thị form import và kết quả
    public function importForm(Request $request)
    {
        $import_failures = session('import_errors', []);
        $success = session('import_success', null);
        return view('customers.import', compact('import_failures', 'success'));
    }

    // Xử lý import excel
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);
        $import = new \App\Imports\CustomerImportWithErrorReport();
        try {
            Excel::import($import, $request->file('file'));
            $failures = $import->failures();
            if (count($failures) > 0) {
                $errors = [];
                foreach ($failures as $failure) {
                    $row = $failure->row();
                    $attr = $failure->attribute();
                    $errs = $failure->errors();
                    $values = $failure->values();
                    $errors[] = [
                        'row' => $row,
                        'attribute' => $attr,
                        'errors' => $errs,
                        'values' => $values,
                    ];
                }
                return redirect()->route('customers.import.form')->with(['import_failures' => $errors]);
            }
            return redirect()->route('customers.import.form')->with(['import_success' => __('customers.messages.import_success')]);
        } catch (\Exception $e) {
            return redirect()->route('customers.import.form')->with(['import_errors' => [['row' => '-', 'attribute' => '-', 'errors' => [$e->getMessage()], 'values' => []]]]);
        }
    }
    // List + filter + search + paginate
    use AuthorizesRequests;
    public function index(Request $request)
    { 
        $this->authorize('viewAny', Customer::class);
        $query = Customer::with(['type', 'addresses', 'assignedTo']);

        // Lọc theo loại
        if ($request->filled('type_id')) {
            $query->where('customer_type_id', $request->type_id);
        }

        // Lọc theo user (chỉ admin)
        if (Gate::allows('filter_customer_by_user') && $request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Tìm theo tên / phone / email
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

    $perPage = $request->input('per_page', 15);
    $customers = $query->orderBy('name')
               ->paginate($perPage)
               ->appends($request->query()); // giữ query string khi phân trang

        // Dùng để hiển thị dropdown lọc
        $types = CustomerType::orderByDesc('priority_level')
                             ->orderBy('name')
                             ->get(['id', 'name']);

        $users = null;
        if (Gate::allows('filter_customer_by_user')) {
            $users = User::orderBy('name')->get(['id', 'name']);
        }

        return view('customers.index', compact('customers', 'types', 'users'));
    }

    public function report(Request $request, Customer $customer)
    {
        return redirect()->route('customers.show', array_merge(
            ['customer' => $customer],
            $request->query(),
            ['tab' => 'reports']
        ));
    }

    public function show(Request $request, Customer $customer)
    {
        $this->authorize('view', $customer);

        $validated = $request->validate([
            'tab' => 'nullable|in:info,debt,orders,payments,reports',
            'period' => 'nullable|in:today,week,month,custom',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'order_status' => 'nullable|string|max:50',
            'orders_per_page' => 'nullable|integer|min:5|max:100',
            'debt_per_page' => 'nullable|integer|min:5|max:100',
            'payments_per_page' => 'nullable|integer|min:5|max:100',
        ]);

        [$fromDate, $toDate] = $this->resolveDateRange(
            (string) ($validated['period'] ?? 'month'),
            $validated['from_date'] ?? null,
            $validated['to_date'] ?? null
        );

        $customer->load(['type', 'assignedTo', 'addresses']);

        $ordersBaseQuery = Order::query()->where('customer_id', $customer->id);

        if (!empty($validated['order_status'])) {
            $ordersBaseQuery->where('status', $validated['order_status']);
        }

        $ordersBaseQuery->whereBetween('created_at', [
            $fromDate->copy()->startOfDay(),
            $toDate->copy()->endOfDay(),
        ]);

        $ordersPerPage = (int) ($validated['orders_per_page'] ?? 10);
        $debtPerPage = (int) ($validated['debt_per_page'] ?? 10);
        $paymentsPerPage = (int) ($validated['payments_per_page'] ?? 10);

        $orders = (clone $ordersBaseQuery)
            ->with(['user', 'transactions'])
            ->latest()
            ->paginate($ordersPerPage, ['*'], 'orders_page')
            ->appends($request->query());

        $filteredOrderCount = (clone $ordersBaseQuery)->count();
        $filteredOrderTotal = (float) (clone $ordersBaseQuery)->sum('total');
        $filteredSubtotalAmount = $this->hasOrderColumn('subtotal_amount')
            ? (float) (clone $ordersBaseQuery)->sum('subtotal_amount')
            : $filteredOrderTotal;
        $filteredItemDiscountTotal = $this->hasOrderColumn('item_discount_total')
            ? (float) (clone $ordersBaseQuery)->sum('item_discount_total')
            : 0.0;
        $filteredExtraDiscountTotal = $this->hasOrderColumn('extra_discount_total')
            ? (float) (clone $ordersBaseQuery)->sum('extra_discount_total')
            : (float) (clone $ordersBaseQuery)->sum('order_discount');

        $debtOrders = (clone $ordersBaseQuery)
            ->with('transactions')
            ->latest()
            ->paginate($debtPerPage, ['*'], 'debt_page')
            ->appends($request->query());

        $paymentsBaseQuery = Transaction::query()
            ->where('customer_id', $customer->id)
            ->where('type', 'payment')
            ->whereBetween('created_at', [
                $fromDate->copy()->startOfDay(),
                $toDate->copy()->endOfDay(),
            ]);

        $payments = (clone $paymentsBaseQuery)
            ->with('order')
            ->latest()
            ->paginate($paymentsPerPage, ['*'], 'payments_page')
            ->appends($request->query());

        $eventLogs = DB::table('admin_events')
            ->where('event_type', 'customer_payment')
            ->where('action', 'create')
            ->where('subject_type', $customer->getMorphClass())
            ->where('subject_id', $customer->id)
            ->orderByDesc('id')
            ->get();

        $transactionActorIds = [];
        foreach ($eventLogs as $eventLog) {
            $metadata = json_decode((string) ($eventLog->metadata ?? '{}'), true);
            if (!empty($metadata['transaction_id'])) {
                $transactionActorIds[(int) $metadata['transaction_id']] = (int) ($eventLog->actor_id ?? 0);
            }
        }

        $actorNames = [];
        if (!empty($transactionActorIds)) {
            $actorNames = User::query()
                ->whereIn('id', array_values(array_unique($transactionActorIds)))
                ->pluck('name', 'id')
                ->toArray();
        }

        $orderIdsSubQuery = (clone $ordersBaseQuery)->select('id');
        $totalOrderAmount = (float) (clone $ordersBaseQuery)->sum('total');
        $totalSubtotalAmount = $this->hasOrderColumn('subtotal_amount')
            ? (float) (clone $ordersBaseQuery)->sum('subtotal_amount')
            : $totalOrderAmount;
        $totalItemDiscountAmount = $this->hasOrderColumn('item_discount_total')
            ? (float) (clone $ordersBaseQuery)->sum('item_discount_total')
            : 0.0;
        $totalExtraDiscountAmount = $this->hasOrderColumn('extra_discount_total')
            ? (float) (clone $ordersBaseQuery)->sum('extra_discount_total')
            : (float) (clone $ordersBaseQuery)->sum('order_discount');
        $totalPaidAmount = (float) Transaction::query()
            ->whereIn('order_id', $orderIdsSubQuery)
            ->where('type', 'payment')
            ->sum('amount')
            - (float) Transaction::query()
                ->whereIn('order_id', (clone $ordersBaseQuery)->select('id'))
                ->where('type', 'refund')
                ->sum('amount');
        $totalOutstandingAmount = max($totalOrderAmount - $totalPaidAmount, 0);

        $reportOrders = Order::query()
            ->where('customer_id', $customer->id)
            ->whereBetween('created_at', [
                $fromDate->copy()->startOfDay(),
                $toDate->copy()->endOfDay(),
            ])
            ->with('transactions')
            ->orderBy('created_at')
            ->get();

        $reportByMonth = $reportOrders
            ->groupBy(fn (Order $order) => optional($order->created_at)->format('Y-m'))
            ->map(function ($monthlyOrders, $period) {
                $orderTotal = (float) $monthlyOrders->sum('total');
                $subtotalAmount = (float) $monthlyOrders->sum(function (Order $order) {
                    return (float) ($order->subtotal_amount ?? $order->total ?? 0);
                });
                $itemDiscountTotal = (float) $monthlyOrders->sum(function (Order $order) {
                    return (float) ($order->item_discount_total ?? 0);
                });
                $extraDiscountTotal = (float) $monthlyOrders->sum(function (Order $order) {
                    return (float) ($order->extra_discount_total ?? $order->order_discount ?? 0);
                });
                $paidTotal = (float) $monthlyOrders->sum(function (Order $order) {
                    return (float) $order->transactions->where('type', 'payment')->sum('amount')
                        - (float) $order->transactions->where('type', 'refund')->sum('amount');
                });

                return [
                    'period' => $period,
                    'order_count' => (int) $monthlyOrders->count(),
                    'subtotal_amount' => $subtotalAmount,
                    'item_discount_total' => $itemDiscountTotal,
                    'extra_discount_total' => $extraDiscountTotal,
                    'order_total' => $orderTotal,
                    'paid_total' => $paidTotal,
                    'outstanding_total' => max($orderTotal - $paidTotal, 0),
                ];
            })
            ->sortBy('period')
            ->values();

        $orderStatuses = (clone Order::query())
            ->select('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->filter()
            ->values();

        return view('customers.show', [
            'customer' => $customer,
            'orders' => $orders,
            'debtOrders' => $debtOrders,
            'payments' => $payments,
            'filteredOrderCount' => $filteredOrderCount,
            'filteredOrderTotal' => $filteredOrderTotal,
            'filteredSubtotalAmount' => $filteredSubtotalAmount,
            'filteredItemDiscountTotal' => $filteredItemDiscountTotal,
            'filteredExtraDiscountTotal' => $filteredExtraDiscountTotal,
            'reportByMonth' => $reportByMonth,
            'transactionActorIds' => $transactionActorIds,
            'actorNames' => $actorNames,
            'totalOrderAmount' => $totalOrderAmount,
            'totalSubtotalAmount' => $totalSubtotalAmount,
            'totalItemDiscountAmount' => $totalItemDiscountAmount,
            'totalExtraDiscountAmount' => $totalExtraDiscountAmount,
            'totalPaidAmount' => $totalPaidAmount,
            'totalOutstandingAmount' => $totalOutstandingAmount,
            'period' => (string) ($validated['period'] ?? 'month'),
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'activeTab' => (string) ($validated['tab'] ?? 'info'),
            'orderStatuses' => $orderStatuses,
        ]);
    }

    public function storePayment(Request $request, Customer $customer)
    {
        $this->authorize('update', $customer);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,bank_transfer',
            'note' => 'nullable|string|max:255',
            'receipt_image' => 'nullable|image|max:5120',
            'period' => 'nullable|in:today,week,month,custom',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'tab' => 'nullable|in:info,debt,orders,payments,reports',
            'order_status' => 'nullable|string|max:50',
            'orders_per_page' => 'nullable|integer|min:5|max:100',
            'debt_per_page' => 'nullable|integer|min:5|max:100',
            'payments_per_page' => 'nullable|integer|min:5|max:100',
        ]);

        $allOrdersQuery = Order::query()->where('customer_id', $customer->id);
        $allOrderIdsSubQuery = (clone $allOrdersQuery)->select('id');
        $totalOrderAmount = (float) (clone $allOrdersQuery)->sum('total');
        $totalPaidAmount = (float) Transaction::query()
            ->whereIn('order_id', $allOrderIdsSubQuery)
            ->where('type', 'payment')
            ->sum('amount')
            - (float) Transaction::query()
                ->whereIn('order_id', (clone $allOrdersQuery)->select('id'))
                ->where('type', 'refund')
                ->sum('amount');

        $outstandingAmount = max($totalOrderAmount - $totalPaidAmount, 0);
        $amount = (float) $validated['amount'];

        if ($amount - $outstandingAmount > 0.0001) {
            return back()
                ->withErrors(['amount' => 'Số tiền thanh toán không được vượt quá công nợ hiện tại.'])
                ->withInput();
        }

        if ($outstandingAmount <= 0) {
            return back()
                ->withErrors(['amount' => 'Khách hàng này không còn công nợ để thanh toán.'])
                ->withInput();
        }

        $receiptImagePath = $request->hasFile('receipt_image')
            ? $request->file('receipt_image')->store('customer-payments/receipts', 'public')
            : null;

        $unpaidOrders = Order::query()
            ->where('customer_id', $customer->id)
            ->with('transactions')
            ->orderBy('created_at')
            ->get();

        $createdTransactions = [];
        $remainingAmount = $amount;

        DB::transaction(function () use (
            $request,
            $validated,
            $customer,
            $receiptImagePath,
            $unpaidOrders,
            &$createdTransactions,
            &$remainingAmount
        ) {
            foreach ($unpaidOrders as $order) {
                if ($remainingAmount <= 0.0001) {
                    break;
                }

                $paid = (float) $order->transactions->where('type', 'payment')->sum('amount')
                    - (float) $order->transactions->where('type', 'refund')->sum('amount');
                $outstanding = max((float) $order->total - $paid, 0);

                if ($outstanding <= 0.0001) {
                    continue;
                }

                $allocateAmount = min($remainingAmount, $outstanding);

                $transaction = Transaction::create([
                    'order_id' => $order->id,
                    'customer_id' => $customer->id,
                    'amount' => $allocateAmount,
                    'type' => 'payment',
                    'method' => $validated['method'],
                    'note' => $validated['note'] ?? null,
                    'receipt_image_path' => $receiptImagePath,
                ]);

                $createdTransactions[] = $transaction;

                $freshPaid = (float) $order->transactions()->where('type', 'payment')->sum('amount')
                    - (float) $order->transactions()->where('type', 'refund')->sum('amount');

                if ($freshPaid >= (float) $order->total) {
                    $orderPaymentStatus = 'paid';
                } elseif ($freshPaid > 0) {
                    $orderPaymentStatus = 'partial';
                } else {
                    $orderPaymentStatus = 'unpaid';
                }

                $order->update([
                    'amount_paid' => $freshPaid,
                    'amount_due' => max((float) $order->total - $freshPaid, 0),
                    'payment_status' => $orderPaymentStatus,
                ]);

                AdminActivityService::record(
                    'customer_payment',
                    'create',
                    $customer,
                    'Tạo thanh toán khách hàng',
                    'Đã tạo thanh toán cho đơn #' . ($order->code ?: $order->id),
                    [
                        'transaction_id' => $transaction->id,
                        'order_id' => $order->id,
                        'customer_id' => $customer->id,
                        'amount' => $allocateAmount,
                        'method' => $validated['method'],
                    ],
                    route('customers.show', ['customer' => $customer, 'tab' => 'payments'])
                );

                $remainingAmount -= $allocateAmount;
            }
        });

        if (empty($createdTransactions)) {
            return back()
                ->withErrors(['amount' => 'Không tìm thấy đơn hàng còn nợ để phân bổ thanh toán.'])
                ->withInput();
        }

        return redirect()
            ->route('customers.show', [
                'customer' => $customer,
                'tab' => 'payments',
                'period' => $validated['period'] ?? $request->input('period', 'month'),
                'from_date' => $validated['from_date'] ?? $request->input('from_date'),
                'to_date' => $validated['to_date'] ?? $request->input('to_date'),
                'order_status' => $validated['order_status'] ?? $request->input('order_status'),
                'orders_per_page' => $validated['orders_per_page'] ?? $request->input('orders_per_page', 10),
                'debt_per_page' => $validated['debt_per_page'] ?? $request->input('debt_per_page', 10),
                'payments_per_page' => $validated['payments_per_page'] ?? $request->input('payments_per_page', 10),
            ])
            ->with('success', 'Đã ghi nhận thanh toán thành công.');
    }

    private function resolveDateRange(string $period, ?string $fromDateInput, ?string $toDateInput): array
    {
        $today = Carbon::today();

        if ($period === 'today') {
            return [$today->copy(), $today->copy()];
        }

        if ($period === 'week') {
            return [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()];
        }

        if ($period === 'custom') {
            $fromDate = $fromDateInput ? Carbon::parse($fromDateInput) : $today->copy()->startOfMonth();
            $toDate = $toDateInput ? Carbon::parse($toDateInput) : $today->copy()->endOfMonth();

            if ($fromDate->greaterThan($toDate)) {
                [$fromDate, $toDate] = [$toDate, $fromDate];
            }

            return [$fromDate, $toDate];
        }

        return [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()];
    }

    // Form create
    public function create()
    {
        $types = CustomerType::orderBy('name')->get(['id', 'name']);
        return view('customers.create', compact('types'));
    }

    // Store
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email',
            'website' => 'nullable|url|max:255',
            'gender' => 'nullable|in:male,female,other',
            'dob' => 'nullable|date',
            'customer_type_id' => 'nullable|exists:customer_types,id',
            'note' => 'nullable|string|max:2000',
            'address' => 'nullable|string|max:1000',
            'delivery_time' => 'nullable|string|max:255',
            'foam_box_required' => 'nullable|boolean',
            'foam_box_price' => 'nullable|integer',
            'use_truck_station' => 'nullable|boolean',
            'truck_station_address' => 'nullable|string|max:255',
            'truck_receive_time' => 'nullable|string|max:255',
            'truck_return_time' => 'nullable|string|max:255',
            'truck_return_address' => 'nullable|string|max:255',
            'truck_invoice_image' => 'nullable|string|max:255',
            'truck_delivery_image' => 'nullable|string|max:255',
            'truck_station_phone' => 'nullable|string|max:30',
            'truck_fee' => 'nullable|integer',
        ]);

        $duplicateCustomer = Customer::query()
            ->where(function ($query) use ($data) {
                if (!empty($data['email'])) {
                    $query->orWhereRaw('LOWER(email) = ?', [strtolower($data['email'])]);
                }

                if (!empty($data['phone'])) {
                    $query->orWhere('phone', $data['phone']);
                    $query->orWhere(function ($subQuery) use ($data) {
                        $subQuery->where('name', $data['name'])
                            ->where('phone', $data['phone']);
                    });
                }
            })
            ->first();

        if ($duplicateCustomer) {
            return back()
                ->withInput()
                ->with('error', __('customers.messages.duplicate', [
                    'id' => $duplicateCustomer->id,
                    'name' => $duplicateCustomer->name,
                ]));
        }

        $customer = Customer::create($data);

        if ($request->filled('address')) {
            $customer->addresses()->create([
                'note' => $request->address,
                'is_default' => 1,
            ]);
        }

        return redirect()->route('customers.index')->with('success', __('customers.messages.created'));
    }

    // Form edit
    public function edit(Customer $customer)
    {
        $types = CustomerType::orderBy('name')->get(['id', 'name']);
        return view('customers.edit', compact('customer', 'types'));
    }

    // Update
    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:30|unique:customers,phone,' . $customer->id,
            'email' => 'nullable|email|unique:customers,email,' . $customer->id,
            'website' => 'nullable|url|max:255',
            'gender' => 'nullable|in:male,female,other',
            'dob' => 'nullable|date',
            'customer_type_id' => 'nullable|exists:customer_types,id',
            'note' => 'nullable|string|max:2000',
            'address' => 'nullable|string|max:1000',
            'delivery_time' => 'nullable|string|max:255',
            'foam_box_required' => 'nullable|boolean',
            'foam_box_price' => 'nullable|integer',
            'use_truck_station' => 'nullable|boolean',
            'truck_station_address' => 'nullable|string|max:255',
            'truck_receive_time' => 'nullable|string|max:255',
            'truck_return_time' => 'nullable|string|max:255',
            'truck_return_address' => 'nullable|string|max:255',
            'truck_invoice_image' => 'nullable|string|max:255',
            'truck_delivery_image' => 'nullable|string|max:255',
            'truck_station_phone' => 'nullable|string|max:30',
            'truck_fee' => 'nullable|integer',
        ]);
        $customer->update($data);

        if ($request->filled('address')) {
            $customer->addresses()->updateOrCreate(
                ['is_default' => 1],
                ['note' => $request->address]
            );
        }

        return redirect()->route('customers.index')->with('success', __('customers.messages.updated'));
    }

    // Delete
    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')->with('success', __('customers.messages.deleted'));
    }

    // Bulk Delete
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|string',
        ]);

        $ids = explode(',', $request->input('ids'));

        Customer::whereIn('id', $ids)->delete();

        return redirect()->route('customers.index')->with('success', __('customers.messages.bulk_deleted'));
    }
}
