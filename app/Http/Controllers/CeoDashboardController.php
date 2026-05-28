<?php

namespace App\Http\Controllers;

use App\Models\AdminEvent;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderAdjustment;
use App\Models\OrderItem;
use App\Models\ProductPriceLog;
use App\Models\Role;
use App\Models\Team;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CeoDashboardController extends Controller
{
    /**
     * Báo cáo doanh thu của một khách hàng cho CEO
     */
    public function customerRevenueReport(Request $request, Customer $customer)
    {
        // Lấy các đơn hàng đã hoàn thành của khách này
        $orders = Order::query()
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['completed', 'delivered'])
            ->orderByDesc('created_at')
            ->get();

        $totalRevenue = $orders->sum('total_amount');
        $orderCount = $orders->count();
        $firstOrder = $orders->last();
        $lastOrder = $orders->first();

        return view('ceo.customer_revenue_report', compact('customer', 'orders', 'totalRevenue', 'orderCount', 'firstOrder', 'lastOrder'));
    }
    public function __construct()
    {
        $this->middleware(['auth', 'role:ceo,admin']);
    }

    public function dashboard(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveRange($request);
        $groupBy = $this->resolveGroupBy($request);

        $overview = $this->buildOverview($from, $to);
        $salesTop = $this->buildSalesTop($from, $to, 5);
        $customerTop = $this->buildCustomerTop($from, $to, 5);
        $shipperTop = $this->buildShipperTop($from, $to, 5);
        $alerts = $this->buildAlerts($overview);
        $trend = $this->buildPriceAndVolumeTrend($from, $to, $groupBy);

        return view('ceo.dashboard', compact('overview', 'salesTop', 'customerTop', 'shipperTop', 'alerts', 'from', 'to', 'rangeLabel', 'trend', 'groupBy'));
    }

    public function revenue(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveRange($request);

        $payments = Transaction::query()
            ->where('type', 'payment')
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        $refunds = Transaction::query()
            ->where('type', 'refund')
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        $netRevenue = (float) $payments - (float) $refunds;

        $dailySeries = Transaction::query()
            ->selectRaw('DATE(created_at) as day_key')
            ->selectRaw("SUM(CASE WHEN type='payment' THEN amount ELSE 0 END) as income")
            ->selectRaw("SUM(CASE WHEN type='refund' THEN amount ELSE 0 END) as refund")
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('day_key')
            ->orderBy('day_key')
            ->get();

        return view('ceo.section', [
            'pageTitle' => 'Tổng Quan Doanh Thu',
            'pageSubtitle' => 'Theo dõi doanh thu thuần, thu tiền và hoàn tiền theo kỳ.',
            'rangeLabel' => $rangeLabel,
            'from' => $from,
            'to' => $to,
            'cards' => [
                ['label' => 'Thu tiền', 'value' => number_format((float) $payments) . ' đ'],
                ['label' => 'Hoàn tiền', 'value' => number_format((float) $refunds) . ' đ'],
                ['label' => 'Doanh thu thuần', 'value' => number_format($netRevenue) . ' đ'],
            ],
            'tableTitle' => 'Diễn biến doanh thu theo ngày',
            'columns' => ['Ngày', 'Thu tiền', 'Hoàn tiền', 'Doanh thu thuần'],
            'rows' => $dailySeries->map(function ($row) {
                $net = (float) $row->income - (float) $row->refund;

                return [
                    Carbon::parse($row->day_key)->format('d/m/Y'),
                    number_format((float) $row->income) . ' đ',
                    number_format((float) $row->refund) . ' đ',
                    number_format($net) . ' đ',
                ];
            })->all(),
        ]);
    }

    public function orders(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveRange($request);

        $statusRows = Order::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $total = (int) $statusRows->sum('total');

        return view('ceo.section', [
            'pageTitle' => 'Tổng Đơn Hàng',
            'pageSubtitle' => 'Theo dõi pipeline đơn hàng theo trạng thái xử lý.',
            'rangeLabel' => $rangeLabel,
            'from' => $from,
            'to' => $to,
            'cards' => [
                ['label' => 'Tổng đơn', 'value' => number_format($total)],
                ['label' => 'Đơn hoàn thành', 'value' => number_format((int) $statusRows->whereIn('status', ['completed', 'delivered'])->sum('total'))],
                ['label' => 'Đơn trả/hủy', 'value' => number_format((int) $statusRows->whereIn('status', ['returning', 'returned_completed', 'returned', 'cancelled', 'rejected'])->sum('total'))],
            ],
            'tableTitle' => 'Phân bổ trạng thái đơn',
            'columns' => ['Trạng thái', 'Số đơn', 'Tỷ trọng'],
            'rows' => $statusRows->map(function ($row) use ($total) {
                $rate = $total > 0 ? round(((int) $row->total / $total) * 100, 1) : 0;

                return [
                    (string) $row->status,
                    number_format((int) $row->total),
                    $rate . '%',
                ];
            })->all(),
        ]);
    }

    public function sales(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveRange($request);

        $rows = $this->buildSalesTop($from, $to, 20);

        return view('ceo.section', [
            'pageTitle' => 'Hiệu Suất Sale',
            'pageSubtitle' => 'Bảng xếp hạng doanh số theo nhân sự kinh doanh.',
            'rangeLabel' => $rangeLabel,
            'from' => $from,
            'to' => $to,
            'cards' => [
                ['label' => 'Số sale có phát sinh', 'value' => number_format($rows->count())],
                ['label' => 'Tổng doanh số', 'value' => number_format((float) $rows->sum('total_amount')) . ' đ'],
                ['label' => 'Tổng số đơn', 'value' => number_format((int) $rows->sum('total_orders'))],
            ],
            'tableTitle' => 'Top sale theo doanh số',
            'columns' => ['Nhân sự', 'Số đơn', 'Doanh số'],
            'rows' => $rows->map(fn ($row) => [
                (string) $row->name,
                number_format((int) $row->total_orders),
                number_format((float) $row->total_amount) . ' đ',
            ])->all(),
        ]);
    }

    public function debts(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveRange($request);

        $hasAmountDue = Schema::hasColumn('orders', 'amount_due');

        $rows = collect();
        $totalDebt = 0;

        if ($hasAmountDue) {
            $rows = Order::query()
                ->select('customer_id', DB::raw('SUM(amount_due) as debt_total'), DB::raw('COUNT(*) as total_orders'))
                ->whereBetween('created_at', [$from, $to])
                ->whereNotNull('customer_id')
                ->where('amount_due', '>', 0)
                ->groupBy('customer_id')
                ->with('customer:id,name,phone')
                ->orderByDesc('debt_total')
                ->limit(20)
                ->get();

            $totalDebt = (float) Order::query()
                ->whereBetween('created_at', [$from, $to])
                ->where('amount_due', '>', 0)
                ->sum('amount_due');
        }

        return view('ceo.section', [
            'pageTitle' => 'Công Nợ Khách Hàng',
            'pageSubtitle' => 'Theo dõi nợ phải thu và nhóm khách cần ưu tiên xử lý.',
            'rangeLabel' => $rangeLabel,
            'from' => $from,
            'to' => $to,
            'cards' => [
                ['label' => 'Tổng công nợ', 'value' => number_format($totalDebt) . ' đ'],
                ['label' => 'Khách có nợ', 'value' => number_format($rows->count())],
                ['label' => 'Đơn còn nợ', 'value' => number_format((int) $rows->sum('total_orders'))],
            ],
            'tableTitle' => 'Top khách hàng công nợ',
            'columns' => ['Khách hàng', 'Điện thoại', 'Số đơn nợ', 'Tổng nợ'],
            'rows' => $rows->map(fn ($row) => [
                (string) ($row->customer?->name ?? 'N/A'),
                (string) ($row->customer?->phone ?? '-'),
                number_format((int) $row->total_orders),
                number_format((float) $row->debt_total) . ' đ',
            ])->all(),
        ]);
    }

    public function warehouse(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveRange($request);

        $rows = Warehouse::query()
            ->withCount(['inventories as sku_count'])
            ->withSum('inventories as stock_total', 'quantity')
            ->withSum('inventories as reserved_total', 'reserved_quantity')
            ->orderByDesc('stock_total')
            ->get();

        $lowStockCount = Inventory::query()
            ->where('quantity', '>', 0)
            ->whereRaw('quantity <= COALESCE(low_stock_threshold, 5)')
            ->count();

        $outOfStock = Inventory::query()->where('quantity', '<=', 0)->count();

        return view('ceo.section', [
            'pageTitle' => 'Hiệu Suất Kho',
            'pageSubtitle' => 'Theo dõi mức tồn, hàng giữ chỗ và cảnh báo thiếu hàng.',
            'rangeLabel' => $rangeLabel,
            'from' => $from,
            'to' => $to,
            'cards' => [
                ['label' => 'SKU sắp hết', 'value' => number_format($lowStockCount)],
                ['label' => 'SKU hết hàng', 'value' => number_format($outOfStock)],
                ['label' => 'Tổng kho', 'value' => number_format($rows->count())],
            ],
            'tableTitle' => 'Hiệu suất theo kho',
            'columns' => ['Kho', 'Số SKU', 'Tồn kho', 'Giữ chỗ', 'Khả dụng'],
            'rows' => $rows->map(function ($row) {
                $stock = (int) ($row->stock_total ?? 0);
                $reserved = (int) ($row->reserved_total ?? 0);
                $available = max(0, $stock - $reserved);

                return [
                    (string) $row->name,
                    number_format((int) ($row->sku_count ?? 0)),
                    number_format($stock),
                    number_format($reserved),
                    number_format($available),
                ];
            })->all(),
        ]);
    }

    public function shipper(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveRange($request);

        $rows = Order::query()
            ->select('shipper_id', DB::raw('COUNT(*) as total_orders'))
            ->selectRaw("SUM(CASE WHEN status IN ('completed', 'delivered') THEN 1 ELSE 0 END) as success_orders")
            ->selectRaw("SUM(CASE WHEN status IN ('returning', 'returned', 'returned_completed') THEN 1 ELSE 0 END) as return_orders")
            ->whereNotNull('shipper_id')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('shipper_id')
            ->with('shipper:id,name')
            ->orderByDesc('total_orders')
            ->limit(20)
            ->get();

        return view('ceo.section', [
            'pageTitle' => 'Hiệu Suất Shipper',
            'pageSubtitle' => 'Đánh giá tỷ lệ giao thành công và tỷ lệ hoàn theo shipper.',
            'rangeLabel' => $rangeLabel,
            'from' => $from,
            'to' => $to,
            'cards' => [
                ['label' => 'Shipper hoạt động', 'value' => number_format($rows->count())],
                ['label' => 'Tổng đơn shipper', 'value' => number_format((int) $rows->sum('total_orders'))],
                ['label' => 'Đơn hoàn/trả', 'value' => number_format((int) $rows->sum('return_orders'))],
            ],
            'tableTitle' => 'Top shipper theo khối lượng đơn',
            'columns' => ['Shipper', 'Tổng đơn', 'Giao thành công', 'Hoàn/Trả', 'Tỷ lệ thành công'],
            'rows' => $rows->map(function ($row) {
                $total = (int) $row->total_orders;
                $success = (int) $row->success_orders;
                $rate = $total > 0 ? round(($success / $total) * 100, 1) : 0;

                return [
                    (string) ($row->shipper?->name ?? 'N/A'),
                    number_format($total),
                    number_format($success),
                    number_format((int) $row->return_orders),
                    $rate . '%',
                ];
            })->all(),
        ]);
    }

    public function customers(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveRange($request);

        $rows = $this->buildCustomerTop($from, $to, 20);

        return view('ceo.section', [
            'pageTitle' => 'Khách Hàng Lớn / Tiềm Năng',
            'pageSubtitle' => 'Nhận diện nhóm khách mang doanh thu cao và cần chăm sóc ưu tiên.',
            'rangeLabel' => $rangeLabel,
            'from' => $from,
            'to' => $to,
            'cards' => [
                ['label' => 'Khách phát sinh đơn', 'value' => number_format($rows->count())],
                ['label' => 'Tổng doanh số top', 'value' => number_format((float) $rows->sum('total_amount')) . ' đ'],
                ['label' => 'Tổng số đơn top', 'value' => number_format((int) $rows->sum('total_orders'))],
            ],
            'tableTitle' => 'Top khách hàng theo doanh số',
            'columns' => ['Khách hàng', 'Điện thoại', 'Số đơn', 'Doanh số'],
            'rows' => $rows->map(fn ($row) => [
                (string) ($row->name ?? 'N/A'),
                (string) ($row->phone ?? '-'),
                number_format((int) $row->total_orders),
                number_format((float) $row->total_amount) . ' đ',
            ])->all(),
        ]);
    }

    public function customersList(Request $request)
    {
        $query = Customer::with(['type', 'addresses', 'assignedTo', 'user', 'lastOrder']);

        if ($request->filled('type_id')) {
            $query->where('customer_type_id', $request->input('type_id'));
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->input('assigned_to'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Date range filter
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }

        if ($request->boolean('is_employee')) {
            $query->where('is_employee', true);
        } else {
            $query->where('is_employee', false);
        }

        if (!$request->boolean('is_employee') && $request->filled('ownership_status')) {
            if ($request->input('ownership_status') === 'free') {
                $query->free();
            }

            if ($request->input('ownership_status') === 'managed') {
                $query->managed();
            }
        }

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $customers = $query->orderBy('name')
            ->paginate($perPage)
            ->appends($request->query());

        $types = CustomerType::query()
            ->orderByDesc('priority_level')
            ->orderBy('name')
            ->get(['id', 'name']);

        $users = User::query()
            ->whereHas('roles', function ($q) {
                $q->whereIn(DB::raw('LOWER(name)'), ['sale', 'leader', 'leader_sale', 'sale_manager']);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $creatorUsers = User::query()
            ->whereIn('id', Customer::query()->select('user_id')->whereNotNull('user_id')->distinct())
            ->orderBy('name')
            ->get(['id', 'name']);

        $customerFreeDays = Customer::freeCustomerDays();

        // Get customer statistics by employee (sales only)
        $employeeStats = Customer::query()
            ->select('assigned_to', DB::raw('COUNT(*) as customer_count'))
            ->where('is_employee', false)
            ->whereNotNull('assigned_to')
            ->whereHas('assignedTo.roles', function ($q) {
                $q->whereIn(DB::raw('LOWER(name)'), ['sale', 'leader', 'leader_sale', 'sale_manager']);
            })
            ->groupBy('assigned_to')
            ->with('assignedTo:id,name')
            ->orderByDesc('customer_count')
            ->get()
            ->map(function ($stat) {
                return [
                    'employee_id' => $stat->assigned_to,
                    'employee_name' => optional($stat->assignedTo)->name ?? 'N/A',
                    'customer_count' => $stat->customer_count,
                ];
            })
            ->values();

        // Exclude users from statistics
        if ($request->filled('exclude_users')) {
            $excludeIds = array_filter((array) $request->input('exclude_users'), 'is_numeric');
            if (!empty($excludeIds)) {
                $employeeStats = $employeeStats->filter(function ($stat) use ($excludeIds) {
                    return !in_array($stat['employee_id'], $excludeIds);
                })->values();
            }
        }

        return view('ceo.customers_list', compact(
            'customers',
            'types',
            'users',
            'creatorUsers',
            'customerFreeDays',
            'employeeStats'
        ));
    }

    public function usersList(Request $request)
    {
        $query = User::with('roles', 'warehouse', 'team');

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($sub) use ($search) {
                $sub->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('team_id')) {
            $query->where('team_id', $request->input('team_id'));
        }

        if ($request->filled('role_id')) {
            $roleId = (int) $request->input('role_id');
            $query->whereHas('roles', function ($sub) use ($roleId) {
                $sub->where('roles.id', $roleId);
            });
        }

        $users = $query->orderBy('name')->paginate(15)->appends($request->query());
        $teams = Team::orderBy('name')->get(['id', 'name']);
        $roles = Role::orderBy('name')->get(['id', 'name']);

        $selectedUser = null;
        $activities = collect();
        if ($request->filled('user_id')) {
            $selectedUser = User::with('roles', 'team', 'warehouse', 'department')
                ->find($request->input('user_id'));
            if ($selectedUser && Schema::hasTable('admin_events')) {
                $activities = AdminEvent::where('actor_id', $selectedUser->id)
                    ->orderByDesc('created_at')
                    ->limit(50)
                    ->get();
            }
        }

        return view('ceo.users_list', compact('users', 'teams', 'roles', 'selectedUser', 'activities'));
    }

    public function alerts(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveRange($request);
        $overview = $this->buildOverview($from, $to);
        $alerts = $this->buildAlerts($overview);

        return view('ceo.section', [
            'pageTitle' => 'Cảnh Báo CEO',
            'pageSubtitle' => 'Những vấn đề cần theo dõi và xử lý ở cấp điều hành.',
            'rangeLabel' => $rangeLabel,
            'from' => $from,
            'to' => $to,
            'cards' => [
                ['label' => 'Tổng cảnh báo', 'value' => number_format(count($alerts))],
                ['label' => 'Mức cao', 'value' => number_format(collect($alerts)->where('level', 'high')->count())],
                ['label' => 'Mức trung bình', 'value' => number_format(collect($alerts)->where('level', 'medium')->count())],
            ],
            'tableTitle' => 'Danh sách cảnh báo',
            'columns' => ['Mức độ', 'Tiêu đề', 'Mô tả'],
            'rows' => collect($alerts)->map(fn ($alert) => [
                strtoupper((string) $alert['level']),
                (string) $alert['title'],
                (string) $alert['description'],
            ])->all(),
        ]);
    }

    public function reports(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveRange($request);

        $monthly = Order::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as period")
            ->selectRaw('COUNT(*) as total_orders')
            ->selectRaw('SUM(total) as total_amount')
            ->where('created_at', '>=', now()->subMonths(6)->startOfMonth())
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return view('ceo.section', [
            'pageTitle' => 'Báo Cáo Điều Hành',
            'pageSubtitle' => 'Tổng hợp dữ liệu phục vụ họp điều hành theo kỳ.',
            'rangeLabel' => $rangeLabel,
            'from' => $from,
            'to' => $to,
            'cards' => [
                ['label' => 'Số kỳ báo cáo', 'value' => number_format($monthly->count())],
                ['label' => 'Tổng đơn 6 tháng', 'value' => number_format((int) $monthly->sum('total_orders'))],
                ['label' => 'Tổng giá trị 6 tháng', 'value' => number_format((float) $monthly->sum('total_amount')) . ' đ'],
            ],
            'tableTitle' => 'Tổng hợp theo tháng',
            'columns' => ['Tháng', 'Số đơn', 'Tổng giá trị'],
            'rows' => $monthly->map(fn ($row) => [
                (string) $row->period,
                number_format((int) $row->total_orders),
                number_format((float) $row->total_amount) . ' đ',
            ])->all(),
        ]);
    }

    private function resolveRange(Request $request): array
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
            'day' => 'Theo ngày',
            'week' => 'Theo tuần',
            'year' => 'Theo năm',
            'custom' => 'Tùy chọn',
            default => 'Theo tháng',
        };

        return [$from, $to, $rangeLabel];
    }

    private function resolveGroupBy(Request $request): string
    {
        $groupBy = (string) $request->input('group_by', 'week');

        if (!in_array($groupBy, ['day', 'week', 'month', 'quarter', 'year'], true)) {
            $groupBy = 'week';
        }

        return $groupBy;
    }

    private function periodExpression(string $column, string $groupBy): string
    {
        return match ($groupBy) {
            'day' => "DATE({$column})",
            'week' => "DATE_FORMAT({$column}, '%x-W%v')",
            'month' => "DATE_FORMAT({$column}, '%Y-%m')",
            'quarter' => "CONCAT(YEAR({$column}), '-Q', QUARTER({$column}))",
            'year' => "DATE_FORMAT({$column}, '%Y')",
            default => "DATE_FORMAT({$column}, '%x-W%v')",
        };
    }

    private function formatPeriodLabel(string $periodKey, string $groupBy): string
    {
        return match ($groupBy) {
            'day' => Carbon::parse($periodKey)->format('d/m'),
            'week' => str_replace('-', ' ', $periodKey),
            'month' => Carbon::createFromFormat('Y-m', $periodKey)->format('m/Y'),
            'quarter' => preg_match('/^(\d{4})-Q([1-4])$/', $periodKey, $matches)
                ? ('Q' . $matches[2] . '/' . $matches[1])
                : $periodKey,
            'year' => $periodKey,
            default => $periodKey,
        };
    }

    private function buildPriceAndVolumeTrend(Carbon $from, Carbon $to, string $groupBy): array
    {
        $pricePeriodExpr = $this->periodExpression('applied_at', $groupBy);
        $volumePeriodExpr = $this->periodExpression('orders.created_at', $groupBy);

        $priceRows = ProductPriceLog::query()
            ->selectRaw("{$pricePeriodExpr} as period_key")
            ->selectRaw('MIN(applied_at) as period_start')
            ->selectRaw('AVG(new_price) as avg_price')
            ->selectRaw('COUNT(*) as change_count')
            ->whereBetween('applied_at', [$from, $to])
            ->groupBy('period_key')
            ->orderBy('period_start')
            ->get();

        $volumeRows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->selectRaw("{$volumePeriodExpr} as period_key")
            ->selectRaw('MIN(orders.created_at) as period_start')
            ->selectRaw('SUM(order_items.quantity) as total_qty')
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereNotIn('orders.status', ['cancelled', 'rejected'])
            ->groupBy('period_key')
            ->orderBy('period_start')
            ->get();

        $priceMap = $priceRows->keyBy('period_key');
        $volumeMap = $volumeRows->keyBy('period_key');

        // Chart should represent each price-change milestone.
        $periodKeys = $priceRows->pluck('period_key')->unique()->sort()->values();

        if ($periodKeys->isEmpty()) {
            // Fallback: when no price-change record exists in range, still show volume trend.
            $periodKeys = $volumeRows->pluck('period_key')->unique()->sort()->values();
        }

        $labels = [];
        $avgPrices = [];
        $priceChanges = [];
        $quantities = [];

        foreach ($periodKeys as $periodKey) {
            $labels[] = $this->formatPeriodLabel((string) $periodKey, $groupBy);
            $avgPrices[] = (float) ($priceMap[$periodKey]->avg_price ?? 0);
            $priceChanges[] = (int) ($priceMap[$periodKey]->change_count ?? 0);
            $quantities[] = (int) ($volumeMap[$periodKey]->total_qty ?? 0);
        }

        return [
            'group_by' => $groupBy,
            'labels' => $labels,
            'avg_prices' => $avgPrices,
            'price_changes' => $priceChanges,
            'quantities' => $quantities,
            'summary' => [
                'total_price_changes' => (int) $priceRows->sum('change_count'),
                'total_quantity' => (int) $volumeRows->sum('total_qty'),
                'avg_price_whole_period' => (float) $priceRows->avg('avg_price'),
            ],
        ];
    }

    private function buildOverview(Carbon $from, Carbon $to): array
    {
        $totalOrders = (int) Order::query()->whereBetween('created_at', [$from, $to])->count();
        $completedOrders = (int) Order::query()->whereBetween('created_at', [$from, $to])->whereIn('status', ['completed', 'delivered'])->count();
        $cancelledOrders = (int) Order::query()->whereBetween('created_at', [$from, $to])->whereIn('status', ['cancelled', 'rejected'])->count();
        $returningOrders = (int) Order::query()->whereBetween('created_at', [$from, $to])->whereIn('status', ['returning', 'returned', 'returned_completed'])->count();

        $grossRevenue = (float) Order::query()->whereBetween('created_at', [$from, $to])->sum('total');

        $income = (float) Transaction::query()->whereBetween('created_at', [$from, $to])->where('type', 'payment')->sum('amount');
        $refund = (float) Transaction::query()->whereBetween('created_at', [$from, $to])->where('type', 'refund')->sum('amount');

        $debtTotal = 0;
        if (Schema::hasColumn('orders', 'amount_due')) {
            $debtTotal = (float) Order::query()->whereBetween('created_at', [$from, $to])->where('amount_due', '>', 0)->sum('amount_due');
        }

        $completionRate = $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100, 1) : 0;

        $lowStockCount = (int) Inventory::query()
            ->where('quantity', '>', 0)
            ->whereRaw('quantity <= COALESCE(low_stock_threshold, 5)')
            ->count();

        $outOfStockCount = (int) Inventory::query()->where('quantity', '<=', 0)->count();

        return [
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrders,
            'cancelled_orders' => $cancelledOrders,
            'returning_orders' => $returningOrders,
            'gross_revenue' => $grossRevenue,
            'net_revenue' => $income - $refund,
            'debt_total' => $debtTotal,
            'completion_rate' => $completionRate,
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'active_customers' => (int) Customer::query()->whereHas('orders', function ($q) use ($from, $to) {
                $q->whereBetween('created_at', [$from, $to]);
            })->count(),
            'total_warehouses' => (int) Warehouse::count(),
        ];
    }

    private function buildSalesTop(Carbon $from, Carbon $to, int $limit)
    {
        return Order::query()
            ->select('user_id', DB::raw('COUNT(*) as total_orders'), DB::raw('SUM(total) as total_amount'))
            ->whereNotNull('user_id')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('user_id')
            ->with('user:id,name')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $row->name = $row->user?->name ?? 'N/A';

                return $row;
            });
    }

    private function buildCustomerTop(Carbon $from, Carbon $to, int $limit)
    {
        return Order::query()
            ->select('customer_id', DB::raw('COUNT(*) as total_orders'), DB::raw('SUM(total) as total_amount'))
            ->whereNotNull('customer_id')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('customer_id')
            ->with('customer:id,name,phone')
            ->orderByDesc('total_amount')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $row->name = $row->customer?->name ?? 'N/A';
                $row->phone = $row->customer?->phone ?? '-';

                return $row;
            });
    }

    private function buildShipperTop(Carbon $from, Carbon $to, int $limit)
    {
        return Order::query()
            ->select('shipper_id', DB::raw('COUNT(*) as total_orders'))
            ->selectRaw("SUM(CASE WHEN status IN ('completed', 'delivered') THEN 1 ELSE 0 END) as success_orders")
            ->whereNotNull('shipper_id')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('shipper_id')
            ->with('shipper:id,name')
            ->orderByDesc('total_orders')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $row->name = $row->shipper?->name ?? 'N/A';
                $row->success_rate = ((int) $row->total_orders) > 0
                    ? round(((int) $row->success_orders / (int) $row->total_orders) * 100, 1)
                    : 0;

                return $row;
            });
    }

    private function buildAlerts(array $overview): array
    {
        $alerts = [];

        if ($overview['debt_total'] > 50000000) {
            $alerts[] = [
                'level' => 'high',
                'title' => 'Công nợ vượt ngưỡng an toàn',
                'description' => 'Tổng công nợ đang vượt 50 triệu, cần kế hoạch thu hồi ưu tiên.',
            ];
        }

        if ($overview['completion_rate'] < 70) {
            $alerts[] = [
                'level' => 'high',
                'title' => 'Tỷ lệ hoàn tất đơn thấp',
                'description' => 'Tỷ lệ hoàn tất dưới 70%, cần rà soát quy trình bán hàng và vận hành.',
            ];
        }

        if ($overview['returning_orders'] > 0 && $overview['total_orders'] > 0) {
            $returnRate = round(($overview['returning_orders'] / $overview['total_orders']) * 100, 1);
            if ($returnRate >= 10) {
                $alerts[] = [
                    'level' => 'medium',
                    'title' => 'Tỷ lệ hoàn/trả tăng cao',
                    'description' => 'Đơn hoàn/trả đạt ' . $returnRate . '%, cần kiểm tra nguyên nhân chất lượng hoặc vận chuyển.',
                ];
            }
        }

        if ($overview['out_of_stock_count'] > 0) {
            $alerts[] = [
                'level' => 'medium',
                'title' => 'Có SKU đã hết hàng',
                'description' => 'Hiện có ' . number_format($overview['out_of_stock_count']) . ' SKU hết hàng, cần ưu tiên nhập kho.',
            ];
        }

        if (empty($alerts)) {
            $alerts[] = [
                'level' => 'low',
                'title' => 'Không có cảnh báo nghiêm trọng',
                'description' => 'Các chỉ số hiện tại đang trong ngưỡng theo dõi bình thường.',
            ];
        }

        return $alerts;
    }

    public function weeklyReport(Request $request)
    {
        // Tính tuần hiện tại (từ thứ 2 đến chủ nhật)
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $now->copy()->endOfWeek(Carbon::SUNDAY);

        // Dữ liệu mẫu - thay thế bằng logic thực tế
        $weeklyData = [
            'Sản phẩm A' => [
                'T2' => 10,
                'T3' => 15,
                'T4' => 8,
                'T5' => 12,
                'T6' => 20,
                'T7' => 18,
                'CN' => 25,
                'total' => 108
            ],
            'Sản phẩm B' => [
                'T2' => 5,
                'T3' => 8,
                'T4' => 12,
                'T5' => 15,
                'T6' => 10,
                'T7' => 22,
                'CN' => 18,
                'total' => 90
            ],
            'Sản phẩm C' => [
                'T2' => 20,
                'T3' => 25,
                'T4' => 18,
                'T5' => 30,
                'T6' => 35,
                'T7' => 28,
                'CN' => 40,
                'total' => 196
            ]
        ];

        // Giá sản phẩm mẫu (VNĐ)
        $productPrices = [
            'Sản phẩm A' => 150000, // 150k
            'Sản phẩm B' => 200000, // 200k
            'Sản phẩm C' => 100000, // 100k
        ];

        // Tính doanh thu theo ngày
        $dailyRevenue = [];
        $totalRevenue = 0;
        $days = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];

        foreach ($days as $day) {
            $dailyRevenue[$day] = 0;
            foreach ($weeklyData as $productName => $productData) {
                $quantity = $productData[$day] ?? 0;
                $price = $productPrices[$productName] ?? 0;
                $dailyRevenue[$day] += $quantity * $price;
            }
            $totalRevenue += $dailyRevenue[$day];
        }

        $totalQuantity = 394; // Tổng số lượng

        return view('ceo.weekly_report', compact('weeklyData', 'dailyRevenue', 'totalRevenue', 'totalQuantity'));
    }

    public function weeklyCustomerReport(Request $request)
    {
        // Tính tuần hiện tại (từ thứ 2 đến chủ nhật)
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $now->copy()->endOfWeek(Carbon::SUNDAY);

        // Dữ liệu mẫu khách hàng - thay thế bằng logic thực tế
        $customerWeeklyData = [
            'Công ty TNHH ABC' => [
                'T2' => 2500000,
                'T3' => 1800000,
                'T4' => 3200000,
                'T5' => 4100000,
                'T6' => 2800000,
                'T7' => 3500000,
                'CN' => 5200000,
                'total' => 23100000
            ],
            'Cửa hàng XYZ' => [
                'T2' => 1200000,
                'T3' => 1500000,
                'T4' => 800000,
                'T5' => 2200000,
                'T6' => 1900000,
                'T7' => 2600000,
                'CN' => 3100000,
                'total' => 13300000
            ],
            'Siêu thị DEF' => [
                'T2' => 3500000,
                'T3' => 4200000,
                'T4' => 3800000,
                'T5' => 5100000,
                'T6' => 4600000,
                'T7' => 5800000,
                'CN' => 7200000,
                'total' => 34200000
            ],
            'Khách lẻ Online' => [
                'T2' => 800000,
                'T3' => 950000,
                'T4' => 1200000,
                'T5' => 1400000,
                'T6' => 1100000,
                'T7' => 1600000,
                'CN' => 2100000,
                'total' => 9150000
            ]
        ];

        // Tính tổng doanh thu theo ngày
        $dailyRevenue = [];
        $totalRevenue = 0;
        $days = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];

        foreach ($days as $day) {
            $dailyRevenue[$day] = 0;
            foreach ($customerWeeklyData as $customerName => $customerData) {
                $dailyRevenue[$day] += $customerData[$day] ?? 0;
            }
            $totalRevenue += $dailyRevenue[$day];
        }

        $totalCustomers = count($customerWeeklyData);
        $avgDailyRevenue = $totalRevenue / 7;

        return view('ceo.weekly_customer_report', compact('customerWeeklyData', 'dailyRevenue', 'totalRevenue', 'totalCustomers', 'avgDailyRevenue'));
    }

    public function financialReports(Request $request)
    {
        [$from, $to, $rangeLabel] = $this->resolveRange($request);

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

        $accountFilterId = $request->input('account_id');
        $catStatsQuery = Transaction::query()
            ->with([
                'transactionCategory:id,code,name,flow_direction',
                'customer:id,name',
                'account:id,name,type',
            ])
            ->whereBetween('created_at', [$from, $to])
            ->where('status', Transaction::STATUS_APPROVED)
            ->whereNotNull('transaction_category_id');

        if ($accountFilterId) {
            $catStatsQuery->where('account_id', $accountFilterId);
        }

        $allTransactionsByCategory = $catStatsQuery->get();

        $catStats = collect();
        foreach ($allTransactionsByCategory->groupBy('transaction_category_id') as $transactions) {
            $totalAmount = $transactions->sum('amount');
            $totalCount = $transactions->count();

            $customers = $transactions
                ->filter(fn ($t) => $t->customer_id)
                ->map(fn ($t) => ['id' => $t->customer_id, 'name' => $t->customer?->name ?? 'N/A'])
                ->unique('id')
                ->values();

            $accounts = $transactions
                ->filter(fn ($t) => $t->account_id)
                ->map(fn ($t) => ['id' => $t->account_id, 'name' => $t->account?->name ?? 'N/A', 'type' => $t->account?->type ?? 'N/A'])
                ->unique('id')
                ->values();

            $catStats->push((object) [
                'transaction_category_id' => $transactions->first()?->transaction_category_id,
                'transactionCategory'     => $transactions->first()?->transactionCategory,
                'total_count'             => $totalCount,
                'total_amount'            => $totalAmount,
                'customers'               => $customers,
                'accounts'                => $accounts,
            ]);
        }

        $catStats = $catStats->sortByDesc('total_amount')->values();

        $accounts = \App\Models\Account::active()->orderBy('name')->get(['id', 'name', 'type']);

        return view('ceo.financial_reports', compact(
            'revenue', 'received', 'cost', 'profit',
            'series', 'from', 'to', 'rangeLabel',
            'catStats', 'accounts', 'accountFilterId'
        ));
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

        // Get sales statistics by employee:
        // 1) aggregate each order total first, then
        // 2) aggregate by sale to keep order count/value consistent.
        $orderTotalsBySale = $makeBase()
            ->select([
                'orders.id as order_id',
                'orders.user_id',
                DB::raw('MAX(users.name) as sale_name'),
                DB::raw("SUM({$effTotalExpr}) as order_total"),
            ])
            ->groupBy('orders.id', 'orders.user_id');

        $salesStats = DB::query()
            ->fromSub($orderTotalsBySale, 'sale_orders')
            ->select([
                'sale_orders.user_id',
                DB::raw("COALESCE(sale_orders.sale_name, 'N/A') as sale_name"),
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(sale_orders.order_total) as total_value'),
            ])
            ->groupBy('sale_orders.user_id', 'sale_orders.sale_name')
            ->orderByDesc('total_value')
            ->get()
            ->map(function ($stat) {
                return [
                    'sale_id' => $stat->user_id,
                    'sale_name' => $stat->sale_name,
                    'order_count' => $stat->order_count,
                    'total_value' => $stat->total_value,
                ];
            })
            ->values();

        return view('ceo.daily_sales', compact(
            'items', 'productStats', 'summary',
            'fromDate', 'toDate', 'saleId', 'customerId',
            'sort', 'perPage', 'sales', 'customers', 'salesStats',
        ));
    }
}
