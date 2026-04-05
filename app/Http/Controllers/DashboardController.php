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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user?->hasRole('ceo')) {
            return redirect()->route('ceo.dashboard');
        }

        if ($user?->hasRole('accountant') || $user?->hasRole('accounting')) {
            return redirect()->route('accounting.dashboard');
        }

        if ($user?->hasRole('warehouse')) {
            return redirect()->route('warehouse.dashboard');
        }

        if ($user?->hasRole('shipper')) {
            return redirect()->route('shipper.dashboard');
        }

        $isSalesFlowRole = $user?->hasRole('sale')
            || $user?->hasRole('leader')
            || $user?->hasRole('leader_sale')
            || $user?->hasRole('sale_manager')
            || $user?->hasRole('manager')
            || $user?->hasRole('manager_sale');

        if ($isSalesFlowRole) {
            return redirect()
                ->route('pages.my_orders.monitoring')
                ->with('error', 'Vai trò của bạn không được truy cập Dashboard.');
        }

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

        $dailyProductPrices = ProductVariant::query()
            ->with(['product.avatar.media', 'latestPriceRule'])
            ->whereHas('product', function ($query) {
                $query->where('status', true);
            })
            ->orderBy('product_id')
            ->orderBy('sku')
            ->get()
            ->groupBy('product_id')
            ->map(function ($variants) {
                $product = $variants->first()?->product;
                if (!$product) {
                    return null;
                }

                $variantRows = $variants->map(function (ProductVariant $variant) {
                    $price = (float) ($variant->latestPriceRule?->price ?? $variant->final_price);

                    return [
                        'variant_sku' => $variant->sku ?? '-',
                        'price' => $price,
                        'price_key' => number_format($price, 4, '.', ''),
                    ];
                })->values();

                if ($variantRows->isEmpty()) {
                    return null;
                }

                $groupedByPrice = $variantRows->groupBy('price_key');
                $representativeGroup = $groupedByPrice->sortByDesc(function ($items) {
                    return $items->count();
                })->first();

                $representativePrice = (float) ($representativeGroup[0]['price'] ?? 0);
                $representativePriceKey = $representativeGroup[0]['price_key'] ?? number_format(0, 4, '.', '');

                $differentVariants = $variantRows
                    ->filter(function ($row) use ($representativePriceKey) {
                        return $row['price_key'] !== $representativePriceKey;
                    })
                    ->values();

                return [
                    'product_name' => $product->name,
                    'product_avatar_path' => $product->avatar?->media?->file_path,
                    'representative_price' => $representativePrice,
                    'representative_variants_count' => count($representativeGroup),
                    'total_variants_count' => $variantRows->count(),
                    'is_uniform_price' => $groupedByPrice->count() === 1,
                    'different_variants' => $differentVariants,
                ];
            })
            ->filter()
            ->sortBy('product_name')
            ->take(12)
            ->values();

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
            'dailyProductPrices' => $dailyProductPrices,
            'latestOrders' => $latestOrders,
            'topProducts' => $topProducts,
            'dailyStats' => $dailyStats,
            'ordersByStatus' => $ordersByStatus,
        ];
    }

    public function deploy(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->hasRole('admin')) {
            abort(403, 'Bạn không có quyền thực hiện deploy.');
        }

        if ((string) $request->input('key') !== 'huy2024') {
            return back()->with('error', 'Sai key deploy.');
        }

        $deployPath = '/home/hltnt/public_html';
        $branch = 'hoanglong';
        $logs = [];

        $logs[] = 'Deploy branch: ' . $branch;
        $logs[] = 'Deploy path: ' . $deployPath;
        $logs[] = '';
        $logs[] = 'Pulling code...';

        [$pullCode, $pullOutput] = $this->runDeployCommand("cd {$deployPath} && git pull origin {$branch}");
        $logs = array_merge($logs, $pullOutput);
        $logs[] = '';

        if ($pullCode !== 0) {
            $logs[] = 'Deploy failed at step: git pull';

            return back()
                ->with('error', 'Deploy thất bại ở bước pull code.')
                ->with('deploy_output', implode("\n", $logs))
                ->with('deploy_status', 'error');
        }

        $logs[] = 'Changed files:';
        [$diffCode, $diffOutput] = $this->runDeployCommand("cd {$deployPath} && git diff --name-only ORIG_HEAD HEAD");
        if ($diffCode === 0 && !empty($diffOutput)) {
            $logs = array_merge($logs, $diffOutput);
        } else {
            $logs[] = '(No changed files or already up-to-date)';
        }
        $logs[] = '';

        $steps = [
            ['title' => 'Running migrate...', 'command' => "cd {$deployPath} && php artisan migrate --force", 'fail' => 'migrate'],
            ['title' => 'Clearing cache...', 'command' => "cd {$deployPath} && php artisan optimize:clear", 'fail' => 'optimize:clear'],
            ['title' => 'Caching config...', 'command' => "cd {$deployPath} && php artisan config:cache", 'fail' => 'config:cache'],
            ['title' => 'Caching routes...', 'command' => "cd {$deployPath} && php artisan route:cache", 'fail' => 'route:cache'],
        ];

        foreach ($steps as $step) {
            $logs[] = $step['title'];
            [$code, $output] = $this->runDeployCommand($step['command']);
            $logs = array_merge($logs, $output);
            $logs[] = '';

            if ($code !== 0) {
                $logs[] = 'Deploy failed at step: ' . $step['fail'];

                return back()
                    ->with('error', 'Deploy thất bại ở bước ' . $step['fail'] . '.')
                    ->with('deploy_output', implode("\n", $logs))
                    ->with('deploy_status', 'error');
            }
        }

        $logs[] = 'Deploy success.';

        return back()
            ->with('success', 'deploy success')
            ->with('deploy_output', implode("\n", $logs))
            ->with('deploy_status', 'success');
    }

    private function runDeployCommand(string $command): array
    {
        $output = [];
        $exitCode = 0;
        exec($command . ' 2>&1', $output, $exitCode);

        if (empty($output)) {
            $output[] = '(No output)';
        }

        return [$exitCode, $output];
    }
}
