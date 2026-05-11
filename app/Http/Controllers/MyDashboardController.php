<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\TaskAssignment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class MyDashboardController extends Controller
{
    public function __construct()
    {
        $this->settings = Cache::remember('settings', 60, function () {
            return Setting::all()->keyBy('key');
        });
    }
    public function index()
    {
        $user = auth()->user();
        abort_unless($user, 403);

        $payload = $this->buildPayload($user);
        $settings   = $this->settings;
        return view('site.my_dashboard_sales', array_merge($payload, ['settings' => $settings]));
    }

    public function stats(): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user, 403);

        return response()->json($this->buildPayload($user));
    }

    private function buildPayload(User $user): array
    {
        $now = now();
        $monthStart = $now->copy()->startOfMonth();

        $memberIds = $this->resolveScopedUserIds($user);

        $completedStatuses = ['completed', Order::STATUS_COMPLETED, Order::STATUS_DELIVERED];
        $ordersBaseQuery = Order::query()->whereIn('user_id', $memberIds);

        $totalRevenue = (float) (clone $ordersBaseQuery)
            ->whereIn('status', $completedStatuses)
            ->sum('total');

        $ordersThisMonth = (int) (clone $ordersBaseQuery)
            ->whereBetween('created_at', [$monthStart, $now])
            ->count();

        $customerIdsQuery = (clone $ordersBaseQuery)
            ->select('customer_id')
            ->whereNotNull('customer_id')
            ->distinct();

        $totalCustomers = (int) (clone $customerIdsQuery)->count('customer_id');

        $activeCustomerCount = (int) (clone $ordersBaseQuery)
            ->whereNotNull('customer_id')
            ->whereDate('created_at', '>=', now()->subDays(30)->toDateString())
            ->distinct('customer_id')
            ->count('customer_id');

        $taskBaseQuery = TaskAssignment::query()
            ->whereHas('assignees', fn ($q) => $q->whereIn('user_id', $memberIds));

        $taskProcessingCount = (int) (clone $taskBaseQuery)
            ->whereIn('status', ['processing', 'in_progress'])
            ->count();

        $taskUnfinishedCount = (int) (clone $taskBaseQuery)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->count();

        $commissionThisMonth = 0.0;
        $commissionFeed = collect();
        if (Schema::hasTable('order_commissions')) {
            $commissionBase = DB::table('order_commissions as oc')
                ->leftJoin('orders as o', 'o.id', '=', 'oc.order_id')
                ->leftJoin('customers as c', 'c.id', '=', 'oc.customer_id')
                ->whereIn('oc.sale_user_id', $memberIds);

            $commissionThisMonth = (float) (clone $commissionBase)
                ->whereBetween('oc.confirmed_at', [$monthStart, $now])
                ->sum('oc.commission_amount');

            $commissionFeed = (clone $commissionBase)
                ->select([
                    'oc.order_id',
                    'oc.order_total',
                    'oc.commission_percent',
                    'oc.commission_amount',
                    'oc.confirmed_at',
                    'o.code as order_code',
                    'c.name as customer_name',
                ])
                ->whereNotNull('oc.confirmed_at')
                ->orderByDesc('oc.confirmed_at')
                ->limit(12)
                ->get();
        }

        $salesChart = $this->buildSalesChart($memberIds, $monthStart, $now);

        $timeline = DB::table('task_status_logs as l')
            ->join('task_assignments as t', 't.id', '=', 'l.task_id')
            ->leftJoin('users as u', 'u.id', '=', 'l.changed_by')
            ->where(function ($query) use ($memberIds) {
                $query->whereIn('t.created_by', $memberIds)
                    ->orWhereExists(function ($sub) use ($memberIds) {
                        $sub->select(DB::raw(1))
                            ->from('task_assignees as ta')
                            ->whereColumn('ta.task_id', 't.id')
                            ->whereIn('ta.user_id', $memberIds);
                    });
            })
            ->orderByDesc('l.created_at')
            ->limit(15)
            ->get([
                'l.from_status',
                'l.to_status',
                'l.reason',
                'l.created_at',
                't.code as task_code',
                't.title as task_title',
                'u.name as changed_by_name',
            ]);

        return [
            'dashboardStats' => [
                'total_revenue' => $totalRevenue,
                'commission_this_month' => $commissionThisMonth,
                'total_customers' => $totalCustomers,
                'active_customers' => $activeCustomerCount,
                'orders_this_month' => $ordersThisMonth,
                'tasks_processing' => $taskProcessingCount,
                'tasks_unfinished' => $taskUnfinishedCount,
            ],
            'commissionFeed' => $commissionFeed,
            'salesChart' => $salesChart,
            'timeline' => $timeline,
        ];
    }

    private function resolveScopedUserIds(User $user): array
    {
        if ($user->hasRole('sale')) {
            return [$user->id];
        }

        if ($user->hasRole('leader') || $user->hasRole('leader_sale') || $user->hasRole('sale_manager')) {
            $memberIds = User::query()
                ->where('team_id', $user->team_id)
                ->pluck('id')
                ->all();

            return empty($memberIds) ? [$user->id] : $memberIds;
        }

        if ($user->hasRole('manager') || $user->hasRole('manager_sale')) {
            $memberIds = User::query()
                ->when($user->team_id, fn ($q) => $q->where('team_id', $user->team_id))
                ->pluck('id')
                ->all();

            return empty($memberIds) ? [$user->id] : $memberIds;
        }

        return [$user->id];
    }

    private function buildSalesChart(array $memberIds, Carbon $from, Carbon $to): array
    {
        $rows = Order::query()
            ->selectRaw('DATE(created_at) as day, COALESCE(SUM(total), 0) as total_amount')
            ->whereIn('user_id', $memberIds)
            ->whereIn('status', ['completed', Order::STATUS_COMPLETED, Order::STATUS_DELIVERED])
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $labels = [];
        $values = [];
        $cursor = $from->copy();

        while ($cursor->lte($to)) {
            $day = $cursor->toDateString();
            $labels[] = $cursor->format('d/m');
            $values[] = (float) ($rows[$day]->total_amount ?? 0);
            $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }
}
