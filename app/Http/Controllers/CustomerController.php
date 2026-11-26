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
use App\Services\CustomerDebtPolicyService;
use App\Services\CustomerPriorityService;
use App\Services\CustomerTextImportService;
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
            self::$orderColumnsCache = Schema::hasTable('orders')
                ? Schema::getColumnListing('orders')
                : [];
        }

        return self::$orderColumnsCache;
    }

    private function hasOrderColumn(string $column): bool
    {
        return in_array($column, $this->orderColumns(), true);
    }


    /**
     * Gán nhiều khách hàng cho 1 sale
     */
    public function bulkAssignSale(Request $request, CustomerPriorityService $priorityService)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $request->validate([
            'ids' => 'required|string',
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
        ]);

        $ids = array_values(array_filter(array_map('intval', explode(',', $request->input('ids')))));
        $assignedTo = (int) $request->input('assigned_to');

        if (empty($ids)) {
            return redirect()->route('customers.index')->withErrors([
                'ids' => 'Vui lòng chọn ít nhất 1 khách hàng để gán sale!',
            ]);
        }

        $allowedSaleIds = $this->salesUsersQuery()->pluck('id')->all();
        if (!in_array($assignedTo, array_map('intval', $allowedSaleIds), true)) {
            return redirect()->route('customers.index')->withErrors([
                'assigned_to' => 'User được chọn không thuộc nhóm sale có thể nhận khách.',
            ]);
        }

        $customers = Customer::query()->whereIn('id', $ids)->get();
        $count = 0;
        foreach ($customers as $customer) {
            if ($customer->is_employee) continue;
            $priorityService->takeover($customer, $assignedTo, 'bulk_assign');
            $count++;
        }

        return redirect()->route('customers.index')->with('success', "Đã gán $count khách hàng cho sale.");
    }

    public function bulkUpdateCommission(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'ids' => ['required', 'string'],
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'recalculate_existing' => ['nullable', 'boolean'],
        ], [
            'ids.required' => 'Vui lòng chọn ít nhất một khách hàng.',
            'commission_percent.required' => 'Vui lòng nhập mức hoa hồng sale.',
        ]);

        $customerIds = collect(explode(',', $validated['ids']))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();
        if ($customerIds->isEmpty()) {
            return back()->withErrors(['ids' => 'Vui lòng chọn ít nhất một khách hàng.']);
        }

        $percent = round((float) $validated['commission_percent'], 2);
        $recalculate = (bool) ($validated['recalculate_existing'] ?? false);
        $updatedCustomers = 0;
        $updatedOrders = 0;

        DB::transaction(function () use ($customerIds, $percent, $recalculate, &$updatedCustomers, &$updatedOrders): void {
            $updatedCustomers = Customer::query()
                ->whereIn('id', $customerIds)
                ->update(['commission_percent' => $percent, 'updated_at' => now()]);

            if (Schema::hasTable('accounting_customer_commissions')) {
                $now = now();
                DB::table('accounting_customer_commissions')->insert(
                    $customerIds->map(fn (int $customerId) => [
                        'customer_id' => $customerId,
                        'type' => 'percent',
                        'value' => $percent,
                        'effective_date' => $now->toDateString(),
                        'note' => 'Cập nhật hàng loạt từ danh sách khách hàng',
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            }

            if (! $recalculate || ! Schema::hasTable('order_commissions')) {
                return;
            }

            $commissions = DB::table('order_commissions')
                ->whereIn('customer_id', $customerIds)
                ->get(['id', 'order_id', 'order_total']);
            $updatedOrders = $commissions->count();

            foreach ($commissions as $commission) {
                $amount = round((float) $commission->order_total * $percent / 100, 2);
                DB::table('order_commissions')->where('id', $commission->id)->update([
                    'commission_percent' => $percent,
                    'commission_amount' => $amount,
                    'updated_at' => now(),
                ]);
                Order::query()->whereKey($commission->order_id)->update([
                    'commission_percent_snapshot' => $percent,
                    'commission_amount_snapshot' => $amount,
                    'commission_created_at' => DB::raw('COALESCE(commission_created_at, NOW())'),
                ]);
            }
        });

        $message = 'Đã gán hoa hồng '.number_format($percent, 2, ',', '.').'% cho '.$updatedCustomers.' khách hàng.';
        if ($recalculate) {
            $message .= ' Đã tính lại '.$updatedOrders.' đơn hàng.';
        }

        return back()->with('success', $message);
    }

    // Export excel
    public function export(Request $request)
    {
        if ($request->boolean('export_choices') && $request->isMethod('get')) {
            return response()->json($this->filteredCustomerList($request)->orderBy('name')->get(['id', 'name']))
                ->header('Cache-Control', 'private, no-store');
        }

        if ($request->isMethod('post')) {
            $payload = $request->validate(['selection' => 'required|string|json'])['selection'];
            $ids = validator(['ids' => json_decode($payload, true)], [
                'ids' => 'required|array|list|min:1',
                'ids.*' => 'required|integer|min:1|distinct',
            ])->validate()['ids'];
            $customers = $this->visibleCustomerList($request)->whereIntegerInRaw('customers.id', $ids);
            if (!$customers->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['selection' => 'Danh sách xuất không có khách hàng bạn được phép xem.']);
            }
        } else {
            $customers = $this->visibleCustomerList($request);
        }

        return Excel::download(new CustomerExport($customers), 'customers.xlsx');
    }

    // Hiển thị form import và kết quả
    public function importForm(Request $request)
    {
        $import_failures = session('import_errors', []);
        $success = session('import_success', null);
        $textImportResult = session('text_import_result');
        $textData = old('text_data', session('text_import_data', ''));
        $salesUsers = $request->user()?->isAdmin() ? $this->salesUsersQuery()->get(['id', 'name', 'short_name', 'email']) : collect();
        return view('customers.import', compact('import_failures', 'success', 'textImportResult', 'textData', 'salesUsers'));
    }

    // Xử lý import excel
    public function import(Request $request, CustomerTextImportService $textImporter)
    {
        if ($request->filled('text_data') || $request->input('import_source') === 'text') {
            $validated = $request->validate([
                'text_data' => ['required', 'string', 'max:200000'],
                'text_action' => ['required', Rule::in(['preview', 'import'])],
                'sale_mapping' => ['nullable', 'array'],
                'sale_mapping.*' => ['nullable', 'integer', 'exists:users,id'],
            ]);

            try {
                $saleMappings = $validated['sale_mapping'] ?? [];
                $result = $validated['text_action'] === 'import'
                    ? $textImporter->import($validated['text_data'], $request->user(), $saleMappings)
                    : $textImporter->preview($validated['text_data'], $request->user(), $saleMappings);

                if ($validated['text_action'] === 'import' && ($result['imported'] ?? false)) {
                    $created = $result['counts']['create'] ?? 0;
                    $updated = $result['counts']['update'] ?? 0;
                    $skipped = $result['counts']['skip'] ?? 0;
                    return redirect()->route('customers.import.form')->with([
                        'import_success' => "Đã import {$created} khách mới, bổ sung {$updated} khách hiện có; bỏ qua {$skipped} dòng không thay đổi.",
                        'text_import_result' => $result,
                    ]);
                }

                return view('customers.import', [
                    'import_failures' => [],
                    'success' => null,
                    'textImportResult' => $result,
                    'textData' => $validated['text_data'],
                    'salesUsers' => $request->user()?->isAdmin() ? $this->salesUsersQuery()->get(['id', 'name', 'short_name', 'email']) : collect(),
                ]);
            } catch (\InvalidArgumentException $e) {
                return back()->withInput()->withErrors(['text_data' => $e->getMessage()]);
            }
        }

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
    private function visibleCustomerList(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        return Customer::query()->when(! $request->user()->isAdmin(), fn ($query) => $query->visibleTo($request->user()));
    }

    private function filteredCustomerList(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = $this->visibleCustomerList($request);
        if ($request->input('order_status') === 'has_orders') {
            $query->has('latestValidCustomerOrder');
        } elseif ($request->input('order_status') === 'no_orders') {
            $query->doesntHave('latestValidCustomerOrder');
        }
        $isAdmin = $request->user()->isAdmin();

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

        // Tìm theo tên / phone / email
        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Customer::class);
        $salesStatuses = [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED];
        $query = $this->filteredCustomerList($request)->with(['type', 'addresses', 'assignedTo', 'user', 'lastOrder', 'latestValidCustomerOrder'])
            ->withSum(['orders as sales_revenue' => fn ($orders) => $orders->whereNull('trash_at')
                ->whereIn('status', $salesStatuses)], 'total');
        $isAdmin = $request->user()->isAdmin();
        $perPage = $request->input('per_page', 15);
        if (Schema::hasColumn('customers', 'is_pinned')) {
            $query->orderByDesc('is_pinned');
        }
        if (Schema::hasColumn('customers', 'sort_order')) {
            $query->orderByRaw('CASE WHEN sort_order IS NULL OR sort_order = 0 THEN 1 ELSE 0 END')
                ->orderBy('sort_order');
        }
        $customers = $query->orderBy('name')
                   ->paginate($perPage)
                   ->appends($request->query()); // giữ query string khi phân trang

        $topProducts = DB::table('order_items as items')
            ->join('orders', 'orders.id', '=', 'items.order_id')
            ->leftJoin('product_variants as variants', 'variants.id', '=', 'items.product_variant_id')
            ->join('products', fn ($join) => $join->on('products.id', '=', DB::raw('COALESCE(items.product_id, variants.product_id)')))
            ->whereIn('orders.customer_id', $customers->getCollection()->modelKeys())
            ->whereNull('orders.trash_at')
            ->whereIn('orders.status', $salesStatuses)
            ->where('items.quantity', '>', 0)
            ->select('orders.customer_id', 'products.id', 'products.name')
            ->selectRaw('SUM(items.quantity) as ordered_quantity')
            ->groupBy('orders.customer_id', 'products.id', 'products.name')
            ->orderByDesc('ordered_quantity')->orderBy('products.id')
            ->get()->unique('customer_id')->keyBy('customer_id');

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

        return view('customers.index', compact('customers', 'types', 'users', 'creatorUsers', 'customerFreeDays', 'topProducts'));
    }

    public function updateSortSettings(Request $request, Customer $customer)
    {
        $this->authorize('update', $customer);

        $validated = $request->validate([
            'is_pinned' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);

        $updates = [];
        if (array_key_exists('is_pinned', $validated) && Schema::hasColumn('customers', 'is_pinned')) {
            $updates['is_pinned'] = (bool) $validated['is_pinned'];
        }
        if (array_key_exists('sort_order', $validated) && Schema::hasColumn('customers', 'sort_order')) {
            $updates['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        }

        if ($updates) {
            $customer->forceFill($updates)->save();
        }

        return response()->json([
            'ok' => true,
            'is_pinned' => (bool) ($customer->fresh()->is_pinned ?? false),
            'sort_order' => (int) ($customer->fresh()->sort_order ?? 0),
        ]);
    }

    public function report(Request $request, Customer $customer)
    {
        return redirect()->route('customers.show', array_merge(
            ['customer' => $customer],
            $request->query(),
            ['tab' => 'reports']
        ));
    }

    public function show(Request $request, Customer $customer, CustomerDebtPolicyService $debtPolicies)
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

        $activeTab = $validated['tab'] ?? 'info';
        $debtPolicy = in_array($activeTab, ['debt', 'payments'], true)
            ? $debtPolicies->effective(['customer_id' => $customer->id], $debtPolicies->policies())
            : null;

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
            'activeTab' => $activeTab,
            'debtPolicy' => $debtPolicy,
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

        $unpaidOrders = Order::query()
            ->where('customer_id', $customer->id)
            ->with('transactions')
            ->orderBy('created_at')
            ->get();

        $policies = app(CustomerDebtPolicyService::class);
        $settlementMethod = $policies->effective(['customer_id' => $customer->id], $policies->policies())['settlement_method'];
        $specificBills = false;
        if ($settlementMethod !== 'batch') {
            $matcher = app(\App\Services\CustomerDebtPaymentAllocation::class);
            $note = $validated['note'] ?? '';
            $references = $matcher->references($note);
            if (! ($settlementMethod === 'mixed' && $references === [] && $matcher->hasBatchReference($note))) {
                if ($references === []) {
                    return back()->withErrors(['note' => 'Ghi ngày giao và mã đơn theo mẫu Bill 13/11/2026 | Mã đơn: DH123.'])->withInput();
                }
                $ordersByReference = $unpaidOrders->groupBy(fn (Order $order) =>
                    substr((string) $order->getRawOriginal('delivery_date'), 0, 10).'|'.mb_strtoupper(trim((string) $order->code)));
                $matched = collect($references)->map(fn ($reference) => $ordersByReference->get($reference));
                if ($matched->contains(fn ($orders) => $orders?->count() !== 1)) {
                    return back()->withErrors(['note' => 'Ngày giao hoặc mã đơn không khớp với đơn của khách.'])->withInput();
                }
                $unpaidOrders = $matched->map(fn ($orders) => $orders->first());
                $specificBills = true;
            }
        }

        $createdTransactions = [];
        $remainingAmount = $amount;

        DB::transaction(function () use (
            $request,
            $validated,
            $customer,
            $specificBills,
            $unpaidOrders,
            &$createdTransactions,
            &$remainingAmount
        ) {
            if ($specificBills) {
                $ids = $unpaidOrders->pluck('id')->all();
                $locked = Order::query()->where('customer_id', $customer->id)->whereKey($ids)
                    ->orderBy('id')->lockForUpdate()->with('transactions')->get()->keyBy('id');
                $unpaidOrders = collect($ids)->map(fn ($id) => $locked->get($id))->filter();
                $billOutstanding = $unpaidOrders->sum(fn ($order) => max(0, (float) $order->total
                    - $order->transactions->where('type', 'payment')->sum('amount')
                    + $order->transactions->where('type', 'refund')->sum('amount')));
                if ($unpaidOrders->count() !== count($ids) || $remainingAmount - $billOutstanding > 0.0001) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'amount' => 'Số tiền vượt công nợ còn lại hoặc các Bill đã thay đổi. Vui lòng đối chiếu lại.',
                    ]);
                }
            }
            $receiptImagePath = $request->hasFile('receipt_image')
                ? $request->file('receipt_image')->store('customer-payments/receipts', 'public')
                : null;

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
    public function store(Request $request, CustomerPriorityService $priorityService)
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
            'commission_percent' => 'nullable|numeric|min:0|max:100',
            'legacy_code' => 'nullable|string|max:50',
            'old_name' => 'nullable|string|max:5000',
            'special_pickup_enabled' => 'sometimes|boolean',
            'note' => 'nullable|string|max:2000',
            'address' => 'nullable|string|max:1000',
            'delivery_time' => 'required|date_format:H:i',
            'delivery_time_note' => 'nullable|string|max:1000',
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
            'duplicate_priority_level' => 'nullable|in:2,3',
            'duplicate_takeover' => 'nullable|boolean',
        ]);
        if (!$request->user()->isAdmin()) unset($data['special_pickup_enabled']);


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
        $data['current_owner_sale_id'] = $data['assigned_to'] ?? null;
        $data['customer_status'] = $data['assigned_to'] ? 'active' : 'free';
        $data['free_from_date'] = $data['assigned_to'] ? null : now();
        $data['current_cycle_no'] = 1;

        $duplicateCustomer = Customer::query()
            ->where(function ($query) use ($data) {
                $query->where('name_normalized', Customer::normalizeName($data['name']));

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
            $preferredPriority = isset($data['duplicate_priority_level'])
                ? (int) $data['duplicate_priority_level']
                : null;
            $takeover = (bool) ($data['duplicate_takeover'] ?? false);

            if (!Auth::user()?->isAdmin()) {
                if ($takeover || $duplicateCustomer->isFree()) {
                    $priorityService->takeover($duplicateCustomer, (int) Auth::id(), $takeover ? 'takeover' : 'free_customer');
                } else {
                    $priorityService->attachSale($duplicateCustomer, (int) Auth::id(), $preferredPriority, 'duplicate_join');
                }

                return redirect()
                    ->route('customers.index')
                    ->with('success', 'Khách đã tồn tại, đã ghi nhận tham gia chăm sóc vào danh sách priority.');
            }

            return back()
                ->withInput()
                ->with('error', __('customers.messages.duplicate', [
                    'id' => $duplicateCustomer->id,
                    'name' => $duplicateCustomer->name,
                ]));
        }


        $customer = Customer::create($data);

        // Luôn gán priority = 1 cho user hiện tại nếu có assigned_to (khách mới hoặc khách tự do)
        $userId = $customer->assigned_to ?: (auth()->id() ?: null);
        if ($userId) {
            $priorityService->attachSale($customer, (int) $userId, 1, 'created');
        } else {
            $priorityService->ensureLifecycle($customer);
        }

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

    public function editAccountingProfile(Request $request, Customer $customer)
    {
        abort_unless($request->user()->canManageCustomerDebt(), 403);
        $address = $customer->addresses()->where('is_default', true)->first();
        $provinces = Province::query()->orderBy('name')->get(['id', 'name']);
        $provinceId = $request->old('province_id', $address?->province_id);
        $wards = Ward::query()->whereNotNull('province_id')->orderBy('name')->get(['id', 'name', 'province_id'])->groupBy('province_id');

        return view('accounting.customer-profile-edit', compact('customer', 'address', 'provinces', 'wards', 'provinceId'));
    }

    public function updateAccountingProfile(Request $request, Customer $customer)
    {
        abort_unless($request->user()->canManageCustomerDebt(), 403);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('customers')->ignore($customer)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers')->ignore($customer)],
            'address' => ['present', 'nullable', 'string', 'max:1000'],
            'province_id' => ['nullable', 'integer', 'exists:provinces,id', 'required_with:ward_id'],
            'ward_id' => ['nullable', 'integer', Rule::exists('wards', 'id')->where('province_id', $request->input('province_id'))],
            'company_name' => ['nullable', 'string', 'max:255'],
            'tax_code' => ['nullable', 'string', 'max:50'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'company_email' => ['nullable', 'email', 'max:255'],
        ]);
        if (Customer::query()->where('name_normalized', Customer::normalizeName($data['name']))->whereKeyNot($customer->id)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['name' => 'Tên khách hàng đã tồn tại.']);
        }
        $province = Province::find($data['province_id'] ?? null);
        $ward = Ward::find($data['ward_id'] ?? null);
        unset($data['province_id'], $data['ward_id']);

        DB::transaction(function () use ($customer, $data, $province, $ward) {
            $customer = Customer::query()->lockForUpdate()->findOrFail($customer->id);
            $customer->update($data);
            $address = $customer->addresses()->where('is_default', true)->first();
            if ($data['address'] || $province || $address) {
                $locationChanged = (int) $address?->province_id !== (int) $province?->id
                    || (int) $address?->ward_id !== (int) $ward?->id;
                $location = $locationChanged ? [
                    'city' => $province?->name,
                    'ward' => $ward?->name,
                    'province_id' => $province?->id,
                    'ward_id' => $ward?->id,
                ] : [];
                $customer->addresses()->updateOrCreate(['is_default' => true], ['note' => $data['address']] + $location);
            }
        });

        return redirect()->route('accounting.customers-list')->with('success', 'Đã cập nhật thông tin khách hàng.');
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
            'commission_percent' => 'nullable|numeric|min:0|max:100',
            'legacy_code' => 'nullable|string|max:50',
            'old_name' => 'nullable|string|max:5000',
            'special_pickup_enabled' => 'sometimes|boolean',
            'note' => 'nullable|string|max:2000',
            'address' => 'nullable|string|max:1000',
            'delivery_time' => 'required|date_format:H:i',
            'delivery_time_note' => 'nullable|string|max:1000',
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
        if (!$request->user()->isAdmin()) unset($data['special_pickup_enabled']);


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

        $duplicateName = Customer::query()
            ->where('name_normalized', Customer::normalizeName($data['name']))
            ->whereKeyNot($customer->id)
            ->first();

        if ($duplicateName) {
            return back()->withErrors([
                'name' => "Tên khách hàng đã tồn tại (#{$duplicateName->id}: {$duplicateName->name}).",
            ])->withInput();
        }

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

    public function assignSale(Request $request, Customer $customer, CustomerPriorityService $priorityService)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if ($customer->is_employee) {
            if ($request->expectsJson()) return response()->json(['message' => __('customers.messages.employee_cannot_assign_sale')], 422);
            return back()->withErrors(['assigned_to' => __('customers.messages.employee_cannot_assign_sale')]);
        }

        $validated = $request->validate([
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        $assignedTo = $validated['assigned_to'] ?? null;

        if ($assignedTo !== null) {
            $allowedSaleIds = $this->salesUsersQuery()->pluck('id')->all();
            if (!in_array((int) $assignedTo, array_map('intval', $allowedSaleIds), true)) {
                if ($request->expectsJson()) return response()->json(['message' => 'User được chọn không thuộc nhóm sale có thể nhận khách.'], 422);
                return back()->withErrors([
                    'assigned_to' => 'User được chọn không thuộc nhóm sale có thể nhận khách.',
                ]);
            }
        }

        if ($assignedTo === null) {
            $customer->update([
                'assigned_to' => null,
                'assigned_at' => null,
                'current_owner_sale_id' => null,
                'customer_status' => 'free',
                'free_from_date' => now(),
            ]);
        } else {
            $priorityService->takeover($customer, (int) $assignedTo, 'manager_override');
        }

        if ($request->expectsJson()) return response()->json(['success' => true, 'message' => $assignedTo ? 'Đã chuyển giao khách hàng cho sale.' : 'Đã chuyển khách hàng về trạng thái tự do.']);

        return redirect()->route('customers.index', [
            'q' => $request->input('q'),
            'type_id' => $request->input('type_id'),
            'assigned_to' => $request->input('assigned_to'),
            'user_id' => $request->input('user_id'),
            'per_page' => $request->input('per_page'),
            'ownership_status' => $request->input('ownership_status'),
            'order_status' => $request->input('order_status'),
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
            'order_status',
            'per_page',
            'is_employee',
            'page',
        ]))->with('success', __('customers.messages.bulk_marked_employee'));
    }

    public function bulkUnmarkEmployee(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $request->validate([
            'ids' => 'required|string',
        ]);

        $ids = array_values(array_filter(array_map('intval', explode(',', $request->input('ids')))));

        if (empty($ids)) {
            return redirect()->route('customers.index')->withErrors([
                'ids' => __('customers.index.choose_one_for_bulk_unmark_employee'),
            ]);
        }

        Customer::query()
            ->whereIn('id', $ids)
            ->where('is_employee', true)
            ->update(['is_employee' => false]);

        return redirect()->route('customers.index', $request->only([
            'q',
            'type_id',
            'assigned_to',
            'user_id',
            'ownership_status',
            'order_status',
            'per_page',
            'is_employee',
            'page',
        ]))->with('success', __('customers.messages.bulk_unmarked_employee'));
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
