<?php

namespace App\Http\Controllers;

use App\Models\ApprovalOrder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $roleName = $user?->roles()->pluck('name')->first() ?? 'default';

        $adminData = [];
        if ($roleName === 'admin') {
            $adminData = $this->buildAdminDashboardData();
        }

        return match ($roleName) {
            'admin'   => view('dashboard.admin', compact('user', 'adminData')),
            'manager' => view('dashboard.manager', compact('user')),
            'staff'   => view('dashboard.staff', compact('user')),
            default   => view('dashboard.default', compact('user')),
        };
    }

    private function buildAdminDashboardData(): array
    {
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();

        $totalOrders = Order::count();
        $todayOrders = Order::whereDate('created_at', $today)->count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $pendingApprovals = ApprovalOrder::where('status', 'pending')->count();

        $grossPaymentsThisMonth = (float) Transaction::where('type', 'payment')
            ->where('created_at', '>=', $monthStart)
            ->sum('amount');

        $refundsThisMonth = (float) Transaction::where('type', 'refund')
            ->where('created_at', '>=', $monthStart)
            ->sum('amount');

        $netRevenueThisMonth = $grossPaymentsThisMonth - $refundsThisMonth;

        $activeProducts = Product::where('status', true)->count();
        $inactiveProducts = Product::where('status', false)->count();
        $outOfStockVariants = ProductVariant::where('stock', '<=', 0)->count();
        $totalCustomers = Customer::count();
        $newCustomers30d = Customer::where('created_at', '>=', Carbon::now()->subDays(30))->count();

        $latestOrders = Order::with(['customer', 'user'])
            ->latest()
            ->take(8)
            ->get();

        $topProducts = OrderItem::query()
            ->select('product_id', DB::raw('SUM(quantity) as sold_qty'), DB::raw('SUM(total) as sold_amount'))
            ->whereNotNull('product_id')
            ->groupBy('product_id')
            ->orderByDesc('sold_qty')
            ->with('product')
            ->take(6)
            ->get();

        $dailyStatsRaw = Order::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as order_count, COALESCE(SUM(total), 0) as total_amount')
            ->whereDate('created_at', '>=', Carbon::now()->subDays(6))
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $dailyStats = collect(range(6, 0))
            ->map(function (int $dayOffset) use ($dailyStatsRaw) {
                $date = Carbon::today()->subDays($dayOffset)->toDateString();
                $row = $dailyStatsRaw->get($date);

                return [
                    'date' => $date,
                    'label' => Carbon::parse($date)->format('d/m'),
                    'orders' => (int) ($row->order_count ?? 0),
                    'amount' => (float) ($row->total_amount ?? 0),
                ];
            })
            ->values();

        $ordersByStatus = Order::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return [
            'totalOrders' => $totalOrders,
            'todayOrders' => $todayOrders,
            'pendingOrders' => $pendingOrders,
            'pendingApprovals' => $pendingApprovals,
            'grossPaymentsThisMonth' => $grossPaymentsThisMonth,
            'refundsThisMonth' => $refundsThisMonth,
            'netRevenueThisMonth' => $netRevenueThisMonth,
            'activeProducts' => $activeProducts,
            'inactiveProducts' => $inactiveProducts,
            'outOfStockVariants' => $outOfStockVariants,
            'totalCustomers' => $totalCustomers,
            'newCustomers30d' => $newCustomers30d,
            'latestOrders' => $latestOrders,
            'topProducts' => $topProducts,
            'dailyStats' => $dailyStats,
            'ordersByStatus' => $ordersByStatus,
        ];
    }
}
