<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Setting;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CustomerTrackingController extends Controller
{
    protected $settings;

    public function __construct()
    {
        $this->settings = Cache::remember('settings', 60, function () {
            return Setting::all()->keyBy('key');
        });
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        return view('site.customer_tracking.index', [
            'settings' => $this->settings,
            'user'     => $user,
        ]);
    }

    public function data(Request $request)
    {
        $user = Auth::user();

        // ── Date range ────────────────────────────────────────────────────
        $fromDate = $request->input('from_date')
            ? Carbon::parse($request->input('from_date'))->startOfDay()
            : Carbon::now()->subDays(29)->startOfDay();

        $toDate = $request->input('to_date')
            ? Carbon::parse($request->input('to_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        if ($fromDate->gt($toDate)) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        // ── Report type ───────────────────────────────────────────────────
        $reportType = $request->input('report_type', 'day'); // day | week | month
        if (!in_array($reportType, ['day', 'week', 'month'], true)) {
            $reportType = 'day';
        }

        // ── Sorting ───────────────────────────────────────────────────────
        $allowedSort = ['order_count', 'total_revenue', 'total_qty', 'name'];
        $sortBy  = $request->input('sort_by', 'total_qty');
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'total_qty';
        }
        $sortDir = strtolower((string) $request->input('sort_dir', 'desc'));
        $sortDir = in_array($sortDir, ['asc', 'desc'], true) ? $sortDir : 'desc';

        // ── Pagination ────────────────────────────────────────────────────
        $allowedPerPage = [10, 20, 50, 100];
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }
        $page = max(1, (int) $request->input('page', 1));

        // ── Search ────────────────────────────────────────────────────────
        $search = trim((string) $request->input('search', ''));

        // ── Customer scope: sale sees own, leader/manager sees all in team/dept ──
        $isSale    = $user->hasRole('sale');
        $isLeader  = $user->hasRole('leader') || $user->hasRole('leader_sale') || $user->hasRole('sale_manager');
        $isManager = $user->hasRole('manager') || $user->hasRole('manager_sale');
        $isAdmin   = $user->isAdmin();

        // Build base customer query
        $customerQuery = Customer::query();

        if ($isSale && !$isLeader && !$isManager && !$isAdmin) {
            // Only customers assigned to or owned by this user
            $customerQuery->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('assigned_to', $user->id);
            });
        } elseif ($isLeader && !$isManager && !$isAdmin) {
            // All customers of sale members in same team
            $teamId = $user->team_id;
            if ($teamId) {
                $teamMemberIds = \App\Models\User::where('team_id', $teamId)->pluck('id')->toArray();
                $customerQuery->where(function ($q) use ($teamMemberIds) {
                    $q->whereIn('user_id', $teamMemberIds)
                      ->orWhereIn('assigned_to', $teamMemberIds);
                });
            }
        }
        // manager / admin: no extra scope filter

        if ($search !== '') {
            $customerQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customerIds = $customerQuery->pluck('id');

        // ── Aggregate orders for those customers ──────────────────────────
        $orderAgg = Order::query()
            ->join('customers', 'orders.customer_id', '=', 'customers.id')
            ->whereIn('orders.customer_id', $customerIds)
            ->whereBetween('orders.created_at', [$fromDate, $toDate])
            ->whereNotIn('orders.status', ['cancelled', 'rejected'])
            ->select(
                'orders.customer_id',
                'customers.name as customer_name',
                'customers.phone as customer_phone',
                DB::raw('COUNT(orders.id) as order_count'),
                DB::raw('SUM(COALESCE(orders.total, 0)) as total_revenue'),
                DB::raw('SUM(COALESCE(orders.amount_due, 0)) as total_due'),
            )
            ->groupBy('orders.customer_id', 'customers.name', 'customers.phone');

        // Also get total quantity from order_items
        $itemAgg = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.customer_id', $customerIds)
            ->whereBetween('orders.created_at', [$fromDate, $toDate])
            ->whereNotIn('orders.status', ['cancelled', 'rejected'])
            ->select(
                'orders.customer_id',
                DB::raw('SUM(COALESCE(order_items.quantity, 0)) as total_qty')
            )
            ->groupBy('orders.customer_id');

        $aggRows = $orderAgg->get()->keyBy('customer_id');
        $qtyRows = $itemAgg->get()->keyBy('customer_id');

        // Merge
        $merged = $aggRows->map(function ($row) use ($qtyRows) {
            $row->total_qty = (float) ($qtyRows[$row->customer_id]->total_qty ?? 0);
            return $row;
        });

        // Sort
        if ($sortBy === 'name') {
            $merged = $sortDir === 'asc'
                ? $merged->sortBy('customer_name')
                : $merged->sortByDesc('customer_name');
        } else {
            $merged = $sortDir === 'asc'
                ? $merged->sortBy($sortBy)
                : $merged->sortByDesc($sortBy);
        }

        $total = $merged->count();
        $rows  = $merged->forPage($page, $perPage)->values();

        // ── Sparkline: per-customer order counts for mini-charts ──────────
        $sparklineLabels = $this->generateLabels($fromDate, $toDate, $reportType);

        if ($reportType === 'month') {
            $spExpr = "DATE_FORMAT(orders.created_at, '%Y-%m')";
        } elseif ($reportType === 'week') {
            $spExpr = "CONCAT(YEAR(orders.created_at), '-W', LPAD(WEEK(orders.created_at, 3), 2, '0'))";
        } else {
            $spExpr = "DATE(orders.created_at)";
        }

        $pageIds = $rows->pluck('customer_id');
        $sparklineRows = Order::query()
            ->whereIn('customer_id', $pageIds)
            ->whereBetween('orders.created_at', [$fromDate, $toDate])
            ->whereNotIn('orders.status', ['cancelled', 'rejected'])
            ->select(
                'customer_id',
                DB::raw("({$spExpr}) as period"),
                DB::raw('COUNT(id) as cnt')
            )
            ->groupBy('customer_id', 'period')
            ->get()
            ->groupBy('customer_id');

        $rows = $rows->map(function ($row) use ($sparklineRows, $sparklineLabels) {
            $byPeriod = ($sparklineRows->get($row->customer_id) ?? collect())->keyBy('period');
            $row->sparkline = array_map(
                fn($lbl) => $byPeriod->has($lbl) ? (int) $byPeriod[$lbl]->cnt : 0,
                $sparklineLabels
            );
            return $row;
        });

        // ── Chart data: grouped by day/week/month ─────────────────────────
        $chartData = $this->buildChartData($customerIds, $fromDate, $toDate, $reportType);

        return response()->json([
            'rows'             => $rows,
            'total'            => $total,
            'page'             => $page,
            'per_page'         => $perPage,
            'last_page'        => (int) ceil($total / $perPage),
            'chart'            => $chartData,
            'sparkline_labels' => $sparklineLabels,
            'summary'          => [
                'total_customers' => $total,
                'total_orders'    => $merged->sum('order_count'),
                'total_revenue'   => $merged->sum('total_revenue'),
                'total_qty'       => $merged->sum('total_qty'),
            ],
        ]);
    }

    /**
     * Detail page for a single customer.
     */
    public function show(Customer $customer, Request $request)
    {
        $user = Auth::user();
        $this->authorizeCustomerAccess($user, $customer);

        return view('site.customer_tracking.show', [
            'settings' => $this->settings,
            'user'     => $user,
            'customer' => $customer,
        ]);
    }

    /**
     * AJAX data for a single customer's charts + recent orders.
     */
    public function customerData(Customer $customer, Request $request)
    {
        $user = Auth::user();
        $this->authorizeCustomerAccess($user, $customer);

        $fromDate = $request->input('from_date')
            ? Carbon::parse($request->input('from_date'))->startOfDay()
            : Carbon::now()->subDays(29)->startOfDay();

        $toDate = $request->input('to_date')
            ? Carbon::parse($request->input('to_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        if ($fromDate->gt($toDate)) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $reportType = $request->input('report_type', 'day');
        if (!in_array($reportType, ['day', 'week', 'month'], true)) {
            $reportType = 'day';
        }

        $cid = $customer->id;

        // ── KPI summary ───────────────────────────────────────────────
        $ordersInRange = Order::where('customer_id', $cid)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->whereNotIn('status', ['cancelled', 'rejected']);

        $orderCount   = (clone $ordersInRange)->count();
        $totalRevenue = (clone $ordersInRange)->sum('total');
        $totalDue     = (clone $ordersInRange)->sum('amount_due');

        $totalQty = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.customer_id', $cid)
            ->whereBetween('orders.created_at', [$fromDate, $toDate])
            ->whereNotIn('orders.status', ['cancelled', 'rejected'])
            ->sum('order_items.quantity');

        // ── Chart data ────────────────────────────────────────────────
        $chartData = $this->buildChartDataForCustomer($cid, $fromDate, $toDate, $reportType);

        // ── Recent orders ─────────────────────────────────────────────
        $recentOrders = Order::where('customer_id', $cid)
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->orderByDesc('created_at')
            ->with(['items'])
            ->take(50)
            ->get()
            ->map(function ($o) {
                $qty = $o->items->sum('quantity');
                return [
                    'id'         => $o->id,
                    'code'       => $o->code ?: ('#' . $o->id),
                    'status'     => $o->status,
                    'total'      => (float) $o->total,
                    'amount_due' => (float) $o->amount_due,
                    'qty'        => (int) $qty,
                    'created_at' => optional($o->created_at)->format('d/m/Y'),
                ];
            });

        // ── All-time totals for debt block ────────────────────────────
        $allTimeDebt = (float) Order::where('customer_id', $cid)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->sum('amount_due');

        return response()->json([
            'summary' => [
                'order_count'   => $orderCount,
                'total_revenue' => (float) $totalRevenue,
                'total_qty'     => (float) $totalQty,
                'total_due'     => (float) $totalDue,
                'all_time_debt' => $allTimeDebt,
            ],
            'chart'         => $chartData,
            'recent_orders' => $recentOrders,
        ]);
    }

    /**
     * Check the authenticated user is allowed to view this customer's data.
     */
    private function authorizeCustomerAccess($user, Customer $customer): void
    {
        if ($user->isAdmin()) return;

        $isManager = $user->hasRole('manager') || $user->hasRole('manager_sale');
        if ($isManager) return;

        $isLeader = $user->hasRole('leader') || $user->hasRole('leader_sale') || $user->hasRole('sale_manager');
        if ($isLeader) {
            $teamId = $user->team_id;
            if ($teamId) {
                $teamMemberIds = \App\Models\User::where('team_id', $teamId)->pluck('id')->toArray();
                $ok = in_array($customer->user_id, $teamMemberIds, true)
                   || in_array($customer->assigned_to, $teamMemberIds, true);
                if ($ok) return;
            }
        }

        // sale: must own or be assigned
        $ok = (int) $customer->user_id === (int) $user->id
           || (int) $customer->assigned_to === (int) $user->id;

        abort_if(!$ok, 403, 'Bạn không có quyền xem khách hàng này.');
    }

    private function buildChartDataForCustomer(int $cid, Carbon $from, Carbon $to, string $type): array
    {
        return $this->buildChartData(collect([$cid]), $from, $to, $type);
    }

    private function buildChartData($customerIds, Carbon $from, Carbon $to, string $type): array
    {
        if ($type === 'month') {
            $periodExpr = "DATE_FORMAT(orders.created_at, '%Y-%m')";
        } elseif ($type === 'week') {
            // ISO year-week: e.g. 2024-W03
            $periodExpr = "CONCAT(YEAR(orders.created_at), '-W', LPAD(WEEK(orders.created_at, 3), 2, '0'))";
        } else {
            $periodExpr = "DATE(orders.created_at)";
        }

        // Orders aggregated by period
        $orderRows = Order::query()
            ->whereIn('customer_id', $customerIds)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->select(
                DB::raw("({$periodExpr}) as period"),
                DB::raw('COUNT(id) as order_count'),
                DB::raw('SUM(COALESCE(total, 0)) as total_revenue'),
                DB::raw('SUM(COALESCE(amount_due, 0)) as total_due')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        // Item quantities aggregated by period
        $qtyRows = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.customer_id', $customerIds)
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereNotIn('orders.status', ['cancelled', 'rejected'])
            ->select(
                DB::raw("({$periodExpr}) as period"),
                DB::raw('SUM(COALESCE(order_items.quantity, 0)) as total_qty')
            )
            ->groupBy('period')
            ->get()
            ->keyBy('period');

        $labels = $this->generateLabels($from, $to, $type);

        $orderCounts = [];
        $totalQtys   = [];
        $revenues    = [];
        $dues        = [];

        foreach ($labels as $label) {
            $o = $orderRows[$label] ?? null;
            $q = $qtyRows[$label] ?? null;
            $orderCounts[] = $o ? (int) $o->order_count : 0;
            $revenues[]    = $o ? (float) $o->total_revenue : 0;
            $dues[]        = $o ? (float) $o->total_due : 0;
            $totalQtys[]   = $q ? (float) $q->total_qty : 0;
        }

        return compact('labels', 'orderCounts', 'totalQtys', 'revenues', 'dues');
    }

    private function generateLabels(Carbon $from, Carbon $to, string $type): array
    {
        $labels = [];

        if ($type === 'day') {
            $period = CarbonPeriod::create($from->toDateString(), '1 day', $to->toDateString());
            foreach ($period as $date) {
                $labels[] = $date->format('Y-m-d');
            }
        } elseif ($type === 'week') {
            $current = $from->copy()->startOfWeek(Carbon::MONDAY);
            while ($current->lte($to)) {
                $labels[] = $current->format('Y') . '-W' . str_pad((string) $current->weekOfYear, 2, '0', STR_PAD_LEFT);
                $current->addWeek();
            }
        } else {
            // month
            $current = $from->copy()->startOfMonth();
            while ($current->lte($to)) {
                $labels[] = $current->format('Y-m');
                $current->addMonth();
            }
        }

        return $labels;
    }
}
