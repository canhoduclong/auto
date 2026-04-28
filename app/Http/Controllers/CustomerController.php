<?php
namespace App\Http\Controllers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Order;
use App\Models\Province;
use App\Models\Transaction;
use App\Models\TruckStation;
use App\Models\User;
use App\Models\Ward;
use App\Services\AdminActivityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CustomerImport;
use App\Exports\CustomerExport;


class CustomerController extends Controller
{
    private static ?array $orderColumnsCache = null;

    private function salesUsersQuery()
    {
        return User::query()
            ->whereHas('roles', function ($query) {
                $query->whereIn(DB::raw('LOWER(name)'), ['sale', 'leader', 'leader_sale', 'sale_manager']);
            })
            ->orderBy('name');
    }

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
        $query = Customer::with(['type', 'addresses', 'assignedTo', 'user']);
        $isAdmin = (bool) Auth::user()?->isAdmin();

        // Lọc theo loại
        if ($request->filled('type_id')) {
            $query->where('customer_type_id', $request->type_id);
        }

        // Lọc theo user (chỉ admin)
        if ($isAdmin && $request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Lọc theo người tạo (chỉ admin)
        if ($isAdmin && $request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Mặc định không hiển thị khách hàng là nhân viên.
        // Chỉ khi bật bộ lọc is_employee=1 mới hiển thị nhóm nhân viên.
        if ($request->boolean('is_employee')) {
            $query->where('is_employee', true);
        } else {
            $query->where('is_employee', false);
        }

        if ($isAdmin && !$request->boolean('is_employee') && $request->filled('ownership_status')) {
            if ($request->ownership_status === 'free') {
                $query->free();
            }

            if ($request->ownership_status === 'managed') {
                $query->managed();
            }
        }

        // Sale chỉ xem khách hàng còn thuộc quyền quản lý của mình
        if (!$isAdmin) {
            $query->visibleTo(Auth::user());
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
        $creatorUsers = null;
        if ($isAdmin) {
            $users = $this->salesUsersQuery()->get(['id', 'name']);

            $creatorUsers = User::query()
                ->whereIn('id', Customer::query()->select('user_id')->whereNotNull('user_id')->distinct())
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        $customerFreeDays = Customer::freeCustomerDays();

        return view('customers.index', compact('customers', 'types', 'users', 'creatorUsers', 'customerFreeDays'));
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
        $this->authorize('create', Customer::class);
        $types = CustomerType::orderBy('name')->get(['id', 'name']);
        $provinces = Province::query()->orderBy('name')->get(['id', 'name']);
        $truckStations = TruckStation::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'province_id', 'ward_id']);

        return view('customers.create', compact('types', 'provinces', 'truckStations'));
    }

    // Store
    public function store(Request $request)
    {
        $this->authorize('create', Customer::class);

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
            'company_name' => 'nullable|string|max:255',
            'tax_code' => 'nullable|string|max:50',
            'company_address' => 'nullable|string|max:255',
            'company_email' => 'nullable|email|max:255',
            'foam_box_required' => 'nullable|boolean',
            'foam_box_price' => 'nullable|integer',
            'use_truck_station' => 'nullable|boolean',
            'truck_station_id' => 'nullable|exists:truck_stations,id',
            'truck_station_address' => 'nullable|string|max:255',
            'truck_receive_time' => 'nullable|string|max:255',
            'truck_return_time' => 'nullable|string|max:255',
            'truck_return_address' => 'nullable|string|max:255',
            'truck_invoice_image' => 'nullable|string|max:255',
            'truck_delivery_image' => 'nullable|string|max:255',
            'truck_station_phone' => 'nullable|string|max:30',
            'truck_fee' => 'nullable|integer',
            'province_id' => 'nullable|exists:provinces,id',
            'ward_id' => 'nullable|exists:wards,id',
        ]);

        if (!(bool) ($data['use_truck_station'] ?? false)) {
            $data['truck_station_id'] = null;
        }

        if (!empty($data['ward_id']) && !empty($data['province_id'])) {
            $wardBelongsToProvince = Ward::query()
                ->whereKey($data['ward_id'])
                ->where('province_id', $data['province_id'])
                ->exists();

            if (!$wardBelongsToProvince) {
                return back()->withErrors([
                    'ward_id' => 'Phường/Xã không thuộc Tỉnh/Thành đã chọn.',
                ])->withInput();
            }
        }

        $selectedProvinceId = $data['province_id'] ?? null;
        $selectedWardId = $data['ward_id'] ?? null;
        unset($data['province_id'], $data['ward_id']);

        // Sale tạo khách thì mặc định giữ khách. Admin tạo khách thì để trạng thái tự do.
        $data['user_id'] = Auth::id();
        if (Auth::user()?->isAdmin()) {
            $data['assigned_to'] = null;
        } elseif (empty($data['assigned_to'])) {
            $data['assigned_to'] = Auth::id();
        }
        $data['assigned_at'] = $data['assigned_to'] ? now() : null;

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

        if ($request->filled('address') || $selectedProvinceId || $selectedWardId) {
            $province = $selectedProvinceId ? Province::find($selectedProvinceId) : null;
            $ward = $selectedWardId ? Ward::find($selectedWardId) : null;

            $customer->addresses()->create([
                'city' => $province?->name,
                'ward' => $ward?->name,
                'province_id' => $province?->id,
                'ward_id' => $ward?->id,
                'note' => $request->address,
                'is_default' => 1,
            ]);
        }

