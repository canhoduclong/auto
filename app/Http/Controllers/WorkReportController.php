<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Models\AdminEvent;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class WorkReportController extends Controller
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
        $user = $request->user();
        $today = Carbon::today();

        $allowedTabs = ['orders', 'new-customers', 'all-customers', 'daily-activities'];
        $tab = (string) $request->input('tab', 'orders');
        if (!in_array($tab, $allowedTabs, true)) {
            $tab = 'orders';
        }

        $allowedPerPage = [10, 20, 50, 100];
        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $type = (string) $request->input('type', 'month');
        if (!in_array($type, ['today', 'week', 'month', 'all', 'range'], true)) {
            $type = 'month';
        }

        $date = (string) $request->input('date', $today->toDateString());
        $fromDateInput = (string) $request->input('from_date', '');
        $toDateInput = (string) $request->input('to_date', '');

        $applyDateFilter = $type !== 'all';

        if ($type === 'today') {
            $start = $today->copy()->startOfDay();
            $end = $today->copy()->endOfDay();
        } elseif ($type === 'week') {
            $baseDate = $date !== '' ? Carbon::parse($date) : $today->copy();
            $start = $baseDate->copy()->startOfWeek()->startOfDay();
            $end = $baseDate->copy()->endOfWeek()->endOfDay();
        } elseif ($type === 'all') {
            $start = Carbon::create(1970, 1, 1)->startOfDay();
            $end = now()->endOfDay();
        } elseif ($type === 'range') {
            $from = $fromDateInput !== '' ? Carbon::parse($fromDateInput) : $today->copy();
            $to = $toDateInput !== '' ? Carbon::parse($toDateInput) : $from->copy();

            if ($from->gt($to)) {
                [$from, $to] = [$to, $from];
            }

            $start = $from->copy()->startOfDay();
            $end = $to->copy()->endOfDay();
            $fromDateInput = $start->toDateString();
            $toDateInput = $end->toDateString();
        } else {
            $baseDate = $date !== '' ? Carbon::parse($date) : $today->copy();
            $start = $baseDate->copy()->startOfMonth()->startOfDay();
            $end = $baseDate->copy()->endOfMonth()->endOfDay();
        }

        $productCount = Product::query()
            ->where('user_id', $user->id)
            ->when($applyDateFilter, function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end]);
            })
            ->count();

        $ordersQuery = Order::query()
            ->where('user_id', $user->id)
            ->when($applyDateFilter, function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end]);
            });

        $newCustomersQuery = Customer::query()
            ->where('user_id', $user->id)
            ->when($applyDateFilter, function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end]);
            });

        $allCustomersQuery = Customer::query()
            ->where('user_id', $user->id)
            ->when($applyDateFilter, function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end]);
            });

        $oldCustomerCount = (clone $ordersQuery)
            ->whereHas('customer', function($q) use ($start, $user) {
                $q->where('created_at', '<', $start)
                  ->where('user_id', $user->id);
            })
            ->distinct('customer_id')
            ->count('customer_id');

        $orderCount = (clone $ordersQuery)->count();
        $newCustomerCount = (clone $newCustomersQuery)->count();
        $allCustomerCount = (clone $allCustomersQuery)->count();
        $totalCustomerCount = $allCustomerCount;

        $totalRevenue = (float) (clone $ordersQuery)->sum('total');
        $totalDebt = (float) (clone $ordersQuery)->sum('amount_due');
        $interactingCustomerCount = (clone $ordersQuery)->distinct('customer_id')->count('customer_id');

        $roleFilter = ['sale', 'leader', 'leader_sale', 'sale_manager', 'manager', 'manager_sale'];

        $isSale = $user->hasRole('sale');
        $isLeader = $user->hasRole('leader') || $user->hasRole('leader_sale') || $user->hasRole('sale_manager');
        $isManager = $user->hasRole('manager') || $user->hasRole('manager_sale');

        $activityActorsQuery = User::query()->whereHas('roles', function ($query) use ($roleFilter) {
            $query->whereIn('name', $roleFilter);
        });

        if ($isSale) {
            $activityActorsQuery->where('id', $user->id);
        } elseif ($isLeader && !empty($user->team_id)) {
            $activityActorsQuery->where('team_id', $user->team_id);
        } elseif ($isLeader && empty($user->team_id)) {
            $activityActorsQuery->where('id', $user->id);
        } elseif (!$isManager) {
            $activityActorsQuery->where('id', $user->id);
        }

        $activityActorIds = $activityActorsQuery->pluck('id');

        $activitiesQuery = AdminEvent::query()
            ->with(['actor.roles'])
            ->whereIn('actor_id', $activityActorIds)
            ->when($applyDateFilter, function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end]);
            });

        $activityCount = (clone $activitiesQuery)->count();
        $activeUserCount = (clone $activitiesQuery)->distinct('actor_id')->count('actor_id');
        $activityByDay = (clone $activitiesQuery)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy(fn ($event) => $event->created_at?->format('d/m/Y'))
            ->map(fn ($events, $day) => ['day' => $day, 'count' => $events->count()])
            ->values();

        $tabCounts = [
            'orders' => $orderCount,
            'new-customers' => $newCustomerCount,
            'all-customers' => $allCustomerCount,
            'daily-activities' => $activityCount,
        ];

        $tabData = match ($tab) {
            'new-customers' => (clone $newCustomersQuery)
                ->with(['latestCareLog.user'])
                ->orderByDesc('created_at')
                ->paginate($perPage)
                ->appends($request->query()),
            'all-customers' => (clone $allCustomersQuery)
                ->with(['latestCareLog.user'])
                ->orderByDesc('created_at')
                ->paginate($perPage)
                ->appends($request->query()),
            'daily-activities' => (clone $activitiesQuery)
                ->orderByDesc('created_at')
                ->paginate($perPage)
                ->appends($request->query()),
            default => (clone $ordersQuery)
                ->with('customer')
                ->orderByDesc('created_at')
                ->paginate($perPage)
                ->appends($request->query()),
        };

        if ($request->boolean('ajax_tab')) {
            $html = view('site.partials.work_report_tab_content', [
                'tab' => $tab,
                'tabData' => $tabData,
                'statusClasses' => [
                    Order::STATUS_COMPLETED => 'success',
                    Order::STATUS_DELIVERED => 'success',
                    Order::STATUS_ORDER_PLACED => 'pending',
                    Order::STATUS_ORDER_CONFIRMED => 'progress',
                    Order::STATUS_PACKED => 'progress',
                    Order::STATUS_IN_DELIVERY => 'progress',
                    Order::STATUS_READY_TO_PACK => 'pending',
                    Order::STATUS_PACKING => 'progress',
                    Order::STATUS_READY_TO_SHIP => 'progress',
                    Order::STATUS_DELIVERING => 'progress',
                    Order::STATUS_RETURNING => 'danger',
                    Order::STATUS_RETURNED_COMPLETED => 'muted',
                    Order::STATUS_RETURNED => 'danger',
                    Order::STATUS_CANCELLED => 'danger',
                    'shipping' => 'progress',
                    'picked_up' => 'progress',
                ],
                'statusLabels' => Order::statusOptions() + [
                    Order::STATUS_READY_TO_PACK => 'Chờ đóng gói',
                    Order::STATUS_PACKING => 'Đang đóng gói',
                    Order::STATUS_READY_TO_SHIP => 'Chờ giao đơn vị vận chuyển',
                    Order::STATUS_DELIVERING => 'Đang giao hàng',
                    Order::STATUS_RETURNING => 'Đang trả hàng',
                    Order::STATUS_RETURNED_COMPLETED => 'Đã nhập kho trả hàng',
                    'shipping' => 'Đang vận chuyển',
                    'picked_up' => 'Đã lấy hàng',
                ],
                'activityByDay' => $activityByDay,
                'activityCount' => $activityCount,
                'activeUserCount' => $activeUserCount,
            ])->render();

            return response()->json([
                'html' => $html,
                'counts' => $tabCounts,
                'tab' => $tab,
                'per_page' => $perPage,
            ]);
        }

        return view('site.work_report', [
            'tab' => $tab,
            'perPage' => $perPage,
            'tabCounts' => $tabCounts,
            'tabData' => $tabData,
            'type' => $type,
            'date' => $date,
            'fromDate' => $fromDateInput,
            'toDate' => $toDateInput,
            'productCount' => $productCount,
            'orderCount' => $orderCount,
            'newCustomerCount' => $newCustomerCount,
            'oldCustomerCount' => $oldCustomerCount,
            'totalCustomerCount' => $totalCustomerCount,
            'interactingCustomerCount' => $interactingCustomerCount,
            'totalRevenue' => $totalRevenue,
            'totalDebt' => $totalDebt,
            'activityCount' => $activityCount,
            'activeUserCount' => $activeUserCount,
            'activityByDay' => $activityByDay,
            'settings' => $this->settings,
            'start' => $start,
            'end' => $end,
        ]);
    }
}