        return redirect()->route('customers.index')->with('success', __('customers.messages.created'));
    }

    // Form edit
    public function edit(Customer $customer)
    {
        $this->authorize('update', $customer);
        $types = CustomerType::orderBy('name')->get(['id', 'name']);
        $provinces = Province::query()->orderBy('name')->get(['id', 'name']);
        $truckStations = TruckStation::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'province_id', 'ward_id']);
        $customer->load('addresses');

        return view('customers.edit', compact('customer', 'types', 'provinces', 'truckStations'));
    }

    // Update
    public function update(Request $request, Customer $customer)
    {
        $this->authorize('update', $customer);

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
            'company_name' => 'nullable|string|max:255',
            'tax_code' => 'nullable|string|max:50',
            'company_address' => 'nullable|string|max:255',
            'company_email' => 'nullable|email|max:255',
            'foam_box_required' => 'nullable|boolean',
            'foam_box_price' => 'nullable|integer',
            'use_truck_station' => 'nullable|boolean',
            'truck_station_id' => 'nullable|exists:truck_stations,id',
            'truck_station_address' => 'nullable|string|max:255',
            'truck_receive_time' => 'nullable|string|max:255',
            'truck_return_time' => 'nullable|string|max:255',
            'truck_return_address' => 'nullable|string|max:255',
            'truck_invoice_image' => 'nullable|string|max:255',
            'truck_delivery_image' => 'nullable|string|max:255',
            'truck_station_phone' => 'nullable|string|max:30',
            'truck_fee' => 'nullable|integer',
            'province_id' => 'nullable|exists:provinces,id',
            'ward_id' => 'nullable|exists:wards,id',
        ]);

        if (!(bool) ($data['use_truck_station'] ?? false)) {
            $data['truck_station_id'] = null;
        }

        if (!empty($data['ward_id']) && !empty($data['province_id'])) {
            $wardBelongsToProvince = Ward::query()
                ->whereKey($data['ward_id'])
                ->where('province_id', $data['province_id'])
                ->exists();

            if (!$wardBelongsToProvince) {
                return back()->withErrors([
                    'ward_id' => 'Phường/Xã không thuộc Tỉnh/Thành đã chọn.',
                ])->withInput();
            }
        }

        $selectedProvinceId = $data['province_id'] ?? null;
        $selectedWardId = $data['ward_id'] ?? null;
        unset($data['province_id'], $data['ward_id']);

        $customer->update($data);

        if ($request->filled('address') || $selectedProvinceId || $selectedWardId) {
            $province = $selectedProvinceId ? Province::find($selectedProvinceId) : null;
            $ward = $selectedWardId ? Ward::find($selectedWardId) : null;

            $customer->addresses()->updateOrCreate(
                ['is_default' => 1],
                [
                    'note' => $request->address,
                    'city' => $province?->name,
                    'ward' => $ward?->name,
                    'province_id' => $province?->id,
                    'ward_id' => $ward?->id,
                ]
            );
        }

        return redirect()->route('customers.index')->with('success', __('customers.messages.updated'));
    }

    // Delete
    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);
        $customer->delete();
        return redirect()->route('customers.index')->with('success', __('customers.messages.deleted'));
    }

    public function assignSale(Request $request, Customer $customer)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if ($customer->is_employee) {
            return back()->withErrors([
                'assigned_to' => __('customers.messages.employee_cannot_assign_sale'),
            ]);
        }

        $validated = $request->validate([
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        $assignedTo = $validated['assigned_to'] ?? null;

        if ($assignedTo !== null) {
            $allowedSaleIds = $this->salesUsersQuery()->pluck('id')->all();
            if (!in_array((int) $assignedTo, array_map('intval', $allowedSaleIds), true)) {
                return back()->withErrors([
                    'assigned_to' => 'User được chọn không thuộc nhóm sale có thể nhận khách.',
                ]);
            }
        }

        $customer->update([
            'assigned_to' => $assignedTo,
            'assigned_at' => $assignedTo ? now() : null,
        ]);

        return redirect()->route('customers.index', [
            'q' => $request->input('q'),
            'type_id' => $request->input('type_id'),
            'assigned_to' => $request->input('assigned_to'),
            'user_id' => $request->input('user_id'),
            'per_page' => $request->input('per_page'),
            'ownership_status' => $request->input('ownership_status'),
            'is_employee' => $request->input('is_employee'),
            'page' => $request->input('page'),
        ])
            ->with('success', $assignedTo ? 'Đã gán khách hàng cho sale.' : 'Đã chuyển khách hàng về trạng thái tự do.');
    }

    public function bulkMarkEmployee(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $request->validate([
            'ids' => 'required|string',
        ]);

        $ids = array_values(array_filter(array_map('intval', explode(',', $request->input('ids')))));

        if (empty($ids)) {
            return redirect()->route('customers.index')->withErrors([
                'ids' => __('customers.index.choose_one_for_bulk_mark_employee'),
            ]);
        }

        Customer::query()
            ->whereIn('id', $ids)
            ->update([
                'is_employee' => true,
                'assigned_to' => null,
                'assigned_at' => null,
            ]);

        return redirect()->route('customers.index', $request->only([
            'q',
            'type_id',
            'assigned_to',
            'user_id',
            'ownership_status',
            'per_page',
            'is_employee',
            'page',
        ]))->with('success', __('customers.messages.bulk_marked_employee'));
    }

    // Bulk Delete
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids' => 'required|string',
        ]);

        $ids = explode(',', $request->input('ids'));

        $query = Customer::query()->whereIn('id', $ids);
        if (!$request->user()?->isAdmin()) {
            $query->visibleTo($request->user());
        }

        $query->delete();

        return redirect()->route('customers.index')->with('success', __('customers.messages.bulk_deleted'));
    }
}
